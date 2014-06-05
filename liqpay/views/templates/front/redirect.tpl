{**
 * Liqpay Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category        Liqpay
 * @package         Liqpay
 * @version         0.1
 * @author          Liqpay
 * @copyright       Copyright (c) 2014 Liqpay
 * @license         http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 *
 * EXTENSION INFORMATION
 *
 * Prestashop       1.5.6.2
 * LiqPay API       https://www.liqpay.com/ru/doc
 *
 *}

{l s='You will be redirected to the Liqpay website in a few seconds.' mod='liqpay'}

<form id="liqpay_redirect" method="POST" action="https://www.liqpay.com/api/pay" accept-charset="utf-8">
    <input type="hidden" name="public_key"  value="{$public_key}" />
    <input type="hidden" name="amount"      value="{$amount}" />
    <input type="hidden" name="currency"    value="{$currency}" />
    <input type="hidden" name="description" value="{$description}" />
    <input type="hidden" name="order_id"    value="{$order_id}" />
    <input type="hidden" name="result_url"  value="{$result_url}" />
    <input type="hidden" name="server_url"  value="{$server_url}" />
    <input type="hidden" name="type"        value="{$type}" />
    <input type="hidden" name="signature"   value="{$signature}" />
    <input type="hidden" name="language"    value="{$language}" />
</form>
<script>document.getElementById("liqpay_redirect").submit();</script>
