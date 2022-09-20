<?php
/**
* 2007-2020 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use Epayco_agregadorOrder as Epayco_agregadorOrder;

include(_PS_MODULE_DIR_ . 'epayco_agregador/lib/EpaycoOrder.php');
include(_PS_MODULE_DIR_ . 'epayco_agregador/lib/CreditCard_Order.php');
include(_PS_MODULE_DIR_ . 'epayco_agregador/lib/CreditCard_OrderState.php');

class Epayco_agregador extends PaymentModule
{
    protected $config_form = false;
    private $_html = '';
    private $_postErrors = array();
    public $orderStates;
    public $p_cust_id_cliente_agregador;
    public $p_key_agregador;
    public $public_key_agregador;
    public $p_test_request_agregador;
    public $p_type_checkout_agregador;
    public $p_url_response_agregador;
    public $p_url_confirmation_agregador;
    public $p_state_end_transaction_agregador;

    public function __construct()
    {
        $this->name = 'epayco_agregador';
        $this->tab = 'payments_gateways';
        $this->version = '1.7.7';
        $this->author = 'Ricardo saldarriaga';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('epayco agregador');
        $this->description = $this->l('ePayco, Tarjetas de Credito, Debito PSE, SafetyPay y Efectivo');

        $this->confirmUninstall = $this->l('Esta seguro de desistalar este modulo?');

        $this->limited_countries = array('FR','CO','USA','EUR','US');


        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $config = Configuration::getMultiple(array('P_CUST_ID_CLIENTE_agregador',
                                                'P_KEY_agregador','PUBLIC_KEY_agregador',
                                                'P_TEST_REQUEST_agregador',
                                                'P_TITULO_agregador',
                                                'P_URL_RESPONSE_agregador',
                                                'P_TYPE_CHECKOUT_agregador',
                                                'P_REDUCE_STOCK_PENDING',
                                                'P_URL_CONFIRMATION_agregador',
                                                'P_STATE_END_TRANSACTION_agregador'));

        if (isset($config['P_CUST_ID_CLIENTE_agregador']))
            $this->p_cust_id_cliente_agregador = trim($config['P_CUST_ID_CLIENTE_agregador']);
        if (isset($config['P_KEY_agregador']))
            $this->p_key_agregador = trim($config['P_KEY_agregador']);
        if (isset($config['PUBLIC_KEY_agregador']))
            $this->public_key_agregador = trim($config['PUBLIC_KEY_agregador']);  
        if (isset($config['P_TEST_REQUEST_agregador']))
            $this->p_test_request_agregador = $config['P_TEST_REQUEST_agregador'];
        if (isset($config['P_TITULO_agregador']))
            $this->p_titulo_agregador = trim($config['P_TITULO_agregador']);
        if (isset($config['P_URL_RESPONSE_agregador']))
            $this->p_url_response_agregador = trim($config['P_URL_RESPONSE_agregador']);
        if (isset($config['P_URL_CONFIRMATION_agregador']))
            $this->p_url_confirmation_agregador = trim($config['P_URL_CONFIRMATION_agregador']);  
        if (isset($config['P_TYPE_CHECKOUT_agregador']))
            $this->p_type_checkout_agregador = $config['P_TYPE_CHECKOUT_agregador'];
         if (isset($config['P_REDUCE_STOCK_PENDING']))
            $this->p_reduce_stock_pending = $config['P_REDUCE_STOCK_PENDING'];
        if (isset($config['P_STATE_END_TRANSACTION_agregador'])){
            $this->p_state_end_transaction_agregador = $config['P_STATE_END_TRANSACTION_agregador'];
        }else{
            $this->p_state_end_transaction_agregador = (int)Configuration::get('PS_OS_PAYMENT');
        }
        if (!isset($this->p_cust_id_cliente_agregador) OR !isset($this->p_key_agregador) OR !isset($this->public_key_agregador))
        $this->warning = $this->l('P_CUST_ID_CLIENTE_agregador, P_KEY_agregador y PUBLIC_KEY_agregador deben estar configurados para utilizar este módulo correctamente');
        if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
        $this->warning = $this->l('No currency set for this module');
    }


    /**
     * @return void
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->registerJavascript('epayco-agregador-checkout','https://checkout.epayco.co/checkout.js', ['position' => 'bottom', 'priority' => 150]);
        $this->context->controller->registerStylesheet(
            'epayco-agregador-checkout-css',$this->getPathUri() .'views/css/back.css',['media' => 'all', 'priority' => 150]
        );
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false)
        {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if (in_array($iso_code, $this->limited_countries) == false)
        {
            $this->_errors[] = $this->l('This module is not available in your country');
            return false;
        }

        Configuration::updateValue('P_TITULO_agregador', 'Checkout ePayco, (Tarjetas de crédito,debito,efectivo.)');
        Configuration::updateValue('P_CUST_ID_CLIENTE_agregador', '');
        Configuration::updateValue('P_KEY_agregador', '');
        Configuration::updateValue('PUBLIC_KEY_agregador', '');
        Configuration::updateValue('P_TEST_REQUEST_agregador', false);
        Configuration::updateValue('P_REDUCE_STOCK_PENDING', true);  
        Configuration::updateValue('P_URL_RESPONSE_agregador', Context::getContext()->link->getModuleLink('epayco_agregador', 'response'));
        Configuration::updateValue('P_URL_CONFIRMATION_agregador', Context::getContext()->link->getModuleLink('epayco_agregador', 'response'));
        Configuration::updateValue('P_STATE_END_TRANSACTION_agregador', 'PS_OS_PAYMENT');
        CreditCard_OrderStates::remove();
        CreditCard_OrderStates::setup();
        //CreditCard_Issuer::setup();
        CreditCard_Orders::setup();

        //crear la tabla para administrar los pedidos
        Epayco_agregadorOrder::remove();
        Epayco_agregadorOrder::setup();
        Configuration::updateValue('epayco_agregador', true);



        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions');
    }

    public function uninstall()
    {
        CreditCard_Orders::remove();
        CreditCard_OrderStates::remove();
        Configuration::deleteByName('PAYCO_LIVE_MODE');
        Configuration::deleteByName('P_TITULO_agregador');
        Configuration::deleteByName('P_CUST_ID_CLIENTE_agregador');
        Configuration::deleteByName('P_KEY_agregador');
        Configuration::deleteByName('PUBLIC_KEY_agregador');
        Configuration::deleteByName('P_TEST_REQUEST_agregador');
        Configuration::deleteByName('P_URL_RESPONSE_agregador');
        Configuration::deleteByName('P_URL_CONFIRMATION_agregador');
        Configuration::deleteByName('P_TYPE_CHECKOUT_agregador');
        Configuration::deleteByName('P_REDUCE_STOCK_PENDING');
        Configuration::deleteByName('P_STATE_END_TRANSACTION_agregador');

        Configuration::deleteByName('epayco_agregador', false);

      Epayco_agregadorOrder::remove();
        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    protected function _displayInfoAdmin()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitEpayco_agregadorModule')) == true) {
            $this->postValidation();
              if (!count($this->_postErrors)) {
                $this->postProcess();
            }else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
           
        }else {

            $this->_html .= '<br />';
        }
        $this->_html .= $this->_displayInfoAdmin();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEpayco_agregadorModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        
      $states = CreditCard_OrderStates::getOrderStates();
      $order_states=array();
      
      foreach($states as $state){
        $order_states[]=array("id"=>$state["id_order_state"],"name"=>$state["name"]);
      }

      $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Configuración ePayco', array(), 'Modules.Epayco_agregador.Admin'),
                    'icon' => 'icon-envelope'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label'=> $this->trans('Titulo', array(), 'Modules.Epayco_agregador.Admin'),
                        'name' => 'P_TITULO_agregador',
                        'required' => true,
                        'desc' => $this->trans('Titulo que el usuario vera durante el Checkout del Plugin', array(), 'Modules.Epayco_agregador.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('P_CUST_ID_CLIENTE', array(), 'Modules.Epayco_agregador.Admin'),
                        'name' => 'P_CUST_ID_CLIENTE_agregador',
                        'desc' => $this->trans('Id del cliente que lo identifica en ePayco.', array(), 'Modules.Epayco_agregador.Admin'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('P_KEY', array(), 'Modules.Epayco_agregador.Admin'),
                        'name' => 'P_KEY_agregador',
                        'desc' => $this->trans('Llave para firmar la información enviada y recibida de ePayco', array(), 'Modules.Epayco_agregador.Admin'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('PUBLIC_KEY', array(), 'Modules.Epayco_agregador.Admin'),
                        'name' => 'PUBLIC_KEY_agregador',
                        'desc' => $this->trans('LLave para autenticar y consumir los servicios de ePayco.', array(), 'Modules.Epayco_agregador.Admin'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Página de Respuesta', array(), 'Modules.Epayco_agregador.Admin'),
                        'name' => 'P_URL_RESPONSE_agregador',
                        'placeholder'=>"http://tutienda.com/respuesta",
                        'desc' => $this->trans('Url de la tienda mostrada luego de finalizar el pago.', array(), 'Modules.Epayco_agregador.Admin'),
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Página de Confirmación', array(), 'Modules.Epayco_agregador.Admin'),
                        'name' => 'P_URL_CONFIRMATION_agregador',
                        'placeholder'=>"http://tutienda.com/confirmacion",
                        'desc' => $this->trans('Url de Confirmación donde ePayco confirma el pago.', array(), 'Modules.Epayco_agregador.Admin'),
                        'required' => true
                    ),
                    array(
                        'type' => 'radio',
                        'label'=> $this->trans('Habilitar modo pruebas', array(), 'Modules.Payment.Admin'),
                        'name' => "P_TEST_REQUEST_agregador",
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'P_TEST_REQUEST_agregador_TRUE',
                                'value' => true,
                                'label' => $this->trans('Si (Transacciones en pruebas)', array(), 'Modules.Payment.Admin'),
                            ),
                            array(
                                'id' => 'P_TEST_REQUEST_agregador_FALSE',
                                'value' => false,
                                'label' => $this->trans('No (Transacciones en producción)', array(), 'Modules.Payment.Admin'),
                            )
                        ),
                    ),
                    array(
                        'type' => 'radio',
                        'label'=> $this->trans('Tipo de checkout ePayco', array(), 'Modules.Epayco_agregador.Admin'),
                        'name' => "P_TYPE_CHECKOUT_agregador",
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'onpage',
                                'value' => true,
                                'label' => $this->trans('OnPage Checkout (El usuario al pagar se queda en la tienda no hay redirección a ePayco)', array(), 'Modules.Epayco_agregador.Admin'),
                            ),
                            array(
                                'id' => 'standart',
                                'value' => false,
                                'label' => $this->trans('Estandar Checkout (El usuario al pagar es redireccionado a la pasarela de ePayco)', array(), 'Modules.Epayco_agregador.Admin'),
                            )
                        ),
                    ),
                    array(
                        'type' => 'radio',
                        'label'=> $this->trans('Reducir el stock en transacciones pendientes', array(), 'Modules.Epayco_agregador.Admin'),
                        'name' => "P_REDUCE_STOCK_PENDING",
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'P_REDUCE_STOCK_PENDING_TRUE',
                                'value' => true,
                                'label' => $this->trans('Si', array(), 'Modules.Payment.Admin'),
                            ),
                            array(
                                'id' => 'P_REDUCE_STOCK_PENDING_FALSE',
                                'value' => false,
                                'label' => $this->trans('No', array(), 'Modules.Payment.Admin'),
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->trans('Estado final Pedido', array(), 'Modules.Epayco_agregador.Admin'),
                        'name' => 'P_STATE_END_TRANSACTION_agregador',
                        'desc' => $this->trans('Escoja el estado del pago que se aplicar al confirmar la trasacción.', array(), 'Modules.Epayco_agregador.Admin'),
                        'required' => true,
                        'options' => array(
                              'id' => 'id',
                              'name' => 'name',
                              'default' => array(
                                  'value' => '',
                                  'label' => $this->l('Seleccione un estado de Orden')
                              ),
                              'query'=>$order_states,
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                )
            ),
        );

        return $fields_form;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'P_TITULO_agregador' => Tools::getValue('P_TITULO_agregador', Configuration::get('P_TITULO_agregador')),
            'P_CUST_ID_CLIENTE_agregador' => Tools::getValue('P_CUST_ID_CLIENTE_agregador', Configuration::get('P_CUST_ID_CLIENTE_agregador')),
            'P_KEY_agregador' => Tools::getValue('P_KEY_agregador', Configuration::get('P_KEY_agregador')),
            'PUBLIC_KEY_agregador' => Tools::getValue('PUBLIC_KEY_agregador', Configuration::get('PUBLIC_KEY_agregador')),
            'P_TEST_REQUEST_agregador' => Tools::getValue('P_TEST_REQUEST_agregador', Configuration::get('P_TEST_REQUEST_agregador')),
            'P_TYPE_CHECKOUT_agregador' => Tools::getValue('P_TYPE_CHECKOUT_agregador', Configuration::get('P_TYPE_CHECKOUT_agregador')),
            'P_URL_RESPONSE_agregador' => Tools::getValue('P_URL_RESPONSE_agregador', Configuration::get('P_URL_RESPONSE_agregador')),
            'P_URL_CONFIRMATION_agregador' => Tools::getValue('P_URL_CONFIRMATION_agregador', Configuration::get('P_URL_CONFIRMATION_agregador')),
            'P_STATE_END_TRANSACTION_agregador'=>Tools::getValue('P_STATE_END_TRANSACTION_agregador', Configuration::get('P_STATE_END_TRANSACTION_agregador')),
            'P_REDUCE_STOCK_PENDING' => Tools::getValue('P_REDUCE_STOCK_PENDING', Configuration::get('P_REDUCE_STOCK_PENDING'))
        );
    }

      private function postValidation() {
      if (Tools::isSubmit('submitEpayco_agregadorModule')) {
        if (!Tools::getValue('P_CUST_ID_CLIENTE_agregador'))
          $this->_postErrors[] = $this->l('\'P_CUST_ID_CLIENTE_agregador\' Campo Requerido.');
        if (!Tools::getValue('P_KEY_agregador'))
          $this->_postErrors[] = $this->l('\'P_KEY_agregador\' Campo Requerido.');
        if (!Tools::getValue('PUBLIC_KEY_agregador'))
          $this->_postErrors[] = $this->l('\'PUBLIC_KEY_agregador\' Campo Requerido.');
        if (!Tools::getValue('P_STATE_END_TRANSACTION_agregador'))
          $this->_postErrors[] = $this->l('\'Estado final Pedido\' Campo Requerido.');
        
      }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('submitEpayco_agregadorModule')) {

            if(Tools::getValue('P_URL_RESPONSE_agregador')=="")
            {
              $p_url_response_agregador=Context::getContext()->link->getModuleLink('epayco_agregador', 'response');
            }else{
               $p_url_response_agregador=Tools::getValue('P_URL_RESPONSE_agregador');
            }
            if(Tools::getValue('P_URL_CONFIRMATION_agregador')=="")
            {
              $p_url_confirmation_agregador=Context::getContext()->link->getModuleLink('epayco_agregador', 'confirmation');
            }else{
               $p_url_confirmation_agregador=Tools::getValue('P_URL_CONFIRMATION_agregador');
            }
            if(Tools::getValue('P_TITULO_agregador')==""){
               $p_titulo_agregador="Checkout ePayco, Tarjetas de Crédito, Débito y  Efectivo";
            }else{
              $p_titulo_agregador=Tools::getValue('P_TITULO_agregador');
            }

            Configuration::updateValue('P_CUST_ID_CLIENTE_agregador', Tools::getValue('P_CUST_ID_CLIENTE_agregador'));
            Configuration::updateValue('P_KEY_agregador', Tools::getValue('P_KEY_agregador'));
            Configuration::updateValue('PUBLIC_KEY_agregador', Tools::getValue('PUBLIC_KEY_agregador'));
            Configuration::updateValue('P_TEST_REQUEST_agregador', Tools::getValue('P_TEST_REQUEST_agregador'));
            Configuration::updateValue('P_TITULO_agregador', $p_titulo_agregador);
            Configuration::updateValue('P_URL_RESPONSE_agregador', $p_url_response_agregador);
            Configuration::updateValue('P_URL_CONFIRMATION_agregador', $p_url_confirmation_agregador);
            Configuration::updateValue('P_TYPE_CHECKOUT_agregador', Tools::getValue('P_TYPE_CHECKOUT_agregador'));
            Configuration::updateValue('P_REDUCE_STOCK_PENDING', Tools::getValue('P_REDUCE_STOCK_PENDING'));
            Configuration::updateValue('P_STATE_END_TRANSACTION_agregador', Tools::getValue('P_STATE_END_TRANSACTION_agregador'));
            $this->_html.= '<div class="bootstrap"><div class="alert alert-success">'.$this->l('Cambios Aplicados Exitosamente') . '</div></div>'; 
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);

        if (in_array($currency->iso_code, $this->limited_currencies) == false)
            return false;

        $this->smarty->assign('module_dir', $this->_path);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false)
            return;

        if (version_compare(_PS_VERSION_, '1.7.0.0 ', '<')){
            $order = $params['objOrder'];
            $value = $params['total_to_pay'];
            $currence = $params['currencyObj'];
        }else{
            $order = $params['order'];
            $value = $params['order']->getOrdersTotalPaid();
            $currence = new Currency($params['order']->id_currency);
        }

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR'))
            $this->smarty->assign('status', 'ok');

          $extra1 = $order->id_cart;
          $extra2 = $order->id;
          $emailComprador = $this->context->customer->email;
          $valorBaseDevolucion = $order->total_paid_tax_excl;
          $iva = $value - $valorBaseDevolucion;
          $cart= $this->context->cart;

          /*
          Para determinar la ubicación o por default CO
          */
          $iso = 'CO';
          if ($iva == 0) $valorBaseDevolucion = 0;

          $currency = $this->getCurrency();
          $idcurrency = $order->id_currency;
          foreach ($currency as $mon) {
            if ($idcurrency == $mon['id_currency']) $currency = $mon['iso_code'];
          }

          //si no existe la moneda
          if ($currency == ''){
            $currency = 'COP';
          }

          $refVenta = $order->reference;
          $state = $order->getCurrentState();

          if ($state) {
                $p_signature = md5(trim($this->p_cust_id_cliente_agregador).'^'.trim($this->p_key_agregador).'^'.$refVenta.'^'.$value.'^'.$currency);
                $addressdelivery = new Address((int)($cart->id_address_delivery));
            if($this->p_test_request_agregador==1){
              $test="true";
            }else{
              $test="false";
            }

            if($this->p_type_checkout_agregador==1){
              $external="false";
            }else{
              $external="true";
            }

            $valueNodecimal = substr($value, 0,strrpos($value, '.'));
            $decimals = !substr($value, strrpos($value, '.')+1 ,2) ? '00': substr($value, strrpos($value, '.')+1 ,2);
            //definir la url de respuesta y confirmacion segun la defina el usuario
            $p_url_response_agregador=Context::getContext()->link->getModuleLink('epayco_agregador', 'response');
            $p_url_confirmation_agregador=Context::getContext()->link->getModuleLink('epayco_agregador', 'confirmation');

            $lang = $this->context->language->language_code;
            if($lang == "es"){
                 $url_button = "https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color1.png";
            }else{
                $url_button = "https://multimedia.epayco.co/epayco-landing/btns/Boton-epayco-color-Ingles.png";
                $lang = "en";
            }
            $this->smarty->assign(array(
                'this_path_bw' => $this->_path,
                'p_signature' => $p_signature,
                'total_to_pay' => Tools::displayPrice($value, $currence, false),
                'status' => 'ok',
                'refVenta' => $refVenta,
                'custemail' => $emailComprador,
                'extra1' => $extra1,
                'extra2' => $extra2,
                'total' => $valueNodecimal.'.'.$decimals,
                'currency' => $currency,
                'iso' => $iso,
                'iva' => $iva,
                'baseDevolucionIva' => $valorBaseDevolucion,
                'merchantid' => trim($this->p_cust_id_cliente_agregador),
                'external'=>$external,
                'merchantpassword' => trim($this->p_key_agregador),
                'merchanttest'=> $test,
                'p_key_agregador'=>trim($this->p_key_agregador),
                'public_key_agregador'=>trim($this->public_key_agregador),
                'custip' => $_SERVER['REMOTE_ADDR'],
                'custname' => $this->context->customer->firstname." ".$this->context->customer->lastname,
                'p_url_response_agregador' => $p_url_response_agregador,
                'p_url_confirmation_agregador' => $p_url_confirmation_agregador,
                'p_billing_email' => $this->context->customer->email,
                'p_billing_name' => $this->context->customer->firstname,
                'p_billing_last_name' => $this->context->customer->lastname,
                'p_billing_address'=>$addressdelivery->address1 . " " . $addressdelivery->address2,
                'p_billing_city'=>$addressdelivery->city,
                'p_billing_country'=>$addressdelivery->id_state,
                'p_billing_phone'=>"",
                'url_button'=>$url_button,
                'lang'=>$lang
                )
            );
        } else {
            $this->smarty->assign('status', 'failed');
        }
        $this->context->controller->addCSS($this->_path.'/views/css/back.css');
        return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
       $this->context->smarty->assign(array("titulo"=>$this->p_titulo_agregador));
        
        $modalOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $modalOption->setCallToActionText($this->l(''))
                      ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                      ->setAdditionalInformation($this->context->smarty->fetch('module:epayco_agregador/views/templates/hook/payment_onpage.tpl'))
                      ->setLogo("https://multimedia.epayco.co/epayco-landing/btns/epayco-logo-fondo-oscuro-lite.png");
        $payment_options = [
           $modalOption,
        ];

        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    
    public function hookDisplayPayment()
    {
        /* Place your code here. */
    }

    public function hookDisplayPaymentReturn($params)
    {
        if ($this->active == false)
            return;

        if (version_compare(_PS_VERSION_, '1.7.0.0 ', '<')){
            $order = $params['objOrder'];
            $value = $params['total_to_pay'];
            $currence = $params['currencyObj'];
        }else{
            $order = $params['order'];
            $value = $params['order']->getOrdersTotalPaid();
            $currence = new Currency($params['order']->id_currency);
        }

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')){
             $this->smarty->assign('status', 'ok');
        }
        
        $extra1 = $order->id_cart;
        $extra2 = $order->id;
        $emailComprador = $this->context->customer->email;
        $valorBaseDevolucion = $order->total_paid_tax_excl;
        $iva = $value - $valorBaseDevolucion;
        $cart= $this->context->cart;

        /*
        Para determinar la ubicación o por default CO
        */
        $iso = 'CO';
        if ($iva == 0) $valorBaseDevolucion = 0;

        $currency = $this->getCurrency();
        $idcurrency = $order->id_currency;
        foreach ($currency as $mon) {
            if ($idcurrency == $mon['id_currency']) $currency = $mon['iso_code'];
        }

        //si no existe la moneda
        if ($currency == ''){
            $currency = 'COP';
        }

        $refVenta = $order->reference;
        $state = $order->getCurrentState();

        if ($state) {
            $p_signature = md5(trim($this->p_cust_id_cliente_agregador).'^'.trim($this->p_key_agregador).'^'.$refVenta.'^'.$value.'^'.$currency);
            $addressdelivery = new Address((int)($cart->id_address_delivery));
        
            if($this->p_test_request_agregador==1){
                $test="true";
            }else{
                $test="false";
            }

            if($this->p_type_checkout_agregador==1){
                $external="false";
            }else{
                $external="true";
            }
           

            $valueNodecimal = substr($value, 0,strrpos($value, '.'));
            $decimals = !substr($value, strrpos($value, '.')+1 ,2) ? '00': substr($value, strrpos($value, '.')+1 ,2);
            //definir la url de respuesta y confirmacion segun la defina el usuario
            $p_url_response_agregador=Context::getContext()->link->getModuleLink('epayco_agregador', 'response');
            $p_url_confirmation_agregador=Context::getContext()->link->getModuleLink('epayco_agregador', 'confirmation');

            $this->smarty->assign(array(
                'this_path_bw' => $this->_path,
                'p_signature' => $p_signature,
                'total_to_pay' => Tools::displayPrice($value, $currence, false),
                'status' => 'ok',
                'refVenta' => $refVenta,
                'custemail' => $emailComprador,
                'extra1' => $extra1,
                'extra2' => $extra2,
                'total' => $valueNodecimal.'.'.$decimals,
                'currency' => $currency,
                'iso' => $iso,
                'iva' => $iva,
                'baseDevolucionIva' => $valorBaseDevolucion,
                'merchantid' => trim($this->p_cust_id_cliente_agregador),
                'external'=>$external,
                'merchantpassword' => trim($this->p_key_agregador),
                'merchanttest'=> $test,
                'p_key_agregador'=>trim($this->public_key_agregador),
                'public_key_agregador'=>trim($this->public_key_agregador),
                'custip' => $_SERVER['REMOTE_ADDR'],
                'custname' => $this->context->customer->firstname." ".$this->context->customer->lastname,
                'p_url_response_agregador' => $p_url_response_agregador,
                'p_url_confirmation_agregador' => $p_url_confirmation_agregador,
                'p_billing_email' => $this->context->customer->email,
                'p_billing_name' => $this->context->customer->firstname,
                'p_billing_last_name' => $this->context->customer->lastname,
                'p_billing_address'=>$addressdelivery->address1 . " " . $addressdelivery->address2,
                'p_billing_city'=>$addressdelivery->city,
                'p_billing_country'=>$addressdelivery->id_state,
                'p_billing_phone'=>""
                )
            );
        } else {
            $this->smarty->assign('status', 'failed');
        }
        $this->context->controller->addCSS($this->_path.'/views/css/back.css');
        return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    private function is_blank($var) {
        return isset($var) || $var == '0' ? ($var == "" ? true : false) : false;
}

    public function PaymentReturnOnpage(){

        $ref_payco="";
        $url="";
        $confirmation=false;
        $x_ref_payco="";

        foreach ($_REQUEST as $value) {
            if(preg_match("/ref_payco/", $value)){
                $arr_refpayco=explode("=",$value);
                $ref_payco=$arr_refpayco[1];
            }
        }

        if(isset($_REQUEST["?ref_payco"])!="" || isset($_REQUEST["ref_payco"]) || $ref_payco){
            if(isset($_REQUEST["?ref_payco"])){
                $ref_payco=$_REQUEST["?ref_payco"];
            }
            if(isset($_REQUEST["ref_payco"])){
                $ref_payco=$_REQUEST["ref_payco"];
            }
            $url = 'https://secure.epayco.co/validation/v1/reference/'.$ref_payco;
        }
        if($ref_payco!="" and $url!=""){
            //Consultamos la transaccion en el servidor
            $responseData = $this->PostCurl($url,false,$this->StreamContext());
            $jsonData = @json_decode($responseData, true);
            $data = $jsonData['data'];
            $data["ref_payco"]=$ref_payco;
            $data["url"]=$url;
            $this->Acentarpago($data["x_extra1"],$data["x_cod_response"],$data["x_ref_payco"],$data["x_transaction_id"],$data["x_amount"],$data["x_currency_code"],$data["x_signature"],$confirmation,$data["x_test_request"], $data["x_approval_code"],$data["x_franchise"]);
            $this->context->smarty->assign($data);
        }
    }

    public function PaymentSuccess($extra1,$response,$referencia,$transid,$amount,$currency,$signature,$confirmation,$textMode,$x_approval_code,$x_franchise) {
      $this->Acentarpago($extra1,$response,$referencia,$transid,$amount,$currency,$signature,$confirmation,$textMode,$x_approval_code,$x_franchise);
    }

    private function Acentarpago($extra1,$response,$referencia,$transid,$amount,$currency,$signature,$confirmation,$textMode,$x_approval_code,$x_franchise) {

        $config = Configuration::getMultiple(array('P_CUST_ID_CLIENTE_agregador','P_KEY_agregador','PUBLIC_KEY_agregador','P_TEST_REQUEST_agregador','P_STATE_END_TRANSACTION_agregador'));  
        $x_cust_id_cliente=trim($config['P_CUST_ID_CLIENTE_agregador']);
        $x_key=trim($config['P_KEY_agregador']);
        $idorder=$extra1;
        $x_cod_response=(int)$response;
        $x_signature=hash('sha256',
            $x_cust_id_cliente.'^'
            .$x_key.'^'
            .$referencia.'^'
            .$transid.'^'
            .$amount.'^'
            .$currency
        );

        $payment=false;
        if($textMode == "TRUE"){
            $state = 'PAYCO_OS_REJECTED_TEST';
            if ($x_cod_response == 4)
                $state = 'PAYCO_OS_FAILED_TEST';
            else if ($x_cod_response == 2)
                $state = 'PAYCO_OS_REJECTED_TEST';
            else if ($x_cod_response == 3){
                $state = 'PAYCO_OS_PENDING_TEST';
                $statePending = $state;
            }
            else if ($x_cod_response == 9)
                $state = 'PAYCO_OS_EXPIRED_TEST';
            else if ($x_cod_response == 10)
                $state = 'PAYCO_OS_ABANDONED_TEST';
            else if ($x_cod_response == 11)
                $state = 'PAYCO_OS_CANCELED_TEST';
            else if ($x_cod_response == 1){
                $state = 'PS_OS_PAYMENT';
                $payment=true;
            }
        }else{
            $state = 'PAYCO_OS_REJECTED';
            if ($x_cod_response == 4)
                $state = 'PAYCO_OS_FAILED';
            else if ($x_cod_response == 2)
                $state = 'PAYCO_OS_REJECTED';
            else if ($x_cod_response == 3){
                $state = 'PAYCO_OS_PENDING';
                $statePending = $state;
            }
            else if ($x_cod_response == 9)
                $state = 'PAYCO_OS_EXPIRED';
            else if ($x_cod_response == 10)
                $state = 'PAYCO_OS_ABANDONED';
            else if ($x_cod_response == 11)
                $state = 'PAYCO_OS_CANCELED';
            else if ($x_cod_response == 1){
                $state = 'PS_OS_PAYMENT';
                $payment=true;
            }
        }
        
        $order = new Order((int)Order::getOrderByCartId((int)$idorder));
        $keepOn = false;
        if($this->p_test_request_agregador==1){
            $test="yes";
        }else{
            $test="no";
        }
        $isTestTransaction = $textMode == 'TRUE' ? "yes" : "no";
        $orderAmount = floatval($order->getOrdersTotalPaid());
        if($orderAmount == floatval($amount)){
            
            if($isTestTransaction == "yes"){
               $validation = true;  
            }

            if($isTestTransaction == "no" ){
                if($x_approval_code != "000000" && $x_cod_response == 1){
                    $validation = true;
                }else{
                    if($x_cod_response != 1){
                        $validation = true;
                    }else{
                        $validation = false;
                    }
                }            
            }
        }
        
        $orderStatusPre = Db::getInstance()->executeS('
            SELECT name FROM `' . _DB_PREFIX_ . 'order_state_lang`
            WHERE `id_order_state` = ' . (int)$order->current_state);
        $orderStatusPreName = $orderStatusPre[0]['name'];
        
        if($test == "yes")
            {
                if(
                    $orderStatusPreName == "ePayco Pago Rechazado Prueba" ||
                    $orderStatusPreName == "ePayco Pago Cancelado Prueba" ||
                    $orderStatusPreName == "ePayco Pago Abandonado Prueba"||
                    $orderStatusPreName == "ePayco Pago Expirado Prueba"  ||
                    $orderStatusPreName == "ePayco Pago Fallido Prueba"
                ){
                    $validacionOrderName = false;
                }else{
                    $validacionOrderName = true;
                    }
            }else{
                if(
                    $orderStatusPreName == "ePayco Pago Rechazado" ||
                    $orderStatusPreName == "ePayco Pago Cancelado" ||
                    $orderStatusPreName == "ePayco Pago Abandonado"||
                    $orderStatusPreName == "ePayco Pago Expirado"  ||
                    $orderStatusPreName == "ePayco Pago Fallido"
                ){
                    $validacionOrderName = false;
                }else{
                    $validacionOrderName = true;
                }
            }

        if($x_signature==$signature && $validation){
             $current_state = $order->current_state;
             if (!Epayco_agregadorOrder::ifStockDiscount($order->id)) {
                Epayco_agregadorOrder::updateStockDiscount($order->id, 1);
            }
             
            
            if ($current_state != Configuration::get($state))
            {
                if ($confirmation && !$payment && $x_cod_response != 3 && Epayco_agregadorOrder::ifStockDiscount($order->id)) {
                    if(!$validacionOrderName){
                        $this->RestoreStock($order, '+');
                        $history = new OrderHistory();
                        $history->id_order = (int)$order->id;
                        $history->changeIdOrderState((int)Configuration::get($state), $order, true);
                    }
                }                
                
                $history = new OrderHistory();
                $history->id_order = (int)$order->id;
                
                if($payment && $validacionOrderName){               
                    $orderStatus = Db::getInstance()->executeS('
                        SELECT name FROM `' . _DB_PREFIX_ . 'order_state_lang`
                        WHERE `id_order_state` = ' . (int)$config['P_STATE_END_TRANSACTION_agregador']);
                    
                    if($test == $isTestTransaction){
                        $orderStatusName = $textMode == "TRUE" ? $orderStatus[0]['name'] . " Prueba" : $orderStatus[0]['name'];
                        $newOrderName = $orderStatusName;
                        $orderStatusEndId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                            'SELECT * FROM `' . _DB_PREFIX_ . 'order_state_lang` 
                            WHERE `name` = "' . $orderStatusName . '"'
                        );
                        if(!$orderStatusEndId){
                            $orderStatusEndId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                                'SELECT * FROM `' . _DB_PREFIX_ . 'order_state_lang` 
                            WHERE `name` = "' . $orderStatus[0]['name'] . '"'
                            );
                        }
                    }else{
                        $orderStatusName = $orderStatus[0]['name'] . " Prueba";
                        $newOrderName = $orderStatusName;
                        $orderStatusEndId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                            'SELECT * FROM `' . _DB_PREFIX_ . 'order_state_lang` 
                            WHERE `name` = "' . $orderStatusName . '"'
                        );
                        
                        if($orderStatusEndId != $current_state){
                            if($orderStatusPreName != "ePayco Pago Pendiente Prueba"){
                                $this->RestoreStock($order, '+'); 
                            }
                        }
                    }
                  $history->changeIdOrderState((int)$orderStatusEndId, $order, true);
                }else{
                    if (($x_cod_response == 2
                            || $x_cod_response == 4
                            || $x_cod_response == 6
                            || $x_cod_response == 9
                            || $x_cod_response == 10
                            || $x_cod_response == 11
                        ) ) {
                        if ($current_state != Configuration::get($state)) {

                				
                				if($x_franchise == 'PSE'){
                				    $this->RestoreStock($order, '+');
                				}
                				if(trim($x_cod_response) == 10){
                				     $this->RestoreStock($order, '-');
                				}
                          
                            if($orderStatusPreName == "ePayco Esperando Pago"){
                                $history->changeIdOrderState((int)Configuration::get($state), $order, true);
                                $this->RestoreStock($order, '+');
                            }
                        }
                    }
                    
                    if(!$validacionOrderName){
                        if(!$test && $orderStatusPreName != "ePayco Pago Rechazado" || $orderStatusPreName != "ePayco Pago Cancelado" || $orderStatusPreName != "ePayco Pago Fallido"){
                            $keepOn = true;
                        }
                        if($test && $orderStatusPreName != "ePayco Pago Rechazado Prueba" || $orderStatusPreName != "ePayco Pago Cancelado Prueba" || $orderStatusPreName != "ePayco Pago Fallido Prueba" ){
                            $keepOn = true;
                        }

                        if($keepOn ){
                            if($x_cod_response == 1){
                                $orderStatus = Db::getInstance()->executeS('
                                    SELECT name FROM `' . _DB_PREFIX_ . 'order_state_lang`
                                    WHERE `id_order_state` = ' . (int)$config['P_STATE_END_TRANSACTION_agregador']);
                                $orderStatusName = $textMode == "TRUE" ? $orderStatus[0]['name'] . " Prueba" : $orderStatus[0]['name'];
                                $orderStatusEndId = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                                    'SELECT * FROM `' . _DB_PREFIX_ . 'order_state_lang` 
                                    WHERE `name` = "' . $orderStatusName . '"'
                            );
                                $history->changeIdOrderState((int)$orderStatusEndId, $order, true); 
                                $this->RestoreStock($order, '-'); 
                            }
                            if($textMode == "TRUE" && $x_cod_response != 1){
                                $history->changeIdOrderState((int)Configuration::get($state), $order, true); 
                                $this->RestoreStock($order, '-');
                            }else{
                                if($x_cod_response != 1){
                                    $history->changeIdOrderState((int)Configuration::get($state), $order, true);
                                    if($x_franchise == 'PSE'){
                				        $this->RestoreStock($order, '-');
                				    }
                                } 
                            } 
                        }
                        if(!$keepOn){
                            $history->changeIdOrderState((int)Configuration::get($state), $order, true);
                        }
                    }
                }
                if(!$keepOn){
                    if($x_cod_response != 1){
                        $history->changeIdOrderState((int)Configuration::get($state), $order, true);
                    }
                } 
            }
        }else{
            $history = new OrderHistory();
            $history->id_order = (int)$order->id;
            if($test == "yes"){
                if($orderStatusPreName != "ePayco Pago Fallido Prueba"){
                    $this->RestoreStock($order, '+'); 
                }
                 $history->changeIdOrderState((int)Configuration::get("PAYCO_OS_FAILED_TEST"), $order, true);
            }else{
               if($orderStatusPreName != "ePayco Pago Fallido"){
                    $this->RestoreStock($order, '+'); 
                }
                 $history->changeIdOrderState((int)Configuration::get("PAYCO_OS_FAILED"), $order, true); 
            }
        } 
        
        
        if($confirmation){
            header("HTTP/1.1 200 OK");
            echo $x_cod_response;
            die();
           
        }else{
           if(Configuration::get('P_URL_RESPONSE_agregador') != Context::getContext()->link->getModuleLink('epayco_agregador', 'response')){
            //redireccionado
                Tools::redirect(Configuration::get('P_URL_RESPONSE_agregador'));
            }
        }
            
    }

    //restaurar el stock de la orden
    private function RestoreStock($orderId,$operation){
        $order = $orderId;

        foreach ($order->getProductsDetail() as $product){
            StockAvailable::updateQuantity($product['product_id'], $product['product_attribute_id'], $operation.(int)$product['product_quantity'], $order->id_shop);
        }
    }

    private function PostCurl($url){

        if (function_exists('curl_init')) {
            $ch = curl_init();
            $timeout = 5;
            $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
            curl_setopt($ch,CURLOPT_MAXREDIRS,10);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        }else{
            $data =  @Tools::file_get_contents($url);
            return $data;
        }
    }

    private function StreamContext(){
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'protocol_version' => 1.1,
                'timeout' => 10,
                'ignore_errors' => true
            )
        ));
        return $context;
    }

}