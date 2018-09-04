<?php
/**
 * 2007-2015 PrestaShop
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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2015 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__).'/vendor/autoload.php');

class MauticRecombee extends Module
{

    CONST MAUTICRECOMBEE_CLIENT_KEY = 'MAUTICRECOMBEE_CLIENT_KEY', MAUTICRECOMBEE_CLIENT_SECRET = 'MAUTICRECOMBEE_CLIENT_SECRET', MAUTICRECOMBEE_URL = 'MAUTICRECOMBEE_URL', MAUTICRECOMBEE_TRACKING_CODE = 'MAUTICRECOMBEE_TRACKING_CODE';

    public $fields = [
        self::MAUTICRECOMBEE_URL,
        self::MAUTICRECOMBEE_CLIENT_SECRET,
        self::MAUTICRECOMBEE_CLIENT_KEY,
        self::MAUTICRECOMBEE_TRACKING_CODE,
    ];

    public function __construct()
    {
        $this->name          = 'mauticrecombee';
        $this->tab           = 'front_office_features';
        $this->version       = '1.0.0';
        $this->author        = 'mtcrecombee.com';
        $this->need_instance = 0;
        $this->bootstrap     = true;
        $this->module_key    = '4d10333ceab8a1c5c0901a143144519b';

        parent::__construct();

        $this->displayName = $this->l('Mautic Recoombee integration for Prestashop');
        $this->description = $this->l('Integration Mautic Recoombee to Prestashop');
    }

    public function install()
    {
        if (_PS_VERSION_ >= 1.7) {
            $hookFooter = 'displayBeforeBodyClosingTag';
        } else {
            $hookFooter = 'footer';
        }

        return parent::install() &&
        $this->registerHook('actionCustomerAccountAdd') &&
        $this->registerHook('productfooter') &&
        $this->registerHook('actionCartSave') &&
        $this->registerHook('displayHeader') &&
        $this->registerHook('orderConfirmation') &&
        $this->registerHook('authentication') &&
        $this->registerHook($hookFooter);
    }

    public function uninstall()
    {
        foreach ($this->getFields() as $field) {
            Configuration::deleteByName($field);
        }

        return parent::uninstall();
    }

    private function validateAccess()
    {
        $api = new \Mautic\MauticApi();
        try {

            $segmentsApi = $api->newApi('segments', $this->doAuth(), $this->getApiUrl());
            $segments    = $segmentsApi->getList();
            if (isset($segments['error']) && isset($segments['error']['code']) && $segments['error']['code'] == 403) {
                $output = $this->displayError($this->l('Your API credits are not valid.'));
            } else {
                $output = $this->displayConfirmation($this->l('Your API credits are  valid.'));
            }

            return $output;
        } catch (Exception $e) {
        }
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (((bool) Tools::isSubmit('submitMAUTICRECOMBEEModule')) == true) {
            $this->postProcess();
        }

        $output = $this->validateAccess();

        return $output.$this->renderForm();
    }

    protected function renderForm()
    {
        $helper                           = new HelperForm();
        $helper->show_toolbar             = false;
        $helper->table                    = $this->table;
        $helper->module                   = $this;
        $helper->default_form_language    = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier    = $this->identifier;
        $helper->submit_action = 'submitMAUTICRECOMBEEModule';
        $helper->currentIndex  = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token         = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];


        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $ret = [];
        foreach ($this->getFields() as $field) {
            $ret[$field] = Configuration::get($field);
        }

        return $ret;
    }


    protected function getConfigForm()
    {

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'label' => $this->l('Mautic Url (without slash)'),
                        'name'  => self::MAUTICRECOMBEE_URL,
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Username'),
                        'name'  => self::MAUTICRECOMBEE_CLIENT_KEY,
                    ],
                    [
                        'type'  => 'password',
                        'label' => $this->l('Password'),
                        'name'  => self::MAUTICRECOMBEE_CLIENT_SECRET,
                    ],
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Add Mautic Tracking pixel to website'),
                        'name'   => self::MAUTICRECOMBEE_TRACKING_CODE,
                        'values' => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        return $form;
    }


    protected function postProcess()
    {
        foreach ($this->getFields() as $field) {
            if (!Tools::getValue($field) && $field == self::MAUTICRECOMBEE_CLIENT_SECRET) {
                continue;
            }
            Configuration::updateValue($field, Tools::getValue($field));
        }
    }

    /**
     * @return string
     */
    private function getApiUrl()
    {
        return Configuration::get('MAUTICRECOMBEE_URL').'/api/';
    }

    /**
     * @return mixed
     */
    private function getUrl()
    {
        return Configuration::get('MAUTICRECOMBEE_URL');
    }

    public function doAuth()
    {
        $settings = [
            'AuthMethod' => 'BasicAuth',
            'userName'   => Configuration::get('MAUTICRECOMBEE_CLIENT_KEY'),
            'password'   => Configuration::get('MAUTICRECOMBEE_CLIENT_SECRET'),
            'apiUrl'     => $this->getApiUrl(),
        ];
        $initAuth = new \Mautic\Auth\ApiAuth();

        return $initAuth->newAuth($settings, $settings['AuthMethod']);
    }


    public function hookDisplayHeader($params)
    {
        if (Tools::getValue('controller') == 'order' && !Tools::getValue('step') && Tools::getValue('set_cart')) {
            $cart = Context::getContext()->cart;
            $products = $cart->getProducts();
            foreach ($products as $product) {
                $cart->deleteProduct($product["id_product"], $product["id_product_attribute"]);
            }
            $cartItems = explode(',',Tools::getValue('set_cart'));
            foreach ($cartItems as $cartItem) {
                $iPa = null;
                if (count(explode('-', $cartItem)) == 2) {
                    list($idProduct, $iPa) = explode('-', $cartItem);
                }else{
                    $idProduct = $cartItem;
                }
                $cart->updateQty(1, $idProduct, $iPa);
            }
            $currentUrl = Tools::getCurrentUrlProtocolPrefix().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            Tools::redirect(str_replace('set_cart','filled_cart', $currentUrl));
        }
    }

    public function hookActionAuthentication($params)
    {
        $this->mapCustomerAndAddress($this->context->customer);
    }

    public function hookActionCustomerAccountAdd($params)
    {
        $this->mapCustomerAndAddress($params['newCustomer']);
    }

    public function hookActionCartSave($params)
    {

        $cart     = $this->context->cart;
        $customer = $this->context->customer;
        $leadId   = $this->getLeadId();
        if ($leadId) {
            if (Validate::isLoadedObject($cart)) {

                $cart_products = $cart->getProducts();
                if (isset($cart_products) && count($cart_products)) {
                    foreach ($cart_products as $cart_product) {
                        if ($cart_product['id_product'] == Tools::getValue('id_product')) {
                            $add_product = $cart_product;
                        }
                    }
                }

                if (Tools::getValue('ipa')) {
                    $productId = Tools::getValue('id_product').'-'.Tools::getValue('ipa');
                } else {
                    $productId = Tools::getValue('id_product');
                }

                $options = [
                    'itemId' => $productId,
                ];
                if (Tools::getValue('delete')) {
                    $this->doApi('DeleteCartAddition', $options);
                } elseif (Tools::getValue('add') && !Tools::getValue('op')) {
                    $options = array_merge(
                        $options,
                        [
                            'amount' => (int) Tools::getValue('qty', 1),
                            'price'  => $add_product['price'],
                        ]
                    );
                    $this->doApi('AddCartAddition', $options);
                }

                $this->mapCustomerAndAddress(
                    $customer,
                    [
                        'id_cart'      => $cart->id,
                        'cart_content' => $this->returnTextfromArray($cart->getProducts()),
                    ]
                );
            }
        }
    }

    /**
     * @param $params
     */
    public function hookOrderConfirmation($params)
    {
        if (isset($params['objOrder'])) {
            $order = $params['objOrder'];
        } else {
            $order = $params['order'];
        }
        if (Validate::isLoadedObject($order) && $order->getCurrentState() != (int) Configuration::get('PS_OS_ERROR')) {
            $cartProducts = $order->getProducts();
            if (count($cartProducts)) {
                foreach ($cartProducts as $cartProduct) {
                    if (!empty($cartProduct['product_attribute_id'])) {
                        $productId = $cartProduct['id_product'].'-'.$cartProduct['product_attribute_id'];
                    } else {
                        $productId = $cartProduct['id_product'];
                    }
                    $options = [
                        'itemId' => $productId,
                        'amount' => $cartProduct['product_quantity'],
                        'price'  => $cartProduct['total_price'],
                    ];
                    $this->doApi('AddPurchase', $options);
                }
            }
            $this->mapCustomerAndAddress(Context::getContext()->customer, ['id_order' => $order->id]);
        }
    }

    /**
     * @param       $component
     * @param array $options
     */
    private function doApi($component, $options = [])
    {
        //$options['userId'] = 1;
        if (!isset($options['userId']) && !isset($_COOKIE['mtc_id'])) {
            return;
        }
        if (!isset($options['userId'])) {
            $options['userId'] = $_COOKIE['mtc_id'];
        }
        try {
            $api        = new \Mautic\MauticApi();
            $apiRequest = $api->newApi('api', $this->doAuth(), $this->getApiUrl());
            $response   = $apiRequest->makeRequest('recombee/'.$component, $options, 'POST');
        } catch (Exception $exception) {
            // $exception->getMessage();
        }
    }


    public function hookDisplayFooter()
    {
        return $this->getTrackingCode();
    }

    public function hookDisplayBeforeBodyClosingTag()
    {
        return $this->getTrackingCode();
    }

    public function mapFromArray($array, $anotherData = [])
    {
        $leadId = $this->getLeadId();
        if ($leadId) {
            $data          = $anotherData;
            $auth          = $this->doAuth();
            $api           = new \Mautic\MauticApi();
            $contactApi    = $api->newApi('contacts', $auth, $this->getApiUrl());
            $fieldsMapping = $this->getFieldsMapping();
            foreach ($array as $prestashopAlias => $value) {
                if (isset($fieldsMapping[$prestashopAlias])) {
                    $data[$fieldsMapping[$prestashopAlias]] = $value;
                }
            }
            if (!empty($data)) {
                $contactApi->edit($leadId, $data);
            }
        }
    }

    private function getFieldsMapping()
    {
        $fields['firstname']    = 'firstname';
        $fields['lastname']     = 'lastname';
        $fields['email']        = 'email';
        $fields['address1']     = 'address1';
        $fields['address2']     = 'address2';
        $fields['postcode']     = 'zipcode';
        $fields['city']         = 'city';
        $fields['phone']        = 'phone';
        $fields['phone_mobile'] = 'mobile';
        $fields['vat_number']   = 'vat';
        $fields['dni']          = 'dni';
        $fields['id_gender']    = 'id_gender';
        $fields['newsletter']   = 'newsletter';
        $fields['optin']        = 'optin';
        $fields['website']      = 'website';
        $fields['id_order']     = 'id_order';
        $fields['id_cart']      = 'id_cart';
        $fields['cart_content'] = 'cart_content';

        return $fields;
    }

    private function returnTextfromArray(array $products)
    {
        $content = '';
        foreach ($products as $product) {
            foreach ($product as $key => $value) {
                if (!is_array($value)) {
                    $content .= $key.': '.$value."\n";
                }
            }
        }

        return $content;
    }


    public function getLeadId()
    {
        if (isset($_COOKIE['mtc_id'])) {
            return $_COOKIE['mtc_id'];
        }
    }

    /**
     * @param $object
     *
     * @return array
     */
    private function objectToArray($object)
    {
        if (is_object($object)) {
            return Tools::jsonDecode(Tools::jsonEncode(($object)), true);
        } else {
            return [];
        }
    }

    public function hookProductFooter($params)
    {
        //$options = ['itemId' => (int) $params['product']->id];
        // $this->doApi('AddDetailView', $options);

    }


    private function mapCustomerAndAddress($customer, $data = [])
    {
        if (ValidateCore::isLoadedObject($customer)) {
            $customerArray = $this->objectToArray($customer);
            $addresses     = $customer->getAddresses(Context::getContext()->language->id);
            if (!empty($addresses)) {
                $this->mapFromArray(array_merge($customerArray, end($addresses)), $data);
            } else {
                $this->mapFromArray($customerArray, $data);

            }
        }
    }

    /**
     * @return string
     */
    public function getTrackingCode()
    {
        if (Configuration::get(self::MAUTICRECOMBEE_TRACKING_CODE)) {
            return "<script>
    (function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
        w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
        m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)
    })(window,document,'script','".$this->getUrl()."/mtc.js','mt');
    mt('send', 'pageview');
</script>";

        }

    }


    private function getFields()
    {
        return $this->fields;
    }

}
