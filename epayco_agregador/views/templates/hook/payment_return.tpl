{*
* 2007-2017 PrestaShop
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
*  @copyright 2007-2017 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{if $status == 'ok'}

<div class="loader-container">
    <div class="loading"></div>
</div>
<p style="text-align: center;" class="epayco-title">
    <span class="animated-points">Cargando metodos de pago agregador</span>
   <br><small class="epayco-subtitle"> Si no se cargan automáticamente, de clic en el botón "Pagar con ePayco"</small>
</p>
<a id="btn_epayco" href="#">
    <img src="{$url_button|escape:'htmlall':'UTF-8'}">
</a>
<form id="epayco_form" style="text-align: center;">
     <script src="https://epayco-checkout-testing.s3.amazonaws.com/checkout.preprod.js"></script>
     <script>
     var handler = ePayco.checkout.configure({
            key: "{$public_key_agregador}",
            test: "{$merchanttest}"
        })
    var data = {
            name: ""ORDEN DE COMPRA # {$refVenta|escape:'htmlall':'UTF-8'}",
            description: ""ORDEN DE COMPRA # {$refVenta|escape:'htmlall':'UTF-8'}",
            invoice: "{$refVenta|escape:'htmlall':'UTF-8'}",
            currency: "{$currency|lower|escape:'htmlall':'UTF-8'}",
            amount: "{$total|escape:'htmlall':'UTF-8'}",
            tax_base: "{$baseDevolucionIva|escape:'htmlall':'UTF-8'}",
            tax: "{$iva|escape:'htmlall':'UTF-8'}",
            taxIco: "0",
            country: "{$iso|lower|escape:'htmlall':'UTF-8'}",
            lang: "{$lang|escape:'htmlall':'UTF-8'}",
            external: "{$external|escape:'htmlall':'UTF-8'}",
            confirmation: "{$p_url_confirmation|unescape: 'html' nofilter}",
            response: "{$p_url_response_agregador|unescape: 'html' nofilter}",
            name_billing: "{$p_billing_name|escape:'htmlall':'UTF-8'} {$p_billing_last_name|escape:'htmlall':'UTF-8'}",
            address_billing: "{$p_billing_address|escape:'htmlall':'UTF-8'}",
            email_billing: "{$p_billing_email|escape:'htmlall':'UTF-8'}",
            extra1: "{$extra1|escape:'htmlall':'UTF-8'}",
            extra2: "{$extra2|escape:'htmlall':'UTF-8'}",
            extra3: "{$refVenta|escape:'htmlall':'UTF-8'}"
        }
        var openChekout = function () {
            handler.open(data);
        }
        var bntPagar = document.getElementById("btn_epayco");
        bntPagar.addEventListener("click", openChekout);
        setTimeout(handler, 2000)  
    </script>
</form>

    {literal} 
<script>
    
</script>
    {/literal}
{else}
<p class="warning">
  {l s='Hemos notado un problema con tu orden, si crees que es un error puedes contactar a nuestro departamento de Soporte' mod='epayco_agregador'}
  {l s='' mod='epayco_agregador'}.
</p>
{/if}
