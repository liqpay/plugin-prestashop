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
 * @version         3.0
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
include(dirname(__FILE__). '/../../config/config.inc.php');
include(dirname(__FILE__).'/liqpay.php');

$liqpay = new Liqpay();

$success =
    isset($_POST['data']) &&
    isset($_POST['signature']);

if (!$success) { die(); }

$data                = $_POST['data'];
$parsed_data         = json_decode(base64_decode($data));
$received_signature  = $_POST['signature'];

$received_public_key = $parsed_data->public_key;
$order_id            = $parsed_data->order_id;
$status              = $parsed_data->status;

$order = New Order();
$OrderID = $order->getOrderByCartId(intval($order_id));
if (!$OrderID) { die(); }
$order = New Order($OrderID);
if ($order->getCurrentState() != Configuration::get('PS_OS_PREPARATION')) { die(); }

$private_key = Configuration::get('LIQPAY_PRIVATE_KEY');
$public_key  = Configuration::get('LIQPAY_PUBLIC_KEY');

$generated_signature = base64_encode(sha1($private_key.$data.$private_key, 1));

if ($received_signature != $generated_signature || $public_key != $received_public_key) { die(); }

if ($status == 'success') {
	$history = new OrderHistory();
	$history->id_order = $OrderID;
	$history->id_order_state = Configuration::get('PS_OS_PAYMENT');
	$history->add();
} elseif ($status == 'failure') {
    $history = new OrderHistory();
    $history->id_order = $OrderID;
    $history->id_order_state = Configuration::get('PS_OS_ERROR');
    $history->add();
}
