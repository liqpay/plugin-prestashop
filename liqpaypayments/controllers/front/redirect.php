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

class liqpaypaymentsredirectModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
    
    protected $supported_currencies = array('EUR','UAH','USD');
    protected $page;
    
    public $version = '3.0';
    public $name = 'liqpaypayments';
    public $liqpay_public_key = '';
    public $liqpay_private_key = '';
    public $liqpay_test_public_key = '';
    public $liqpay_test_private_key = '';
    public $liqpay_mode = 0;
    
    protected $public_key = '';
    protected $private_key = '';
    
    public function postProcess() {
        
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
        
        $cart = $this->context->cart;
        
        
        if ($cart->id) {
            $customer = new Customer((int)($cart->id_customer));
            
            if (Validate::isLoadedObject($customer)) {
                $secure_key = $customer->secure_key;
            } else {
                $secure_key = md5(uniqid(rand(), true));
            }
            
            $order = new Order();
            $order->id_customer = $customer->id;
            $order->id_address_invoice = $cart->id_address_invoice;
            $order->id_address_delivery = $cart->id_address_delivery;
            $order->id_currency = $cart->id_currency;
            $order->id_lang = $cart->id_lang;
            $order->id_cart = $cart->id;
            $order->reference = Order::generateReference();
            $order->id_shop = $cart->id_shop;
            $order->id_shop_group = $cart->id_shop_group;
            $order->id_carrier = $cart->id_carrier;
            
            $order->payment = 'LiqPay';
            $order->module = 'liqpaypayments';
            
            $order->conversion_rate = 1;
            $order_status = (int)Configuration::get('PS_OS_PREPARATION');
            
            $order->total_paid = $cart->getOrderTotal(true, Cart::BOTH);
            $order->total_paid_real = $cart->getOrderTotal(true, Cart::BOTH);
            $order->total_paid_tax_incl = $cart->getOrderTotal(true, Cart::BOTH);
            $order->total_paid_tax_excl = $cart->getOrderTotal(false, Cart::BOTH);
            $order->total_products = $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
            $order->total_products_wt = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
            $order->total_shipping = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
            $order->total_shipping_tax_incl = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
            $order->total_shipping_tax_excl = $cart->getOrderTotal(false, Cart::ONLY_SHIPPING);
            
            $order->secure_key = $secure_key;
            
            $order->add();
            
            $order_history = new OrderHistory();
            $order_history->id_order = (int)$order->id;
            $order_history->changeIdOrderState($order_status, (int)($order->id));
            
            //TODO зробити в налаштуваннях перемикач - відсилати на пошту чи ні
            //$order_history->addWithemail(true);
            
            $products = $cart->getProducts();
            
            $order_detail = new OrderDetail(null, null, null);
            $order_detail->createList($order, $cart, 1, $products, 0, true, 0);
            $order_detail->save();

            $cart->delete();
        } else {
            return;
        }
        
        $total = $cart->getOrderTotal(true, Cart::BOTH);
        $currency = new Currency((int)$cart->id_currency);
        
        //Check currency and exit if not supported
        if (!in_array($currency->iso_code, $this->supported_currencies)) {
            return;
        }
        
        // Отримання коду мови
        try {
            $language = new Language((int)$cart->id_lang);
            $language_iso_code = $language->iso_code;
        } catch (Exception $e) {
            $language_iso_code = 'uk';
        }
        
        // Визначення коду мови для LiqPay
        $liqpay_language = 'uk'; // За замовчуванням 'ua'
        if (in_array($language_iso_code, ['en', 'uk'])) {
            $liqpay_language = $language_iso_code;
        }
        
        $params = [
            'amount' => $total,
            'currency' => $currency->iso_code,
            'description' => 'Оплата замовлення №' . $cart->id,
            'language' => $liqpay_language,
            'order_id' => $order->id,
            'version' => '3',
            'action' => 'pay',
            'public_key' => $this->public_key,
//            'server_url' => $this->context->link->getModuleLink($this->name, 'PaymentReturn', [], true),
            'server_url' => "https://webhook.site/61c92bd2-40d8-412b-b93c-ca234667c637",
            'callback_url' => "http://prestashop.local/"
        ];
        $liqpay = new LiqPay($this->public_key, $this->private_key);
        $res = $liqpay->cnb_form_raw($params);
        
        $data = $res['data'];
        $signature = $res['signature'];
        $url = $res['url'];
        
        $this->context->smarty->assign(compact('data', 'signature', 'url'));
        $this->setTemplate('module:liqpaypayments/views/templates/front/redirect.tpl');
    }
    
}
