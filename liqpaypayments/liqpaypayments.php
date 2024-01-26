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

if (!defined('_PS_VERSION_')) { exit; }

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

/**
 * Payment method liqpay form
 *
 * @author      Liqpay <support@liqpay.ua>
 */
class LiqpayPayments extends PaymentModule
{
//	protected $supported_currencies = array('EUR','UAH','USD');
    protected $page;
    
    public $name = 'liqpaypayments';
    public $tab = 'payments_gateways';
    public $version = '3.0';
    public $author = 'Liqpay';
    public $need_instance = 0;
    
//	public $liqpay_public_key = '';
//	public $liqpay_private_key = '';
//    public $liqpay_test_public_key = '';
//    public $liqpay_test_private_key = '';
//    public $liqpay_mode = 0;
//
//    protected $public_key = '';
//    protected $private_key = '';
    
    const HOOKS = [
        'payment',
        'paymentOptions',
        'paymentReturn',
    ];

    /**
     * Costructor
     *
     * @return Liqpay
     */
	public function __construct()
	{
        require_once(_PS_MODULE_DIR_.$this->name.'/libs/LiqPay.php');
        
        parent::__construct();
        $this->page = basename(__FILE__, '.php');
        $this->displayName = 'Liqpay';
        $this->description = $this->l('Accept payments with Liqpay');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
	}


    /**
     * Return module web path
     *
     * @return string
     */
	public function getPath()
	{
		return $this->_path;
	}

    /**
     * Install module
     *
     * @return bool
     */
    public function install()
    {
        return parent::install() &&
            (bool) $this->registerHook(static::HOOKS);
    }


    /**
     * Uninstall module
     *
     * @return bool
     */
	public function uninstall()
	{
		return
			parent::uninstall() &&
			Configuration::deleteByName('LIQPAY_PUBLIC_KEY') &&
			Configuration::deleteByName('LIQPAY_PRIVATE_KEY') &&
            Configuration::deleteByName('LIQPAY_TEST_PUBLIC_KEY') &&
            Configuration::deleteByName('LIQPAY_TEST_PRIVATE_KEY') &&
            Configuration::deleteByName('LIQPAY_MODE');
	}


    /**
     * Hook payment
     *
     * @param array $params
     *
     * @return string
     */
	public function hookPayment($params)
	{
        var_dump($params);
		if (!$this->active) { return; }
		if (!$this->checkCurrency($params['cart'])) { return; }
		$currency = new Currency((int)($params['cart']->id_currency));
        var_dump($currency);
        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			'id' => (int)$params['cart']->id,
        ));
        return $this->display(__FILE__, 'payment.tpl');
	}
    
    
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        
        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name);
        $newOption->setCallToActionText($this->l('Liqpay'));
        $newOption->setAction($this->context->link->getModuleLink($this->name, 'redirect', [], true));
        $newOption->setLogo("https://static.liqpay.ua/buttons/LiqPay-logo.svg");
        
        return array($newOption);
    }

    /**
     * Check currency
     *
     * @param  Cart $cart
     *
     * @return bool
     */
	public function checkCurrency($cart)
	{
		$currency_order = new Currency((int)($cart->id_currency));
		$currencies_module = $this->getCurrency((int)$cart->id_currency);
		if (is_array($currencies_module)) {
			foreach ($currencies_module as $currency_module) {
				if ($currency_order->id == $currency_module['id_currency']){
					return true;
				}
			}
		}
		return false;
	}


    /**
     * Get a configuration page
     *
     * @return string
     */
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit'.$this->name)) {
            $liqpay_mode = Tools::getValue('LIQPAY_MODE');
            $liqpay_public_key = strval(Tools::getValue('LIQPAY_PUBLIC_KEY'));
            $liqpay_private_key = strval(Tools::getValue('LIQPAY_PRIVATE_KEY'));
            $liqpay_test_public_key = strval(Tools::getValue('LIQPAY_TEST_PUBLIC_KEY'));
            $liqpay_test_private_key = strval(Tools::getValue('LIQPAY_TEST_PRIVATE_KEY'));
            
            // Перевірка для режиму 'Live'
            $err_live = $liqpay_mode && (
                    empty($liqpay_public_key) || !Validate::isGenericName($liqpay_public_key) ||
                    !$liqpay_private_key || empty($liqpay_private_key) || !Validate::isGenericName($liqpay_private_key)
                );
            
            // Перевірка для тестового режиму
            $err_test = !$liqpay_mode && (
                    empty($liqpay_test_public_key) || !Validate::isGenericName($liqpay_test_public_key) ||
                    !$liqpay_test_private_key || empty($liqpay_test_private_key) || !Validate::isGenericName($liqpay_test_private_key)
                );
            
            if ($err_live || $err_test) {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('LIQPAY_MODE', $liqpay_mode);
                Configuration::updateValue('LIQPAY_PUBLIC_KEY', $liqpay_public_key);
                Configuration::updateValue('LIQPAY_PRIVATE_KEY', $liqpay_private_key);
                Configuration::updateValue('LIQPAY_TEST_PUBLIC_KEY', $liqpay_test_public_key);
                Configuration::updateValue('LIQPAY_TEST_PRIVATE_KEY', $liqpay_test_private_key);
                $this->liqpay_public_key = $liqpay_public_key;
                $this->liqpay_private_key = $liqpay_private_key;
                $this->liqpay_test_public_key = $liqpay_test_public_key;
                $this->liqpay_test_private_key = $liqpay_test_private_key;
                $this->liqpay_mode = $liqpay_mode;
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }


    /**
     * Generate form
     *
     * @return string
     */
	public function displayForm()
	{
	    $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Mode'),
                    'name' => 'LIQPAY_MODE',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Live')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Test')
                        )
                    ),
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Public key'),
                    'name' => 'LIQPAY_PUBLIC_KEY',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Private key'),
                    'name' => 'LIQPAY_PRIVATE_KEY',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Test Public key'),
                    'name' => 'LIQPAY_TEST_PUBLIC_KEY',
                    'size' => 20,
                    'required' => false
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Test Private key'),
                    'name' => 'LIQPAY_TEST_PRIVATE_KEY',
                    'size' => 20,
                    'required' => false
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );

	    $helper = new HelperForm();
	    $helper->module = $this;
	    $helper->name_controller = $this->name;
	    $helper->token = Tools::getAdminTokenLite('AdminModules');
	    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
	    $helper->default_form_language = $default_lang;
	    $helper->allow_employee_form_lang = $default_lang;
	    $helper->title = $this->displayName;
	    $helper->show_toolbar = true;
	    $helper->toolbar_scroll = true;
	    $helper->submit_action = 'submit'.$this->name;
	    $helper->toolbar_btn = array(
	        'save' => array(
	            'desc' => $this->l('Save'),
	            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
	            '&token='.Tools::getAdminTokenLite('AdminModules'),
	        ),
	        'back' => array(
	            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
	            'desc' => $this->l('Back to list')
	        )
	    );
		$helper->fields_value['LIQPAY_PUBLIC_KEY'] = $this->liqpay_public_key;
		$helper->fields_value['LIQPAY_PRIVATE_KEY'] = $this->liqpay_private_key;
        $helper->fields_value['LIQPAY_TEST_PUBLIC_KEY'] = $this->liqpay_test_public_key;
        $helper->fields_value['LIQPAY_TEST_PRIVATE_KEY'] = $this->liqpay_test_private_key;
		$helper->fields_value['LIQPAY_MODE'] = $this->liqpay_mode;
	    return $helper->generateForm($fields_form);
	}
    
    // Callback for prestashop 1.7+
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        
        $order = $params['order'];
        $state = $order->getCurrentState();
        if (in_array($state, array(Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_OUTOFSTOCK')))) {
            $this->smarty->assign(array(
                'status' => 'ok',
                'id_order' => $order->id
            ));
        } else {
            $this->smarty->assign('status', 'failed');
        }
        
        return $this->display(__FILE__, 'payment_return.tpl');
    }
}


