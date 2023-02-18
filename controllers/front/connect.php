<?php

use Stanconnect\Utils\Logger;

class StanConnectConnectModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $err = Tools::getValue('error');

        $redirect_to = $this->context->cart->id ? 'order' : 'authentication';

        if ($err) {
            Logger::write('[RedirectURI] Redirect URI returned an error', 3, [
                'redirect_uri_path' => $this->context->smarty->tpl_vars['request_uri'],
            ]);
            Tools::redirectLink($redirect_to . "?stan_connect_error=$err", true);
        }

        $access_token_payload = new \Stan\Model\ConnectAccessTokenRequestBody();
        $access_token_payload = $access_token_payload
            ->setClientId($this->module->getClientId())
            ->setClientSecret($this->module->getClientSecret())
            ->setCode(Tools::getValue('code'))
            ->setGrantType('authorization_code')
            ->setScope($this->module->getScope())
            ->setRedirectUri($this->module->getRedirectUri());

        $stan_client = new Stan\Api\StanClient($this->module->getApiConfiguration());

        try {
            $access_token_res = $stan_client->connectApi->createConnectAccessToken($access_token_payload);

            $config = $this->module->getApiConfiguration()
                ->setAccessToken($access_token_res->getAccessToken());

            $stan_client->setConfiguration($config);

            $user = $stan_client->userApi->getUser();

            $this->module->updateAndConnectCustomer($user);

            Tools::redirectLink($redirect_to, true);
        } catch (Exception $e) {
            $access_token_payload->setClientSecret('***hiden***');
            Logger::write('[RedirectURI] Failed to request user infos', 3, [
                'error' => $e,
                'payload' => json_encode($access_token_payload->jsonSerialize()),
            ]);
            Tools::redirectLink($redirect_to . '?stan_connect_error=unauthorized_client', true);
        }
    }
}
