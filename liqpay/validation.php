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
 * LiqPay API       https://www.liqpay.com/ru/doc
 *
 */

include(dirname(__FILE__). '/../../config/config.inc.php');
include(dirname(__FILE__).'/liqpay.php');

$filename = dirname(__FILE__).'/out';

$liqpay = new Liqpay();

$success =
    isset($_POST['amount']) &&
    isset($_POST['currency']) &&
    isset($_POST['public_key']) &&
    isset($_POST['description']) &&
    isset($_POST['order_id']) &&
    isset($_POST['type']) &&
    isset($_POST['status']) &&
    isset($_POST['transaction_id']) &&
    isset($_POST['sender_phone']);

if (!$success) { die(); }

$amount = $_POST['amount'];
$currency = $_POST['currency'];
$public_key = $_POST['public_key'];
$description = $_POST['description'];
$order_id = $_POST['order_id'];
$type = $_POST['type'];
$status = $_POST['status'];
$transaction_id = $_POST['transaction_id'];
$sender_phone = $_POST['sender_phone'];
$insig = $_POST['signature'];

$order = New Order();
$OrderID = $order->getOrderByCartId(intval($order_id));
if (!$OrderID) { die(); }
$order = New Order($OrderID);
if ($order->getCurrentState() != _PS_OS_PREPARATION_) { die(); }

$private_key = Configuration::get('LIQPAY_PRIVATE_KEY');

$gensig = base64_encode(sha1(join('',compact(
    'private_key',
    'amount',
    'currency',
    'public_key',
    'order_id',
    'type',
    'description',
    'status',
    'transaction_id',
    'sender_phone'
)),1));

if ($insig != $gensig) { die(); }

if ($status == 'success') {
	$history = new OrderHistory();
	$history->id_order = $OrderID;
	$history->id_order_state = _PS_OS_PAYMENT_;
	$history->add();
	//$history->changeIdOrderState(_PS_OS_PAYMENT_, $OrderID);
}
