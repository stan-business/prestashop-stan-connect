{*
* 2022 Brightweb
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
*  @author Brightweb SAS <jonathan@brightweb.cloud>
*  @copyright  2022 Brightweb SAS
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

{if $error}
<div class="alert alert-warning" role="alert">
    {$error}
</div>
{/if}
{if isset($title)}
<h1>{$title}</h1>
{/if}
<div id="stan-easy-connect" class="stan-button">
    <div class="stanconnect-tooltip">
        <a class="button button-large" href="{$connect_url}">
            <svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 800.000000 800.000000" preserveAspectRatio="xMidYMid meet">
                <g transform="translate(0.000000,800.000000) scale(0.100000,-0.100000)" fill="#FFFFFF" stroke="none">
                    <path d="M4185 7449 c-294 -24 -600 -110 -835 -237 -168 -89 -289 -180 -431 -322 -331 -330 -488 -643 -489 -972 0 -287 102 -516 340 -762 282 -292 715 -518 1225 -640 341 -82 493 -130 655 -210 127 -62 197 -111 276 -190 133 -133 160 -232 109 -393 -43 -136 -135 -266 -249 -351 -370 -275 -1042 -243 -1655 79 -222 116 -388 237 -565 409 -183 178 -295 326 -402 533 l-61 119 -19 -114 c-27 -159 -27 -491 0 -633 140 -755 782 -1303 1686 -1442 161 -25 522 -24 675 1 451 73 807 245 1077 519 295 299 441 667 425 1067 -7 159 -26 261 -77 405 -172 484 -619 858 -1275 1068 -144 46 -218 65 -535 132 -412 88 -509 123 -584 205 -89 99 -102 190 -43 312 56 115 138 192 280 262 172 84 336 121 592 132 264 12 499 -19 753 -101 589 -190 1069 -518 1407 -964 73 -97 198 -310 248 -424 193 -442 248 -961 148 -1414 -69 -315 -236 -656 -458 -933 -86 -108 -263 -285 -373 -373 -287 -231 -697 -440 -1080 -551 -661 -192 -1359 -208 -1995 -46 -210 53 -381 117 -590 220 -275 135 -465 271 -662 471 -123 125 -184 200 -275 339 -176 267 -295 590 -339 920 -19 136 -16 468 4 611 34 228 102 461 199 674 76 168 81 183 81 272 1 74 -2 88 -36 156 -61 124 -184 219 -346 267 -61 19 -89 22 -167 18 -83 -5 -104 -10 -167 -41 l-73 -36 -53 -108 c-168 -339 -283 -751 -332 -1183 -18 -157 -15 -547 5 -715 72 -596 293 -1150 626 -1570 134 -168 402 -426 596 -573 1191 -899 3083 -1045 4484 -347 379 189 666 398 950 692 516 532 824 1165 921 1891 25 186 30 563 10 757 -85 825 -410 1503 -992 2070 -283 275 -575 482 -940 664 -588 294 -1153 422 -1674 380z"/> <path d="M1444 6786 c-90 -21 -161 -61 -229 -130 -182 -182 -187 -463 -12 -652 251 -272 708 -157 802 201 75 288 -131 571 -428 590 -43 3 -97 -1 -133 -9z"/>
                </g>
            </svg>
            {l s='Login with Stan' mod='stanconnect'}
        </a>
        <span class="tooltiptext">{l s='Login in 1 click with Stan' mod='stanconnect'}</span>
    </div>
</div>