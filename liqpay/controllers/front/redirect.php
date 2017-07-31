<?php
/**
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
 * LiqPay API       https://www.liqpay.ua/documentation/ru
 *
 */

require_once(dirname(__FILE__).'../../../liqpay.php');
class liqpayredirectModuleFrontController extends ModuleFrontController
{
	public $ssl = true;

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();
		$id_cart = Tools::GetValue('id_cart');
		$cart = New Cart((int)$id_cart);
		$liqpay = new Liqpay();
		$total = $cart->getOrderTotal(true, 3);
		$liqpay->validateOrder(intval($cart->id), Configuration::get('PS_OS_PREPARATION'), $total, $liqpay->displayName);
		$currency = new Currency((int)($cart->id_currency));

		$private_key = Configuration::get('LIQPAY_PRIVATE_KEY');
		$public_key  = Configuration::get('LIQPAY_PUBLIC_KEY');
		$amount = number_format($cart->getOrderTotal(true, Cart::BOTH), 1, '.', '');
		$currency = $currency->iso_code == 'RUR' ? 'RUB' : $currency->iso_code;
		$order_id = '000'.$id_cart;
		$description = 'Order #'.$order_id;
		$result_url = 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'index.php?controller=history';
		$server_url = 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').$liqpay->getPath().'validation.php';
		$type = 'buy';

		$version = '3';
		$language = Configuration::get('PS_LOCALE_LANGUAGE') == 'en' ? 'en' : 'ru';

		$data = base64_encode(
			        json_encode(
						    array('version'     => $version,
						    	  'public_key'  => $public_key,
						    	  'amount'      => $amount,
						    	  'currency'    => $currency,
						 		  'description' => $description,
						    	  'order_id'    => $order_id,
						    	  'type'        => $type,
						    	  'language'    => $language)
			        			)
		        			);

		$signature = base64_encode(sha1($private_key.$data.$private_key, 1));
        
		$this->context->smarty->assign(compact('data', 'signature'));
		$this->setTemplate('redirect.tpl');
	}
}
