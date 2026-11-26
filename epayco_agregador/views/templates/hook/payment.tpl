{*
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
*}

<div class="row">
	<div class="col-xs-12 col-md-6">
		<p class="payment_module" id="epayco_agregador_payment_button">
			{if $cart->getOrderTotal() < 2}
				<a href="">
					<img src="{constant('DAVIPAY_ROUTE_IMAGES_S3')}Imagotipo/checkoutSmallWhite_01_v1.svg" alt="Davipay" width="158" height="62" />
					{l s='Monto mínimo requerido para pagar con Davipay:' mod='epayco_agregador'} {convertPrice price=2}
				</a>
			{else}
				<a href="{$link->getModuleLink('epayco_agregador', 'redirect', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Pagar con Davipay' mod='epayco_agregador'}">
					<img src="{constant('DAVIPAY_ROUTE_IMAGES_S3')}Imagotipo/checkoutSmallWhite_01_v1.svg" alt="Davipay" width="158" height="62" />
					{l s='Pagar con Davipay' mod='epayco_agregador'}
				</a>
			{/if}
		</p>
	</div>
</div>
