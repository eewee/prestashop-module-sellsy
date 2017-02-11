{*
* 2007-2016 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if $EEWEE_SELLSY_DISPLAY_FORM_SUPPORT}
	<div class="eewee_sellsy_form">
		<h4>{l s='Need help ?' d='Modules.EeweeSellsy.Shop'}</h4>

		{if $msg}
			<p class="notification {if $error}notification-error{else}notification-success{/if}">{$msg}</p>
		{/if}

		<div class="row">
			<div class="col-md-6 wrapper">
				<a name="eewee_sellsy_help_form"></a>
				<form action="{$urls.pages.index}" method="post" {*class="form-inline"*}>
					<input type="text" class="form-control" name="f_eewee_sellsy_name" value="{$f_eewee_sellsy_name}" placeholder="{l s='Name' d='Modules.EeweeSellsy.Shop'}" />
					<br>
					<input type="email" class="form-control" name="f_eewee_sellsy_email" value="{$f_eewee_sellsy_email}" placeholder="{l s='Email' d='Modules.EeweeSellsy.Shop'}" />
					<br>
					<textarea class="form-control" name="f_eewee_sellsy_message" placeholder="Message" rows="3">{$f_eewee_sellsy_message}</textarea>
					<br>
					<input type="submit" class="btn btn-primary" value="ok" name="submitEeweeSellsy" />
				</form>

			</div>
		</div>

	</div>
{/if}