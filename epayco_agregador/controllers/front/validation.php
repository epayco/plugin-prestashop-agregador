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
class Epayco_agregadorValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        /*
         * If the module is not active anymore, no need to process anything.
         */
        if ($this->module->active == false) {
            die;
        }
    $cart = $this->context->cart;

        /**
         * Since it is an example, we choose sample data,
         * You'll have to get the correct values :)
         */
        // $cart_id = 1;
        // $customer_id = 1;
        // $amount = 100.00;
    //     $cart = $this->context->cart;
       if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
             Tools::redirect('index.php?controller=order&step=1');


        $authorized = false;
        foreach (Module::getPaymentModules() as $module)
            if ($module['name'] == 'epayco_agregador')
            {
                $authorized = true;
                break;
            }
        if (!$authorized)
            die($this->module->l('This payment method is not available.', 'validation'));

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('index.php?controller=order&step=1');

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $mailVars = array(
            '{payco_id}' => Configuration::get('merchantid'),
            '{payco_detail}' => nl2br(Configuration::get('merchantpassword'))
        );

        /*
         * Restore the context from the $cart_id & the $customer_id to process the validation properly.
         */
        Context::getContext()->cart = new Cart((int) $cart->id);
        Context::getContext()->customer = new Customer((int) $cart->id_customer);
        Context::getContext()->currency = new Currency((int) Context::getContext()->cart->id_currency);
        Context::getContext()->language = new Language((int) Context::getContext()->customer->id_lang);

        $secure_key = Context::getContext()->customer->secure_key;



        $module_name = $this->module->displayName;
        $currency_id = (int) Context::getContext()->currency->id;

         $this->module->validateOrder($cart->id, CreditCard_OrderStates::getInitialState(), $total, $module_name, NULL,  $mailVars, $currency_id, false, $secure_key);
      
        // $this->module->validateOrder($cart->id, CreditCard_OrderStates::getInitialState(), $total, $this->module->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
         Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.(int)$currency->id.'&key='.$customer->secure_key);

     }

    // protected function isValidOrder()
    // {
    //     /*
    //      * Add your checks right there
    //      */
    //     return true;
    // }
}
