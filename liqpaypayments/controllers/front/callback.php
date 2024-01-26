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
 * Prestashop       1.8.1.1
 * LiqPay API       https://www.liqpay.ua/documentation/uk
 *
 */
class liqpaypaymentsCallbackModuleFrontController extends ModuleFrontController
{
    public $name = 'liqpaypayments';
    public $liqpay_public_key = '';
    public $liqpay_private_key = '';
    public $liqpay_test_public_key = '';
    public $liqpay_test_private_key = '';
    public $liqpay_mode = 0;
    
    protected $public_key = '';
    protected $private_key = '';
    
    public function postProcess()
    {
        require_once(_PS_MODULE_DIR_.$this->name.'/libs/LiqPay.php');
        
        $params = array('LIQPAY_PUBLIC_KEY', 'LIQPAY_PRIVATE_KEY', 'LIQPAY_TEST_PUBLIC_KEY', 'LIQPAY_TEST_PRIVATE_KEY', 'LIQPAY_MODE');
        $config = Configuration::getMultiple($params);
        
        // Визначення поточного режиму (тестовий або лайв)
        $this->liqpay_mode = isset($config['LIQPAY_MODE']) && $config['LIQPAY_MODE'] == "1";
        
        // Вибір ключів залежно від режиму
        if ($this->liqpay_mode) { // Якщо лайв режим
            $this->liqpay_public_key = isset($config['LIQPAY_PUBLIC_KEY']) ? $config['LIQPAY_PUBLIC_KEY'] : '';
            $this->liqpay_private_key = isset($config['LIQPAY_PRIVATE_KEY']) ? $config['LIQPAY_PRIVATE_KEY'] : '';
            $this->public_key = $this->liqpay_public_key;
            $this->private_key = $this->liqpay_private_key;
        } else { // Якщо тестовий режим
            $this->liqpay_test_public_key = isset($config['LIQPAY_TEST_PUBLIC_KEY']) ? $config['LIQPAY_TEST_PUBLIC_KEY'] : '';
            $this->liqpay_test_private_key = isset($config['LIQPAY_TEST_PRIVATE_KEY']) ? $config['LIQPAY_TEST_PRIVATE_KEY'] : '';
            $this->public_key = $this->liqpay_test_public_key;
            $this->private_key = $this->liqpay_test_private_key;
        }
        
        $data = Tools::getValue('data');
        $signature = Tools::getValue('signature');
        
        if (empty($data) || empty($signature)) {
            die('Error: Missing required parameters.');
        }
        
        if (!$this->validateLiqPaySignature($data, $signature)) {
            die('Signature is not valid');
        }
        
        $DecodedData = json_decode(base64_decode($data), true);
        $orderStatus = $DecodedData['status'] ?? null;
        
        $validStatuses = ['success', 'wait_compensation', 'wait_reserve'];
        
        if (in_array($orderStatus, $validStatuses)) {
            // Оновлення статусу замовлення у разі успішної транзакції
            $this->updateOrderStatus($DecodedData['order_id'], Configuration::get('PS_OS_PAYMENT'));
        } else {
            // Оновлення статусу замовлення у разі невдалої транзакції
            $this->updateOrderStatus($DecodedData['order_id'], Configuration::get('PS_OS_ERROR'));
        }
        
        die('OK');
    }
    
    private function updateOrderStatus($orderId, $statusId)
    {
        $order = new Order((int) $orderId);
        if (Validate::isLoadedObject($order)) {
            $order->setCurrentState($statusId);
        } else {
            die('Error: Order not found.');
        }
    }
    
    private function validateLiqPaySignature($data, $receivedSignature)
    {
        $generatedSignature = base64_encode(sha1($this->private_key . $data . $this->private_key, 1));
        
        return $generatedSignature == $receivedSignature;
    }
    
}
