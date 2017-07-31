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
 * LiqPay API       https://www.liqpay.ua/ru/doc
 *
 *}

<p class="payment_module">
<a href="{$link->getModuleLink('liqpay', 'redirect', ['id_cart' => {$id}])}" title="{l s='Pay liqpay' mod='liqpay'}">
    <img src="{$this_path}liqpay.png" />{l s='Pay liqpay' mod='liqpay'}
</a>
</p>
