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
 * LiqPay API       https://www.liqpay.ua/ru/doc
 *
 */

if (!defined('_PS_VERSION_')) { exit; }

/**
 * Payment method liqpay form
 *
 * @author      Liqpay <support@liqpay.com>
 */
class Liqpay extends PaymentModule
{
	protected $supported_currencies = array('EUR','UAH','USD','RUB');

	public $liqpay_public_key = '';
	public $liqpay_private_key = '';

    /**
     * Costructor
     *
     * @return Liqpay
     */
	public function __construct()
	{
		$this->name = 'liqpay';
		$this->tab = 'payments_gateways';
		$this->version = '0.1';
		$this->author = 'Liqpay';
		$this->need_instance = 0;
        $params = array('LIQPAY_PUBLIC_KEY','LIQPAY_PRIVATE_KEY');
        $config = Configuration::getMultiple($params);
		if (isset($config['LIQPAY_PUBLIC_KEY']) && $config['LIQPAY_PUBLIC_KEY']) {
			$this->liqpay_public_key = $config['LIQPAY_PUBLIC_KEY'];
		}
		if (isset($config['LIQPAY_PRIVATE_KEY']) && $config['LIQPAY_PRIVATE_KEY']) {
			$this->liqpay_private_key = $config['LIQPAY_PRIVATE_KEY'];
		}

		parent::__construct();
        $this->page = basename(__FILE__, '.php');
        $this->displayName = 'Liqpay';
        $this->description = $this->l('Accept payments with Liqpay');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
        $correctly = !isset($this->liqpay_public_key) OR !isset($this->liqpay_private_key);
        if ($correctly) {
        	$this->warning = $this->l('Your Liqpay account must be set correctly');
        }
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
		return parent::install() && $this->registerHook('payment');
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
			Configuration::deleteByName('LIQPAY_PRIVATE_KEY');
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
		if (!$this->active) { return; }
		if (!$this->checkCurrency($params['cart'])) { return; }
		$currency = new Currency((int)($params['cart']->id_currency));
        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			'id' => (int)$params['cart']->id,
        ));
        return $this->display(__FILE__, 'payment.tpl');
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
			$liqpay_public_key = strval(Tools::getValue('LIQPAY_PUBLIC_KEY'));
			$liqpay_private_key = strval(Tools::getValue('LIQPAY_PRIVATE_KEY'));

            $err = !$liqpay_public_key  || empty($liqpay_public_key)  || !Validate::isGenericName($liqpay_public_key)  ||
                   !$liqpay_private_key || empty($liqpay_private_key) || !Validate::isGenericName($liqpay_private_key);

	        if ($err) {
	            $output .= $this->displayError( $this->l('Invalid Configuration value') );
	        } else {
	            Configuration::updateValue('LIQPAY_PUBLIC_KEY', $liqpay_public_key);
	            Configuration::updateValue('LIQPAY_PRIVATE_KEY', $liqpay_private_key);
				$this->liqpay_public_key = $liqpay_public_key;
				$this->liqpay_private_key = $liqpay_private_key;
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
	    return $helper->generateForm($fields_form);
	}
}
