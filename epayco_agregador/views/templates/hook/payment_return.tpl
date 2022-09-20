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

<form id="epayco_form" style="text-align: center;">
    <script src="https://checkout.epayco.co/checkout.js"
        class="epayco-button"
        data-epayco-key="{$public_key_agregador|escape:'htmlall':'UTF-8'}"
        data-epayco-amount="{$total|escape:'htmlall':'UTF-8'}"
        data-epayco-tax="{$iva|escape:'htmlall':'UTF-8'}"
        data-epayco-tax-base="{$baseDevolucionIva|escape:'htmlall':'UTF-8'}"    
        data-epayco-name="ORDEN DE COMPRA # {$refVenta|escape:'htmlall':'UTF-8'}"
        data-epayco-description="ORDEN DE COMPRA # {$refVenta|escape:'htmlall':'UTF-8'}"
        data-epayco-currency="{$currency|lower|escape:'htmlall':'UTF-8'}"
        data-epayco-invoice="{$refVenta|escape:'htmlall':'UTF-8'}"
        data-epayco-country="{$iso|lower|escape:'htmlall':'UTF-8'}"
        data-epayco-test="{$merchanttest}"
        data-epayco-extra1="{$extra1|escape:'htmlall':'UTF-8'}"
        data-epayco-extra2="{$extra2|escape:'htmlall':'UTF-8'}"
        data-epayco-extra3=""
        data-epayco-external="{$external|escape:'htmlall':'UTF-8'}"
        data-epayco-response="{$p_url_response_agregador|unescape: 'html' nofilter}" 
        data-epayco-confirmation="{$p_url_confirmation_agregador|unescape: 'html' nofilter}"
        data-epayco-email-billing="{$p_billing_email|escape:'htmlall':'UTF-8'}"
        data-epayco-name-billing="{$p_billing_name|escape:'htmlall':'UTF-8'} {$p_billing_last_name|escape:'htmlall':'UTF-8'}"
        data-epayco-address-billing="{$p_billing_address|escape:'htmlall':'UTF-8'}"
        data-epayco-lang="{$lang|escape:'htmlall':'UTF-8'}"
        data-epayco-button="{$url_button|escape:'htmlall':'UTF-8'}"
        data-epayco-mobilephone-billing="{$p_billing_phone|escape:'htmlall':'UTF-8'}"
        data-epayco-autoClick="true"
        >
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