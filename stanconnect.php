<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Stan\Utils\ConnectUtils;
use Stanconnect\Utils\Logger;

class StanConnect extends Module
{
    public const STAN_CONNECT_MODULE = 'STAN_CONNECT';
    public const STAN_CONNECT_CLIENT_ID = 'STAN_CONNECT_CLIENT_ID';
    public const STAN_CONNECT_CLIENT_SECRET = 'STAN_CONNECT_CLIENT_SECRET';

    public function __construct()
    {
        $this->module_key = "ad76540710823467933bdeffe4afbcea";
        $this->name = 'stanconnect';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Brightweb';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6',
            'max' => '1.7.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Stan Connect');
        $this->description = $this->l('Get more customers with the 1 click login');

        $this->confirmUninstall = $this->l('Are you sure to remove Stan Connect?');

        if (!Configuration::get(self::STAN_CONNECT_MODULE)) {
            $this->warning = $this->l('No name provided');
        }
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install() &&
            $this->registerHook('displayCustomerLoginFormAfter') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayPersonalInformationTop');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookDisplayCustomerLoginFormAfter($params)
    {
        if ($this->context->customer->isLogged()) {
            return null;
        }

        $state = Stan\Utils\StanUtils::generateState();

        $client_id = Configuration::get(self::STAN_CONNECT_CLIENT_ID, null);

        $connect_url = ConnectUtils::generateAuthorizeRequestLink(
            $client_id,
            $this->getRedirectUri(),
            $state,
            [ConnectUtils::ScopePhone, ConnectUtils::ScopeEmail, ConnectUtils::ScopeAddress, ConnectUtils::ScopeProfile],
            $this->getApiConfiguration()
        );

        $error_code = Tools::getValue('stan_connect_error');
        $this->context->smarty->assign([
            'connect_url' => $connect_url,
            'error' => $error_code ? $this->getErrorMessage($error_code) : null,
        ]);

        return $this->display(__FILE__, 'button.tpl');
    }

    public function hookDisplayPersonalInformationTop($params)
    {
        $this->context->smarty->assign('title', 'Remplissez vos informations en un clic avec Stan');

        return $this->hookDisplayCustomerLoginFormAfter($params);
    }

    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerStylesheet(
            'stanconnect-style',
            $this->_path . 'views/css/styles.css',
            [
                'media' => 'all',
                'priority' => 1000,
            ]
        );
    }

    /**
     * This method handles the module's configuration page
     *
     * @return string The page's HTML content
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue(self::STAN_CONNECT_CLIENT_ID, Tools::getValue(self::STAN_CONNECT_CLIENT_ID));
            Configuration::updateValue(self::STAN_CONNECT_CLIENT_SECRET, Tools::getValue(self::STAN_CONNECT_CLIENT_SECRET));

            $output = $this->displayConfirmation($this->l('Your Stan Connect settings have been saved'));
        }

        $this->context->smarty->assign('form', $this->displayForm());
        $settings_content = $this->display(__FILE__, '/views/templates/admin/config.tpl');

        return $output . $settings_content;
    }

    /**
     * Builds the configuration form
     *
     * @return string HTML code
     */
    public function displayForm()
    {
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Stan Connect Settings'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Your Stan Connect Client name'),
                        'name' => self::STAN_CONNECT_CLIENT_ID,
                        'size' => 20,
                        'required' => false,
                        'hint' => $this->l("It's your Stan Connect client. Find it in your Stan account"),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Your Stan Connect client secret'),
                        'name' => self::STAN_CONNECT_CLIENT_SECRET,
                        'size' => 20,
                        'required' => false,
                        'hint' => $this->l("It's your Stan Connect client secret. Find it in your Stan account"),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        $helper->fields_value[self::STAN_CONNECT_CLIENT_ID] = Tools::getValue(self::STAN_CONNECT_CLIENT_ID, Configuration::get(self::STAN_CONNECT_CLIENT_ID));
        $helper->fields_value[self::STAN_CONNECT_CLIENT_SECRET] = Tools::getValue(self::STAN_CONNECT_CLIENT_SECRET, Configuration::get(self::STAN_CONNECT_CLIENT_SECRET));

        // Module, token and currentIndex
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        // Default language
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        return $helper->generateForm([$form]);
    }

    /**
     * @param Stan\Model\User
     *
     * @return void
     */
    public function updateAndConnectCustomer($stan_user)
    {
        $email = $stan_user->getEmail();

        // Safely get the authenticated user
        $customer_id = Customer::customerExists($email, true);

        if (!$customer_id) {
            $customer = new Customer();

            $customer->firstname = $stan_user->getGivenName();
            $customer->lastname = $stan_user->getFamilyName();
            $customer->email = $email;
            $customer->passwd = Tools::passwdGen(36);
            $customer->shopId = $this->context->shop->id;
            $customer->defaultGroupId = 3;

            $customer->save();
        } else {
            $customer = new Customer($customer_id);
        }

        // Find if the customer has already a Stan address
        $address_id = null;
        $customer_addresses = $customer->getAddresses($this->context->language->id);
        foreach ($customer->getAddresses($this->context->language->id) as $address) {
            if ($address['alias'] === $this->getAddressAlias()) {
                $address_id = (int) $address['id_address'];
                break;
            }
        }

        $stan_address = $stan_user->getShippingAddress();

        if ($stan_address->getStreetAddress()) {
            $address = new Address($address_id);

            $address->id_customer = (int) $customer->id;
            $address->alias = $this->getAddressAlias();
            $address->id_country = Country::getByIso('FR'); // TODO use good country
            $address->firstname = $stan_address->getFirstname() ?: $stan_user->getGivenName();
            $address->lastname = $stan_address->getLastname() ?: $stan_user->getFamilyName();
            $address->address1 = $stan_address->getStreetAddress();
            $address->address2 = $stan_address->getStreetAddressLine2();
            $address->city = $stan_address->getLocality();
            $address->postcode = $stan_address->getZipCode();
            $address->phone_mobile = $stan_user->getPhone();

            // Create or update the address
            if (!$address->save()) {
                Logger::write('[stanconnect] Failed to add address', 2, [
                    'address' => json_encode($stan_address->jsonSerialize()),
                    'id_customer' => $customer->id,
                ]);
            }
        }

        // Authenticate the user
        $this->context->updateCustomer($customer);
    }

    /**
     *  get the Api configuration
     *
     * @return \Stan\Configuration
     */
    public function getApiConfiguration()
    {
        $config = new Stan\Configuration();

        if (defined('_PS_STAN_CUSTOM_API_URL_')) {
            $config = $config->setHost(getenv('_PS_STAN_CUSTOM_API_URL_'));
        }

        return $config;
    }

    public function getClientId()
    {
        return Configuration::get(self::STAN_CONNECT_CLIENT_ID);
    }

    public function getClientSecret()
    {
        return Configuration::get(self::STAN_CONNECT_CLIENT_SECRET);
    }

    public function getScope()
    {
        return 'openid phone email address profile';
    }

    public function getAddressAlias()
    {
        return 'StanConnect';
    }

    /**
     * Get the redirect URI
     *
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->context->link->getModuleLink(
            $this->name,
            'connect',
            [],
            true,
            Configuration::get('PS_LANG_DEFAULT'),
            Configuration::get('PS_SHOP_DEFAULT')
        );
    }

    /**
     * @param string $error_code OAuth2 standard error code
     *
     * @return string
     */
    public function getErrorMessage($error_code)
    {
        switch ($error_code) {
            case 'invalid_request':
            case 'unsupported_response_type':
            case 'invalid_scope':
            case 'unauthorized_client':
                return $this->l("The website failed to connect your account. Please contact the support");
            case 'server_error':
            case 'temporarily_unavailable':
                return $this->l('There is an issue with Stan servers, we are working on this as fast as possible');
            case 'access_denied':
            default:
                return $this->l("You're not authorized to do this");
        }
    }
}
