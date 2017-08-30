<?php
if (!defined('_PS_VERSION_')) {
	exit;
}

class LpExpress extends CarrierModule
{
	/**
	 * LP Express terminals JSON list URL
	 *
	 * @var string
	 */
	public $terminals_url = 'https://www.lpexpress.lt/index.php?cl=terminals&fnc=getTerminals';

	/**
	 * MySQL tables need to be installed
	 *
	 * @var array
	 */
	public $mysql_tables = array('lpexpress_terminal_for_cart');


	/**
	 * lpexpress constructor.
	 */
	public function __construct()
	{
		$this->name = 'lpexpress';
		$this->tab = 'shipping_logistics';
		$this->version = '1.0.0';
		$this->author = 'Martynas Žaliaduonis';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.7.1', 'max' => _PS_VERSION_);
		$this->limited_countries = array('lt');
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('LP Express Shipping');
		$this->description = $this->l('LP Express (lpexpress.lt) shipping method. This module adds two new carriers to your shop: shipping to LP Express self-service parcel terminals and shipping via LP Express courier.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

	}

	/**
	 * PrestaShop module installation function
	 *
	 * @return bool
	 */
	public function install()
	{
		$carrierConfig = array(
			0 => array('name' => $this->l('LP Express courier'),
			           'active' => true,
			           'shipping_handling' => false, // Free shipping handling
			           'delay' => array(
				           'lt' => 'LP Express kurjerių pristatymas',
				           'en' => 'Shipping via LP Express courier services', // English value is required
			           ),
			           'is_module' => true, // We specify that it is a module
			           'shipping_external' => true,
			           'external_module_name' => 'lpexpress', // We specify the name of the module
			           'need_range' => true, // We want want the calculations for the ranges that are configured in the back office
			),
			1 => array('name' => $this->l('LP Express parcel terminals'),
			           'active' => true,
			           'shipping_handling' => false,
			           'delay' => array(
				           'lt' => 'Pristatymas į LP Express paštomatus',
				           'en' => 'Shipping to LP Express self-service parcel terminals',
			           ),
				      'is_module' => true,
				      'shipping_external' => true,
				      'external_module_name' => 'lpexpress',
				      'need_range' => true,
			    ),
		);

		// Check that the Multistore feature is enabled, and if so, set the current context to all shops on this installation of PrestaShop
		if (Shop::isFeatureActive()) {
			Shop::setContext( Shop::CONTEXT_ALL );
		}

		if (!parent::install() ||
			!$this->registerHook('updateCarrier') ||
			!$this->registerHook('displayCarrierExtraContent') ||
			!$this->registerHook('actionValidateStepComplete') ||
			!$this->registerHook('displayOrderDetail') ||
			!$this->registerHook('displayOrderConfirmation') ||
			!$this->registerHook('displayAdminOrder') ||
			!$this->registerHook('actionGetExtraMailTemplateVars') ||
			!$this->createMySQLTables())
		{
			return false;
		}

		// Creating carriers and saving ids
		$id_carrier1 = $this->installExternalCarrier($carrierConfig[0]);
		$id_carrier2 = $this->installExternalCarrier($carrierConfig[1]);
		Configuration::updateValue('LPEXPRESS1_CARRIER_ID', (int)$id_carrier1);
		Configuration::updateValue('LPEXPRESS2_CARRIER_ID', (int)$id_carrier2);

		return true;
	}

	/**
	 * PrestaShop module uninstall function
	 *
	 * @return bool
	 */
	public function uninstall()
	{
		if (!parent::uninstall() ||
			!$this->unregisterHook('updateCarrier') ||
			!$this->unregisterHook('displayCarrierExtraContent') ||
			!$this->unregisterHook('actionValidateStepComplete') ||
			!$this->unregisterHook('displayOrderDetail') ||
			!$this->unregisterHook('displayOrderConfirmation') ||
			!$this->unregisterHook('displayAdminOrder') ||
			!$this->unregisterHook('actionGetExtraMailTemplateVars') ||
            !$this->dropMySQLTables())
		{
			return false;
		}

		// We need to delete the carriers we created earlier
		$carrier1 = new Carrier((int)(Configuration::get('LPEXPRESS1_CARRIER_ID')));
		$carrier2 = new Carrier((int)(Configuration::get('LPEXPRESS2_CARRIER_ID')));

		// If one of our carriers is default, we will change it to other
		if (Configuration::get('PS_CARRIER_DEFAULT') == (int)($carrier1->id) ||
		    Configuration::get('PS_CARRIER_DEFAULT') == (int)($carrier2->id))
		{
			$carriers = Carrier::getCarriers($this->context->language->id, true, false, false,NULL, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
			foreach($carriers as $carrier) {
				if ($carrier['active'] && !$carrier['deleted'] && $carrier['external_module_name'] != $this->name ) {
					Configuration::updateValue('PS_CARRIER_DEFAULT', $carrier['id_carrier']);
					break;
				}
			}
		}

		// Then we delete the carriers using variable delete
		// in order to keep the carrier history for orders placed with them
		$carrier1->deleted = 1;
		$carrier2->deleted = 1;

		if (!$carrier1->update() || !$carrier2->update()) {
			return false;
		}

		return true;
	}

	/**
	 * Installing required tables
	 *
	 * @return bool
	 */
	public function createMySQLTables()
    {
		$success = true;
		foreach($this->mysql_tables as $table) {
			$sql = Tools::file_get_contents(_PS_MODULE_DIR_ . $this->name . '/install/' . $table . '.sql');
			$sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
			$sql = str_replace('_ENGINE_', _MYSQL_ENGINE_, $sql);

			$success &= DB::getInstance()->execute($sql);
		}

		return $success;
	}

	/**
	 * Deleting required tables
	 *
	 * @return bool
	 */
	public function dropMySQLTables()
    {
		$success = true;
		foreach($this->mysql_tables as $table) {
			$sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $table . '`';
			$success &= DB::getInstance()->execute($sql);
		}

		return $success;
	}

	/**
	 * Creating carrier with our settings
	 *
	 * @param $config
	 *
	 * @return bool|int
	 */
	public static function installExternalCarrier($config)
	{
		$carrier = new Carrier();
		$carrier->name = $config['name'];
		$carrier->active = $config['active'];
		$carrier->shipping_handling = $config['shipping_handling'];
		$carrier->is_module = $config['is_module'];
		$carrier->shipping_external = $config['shipping_external'];
		$carrier->external_module_name = $config['external_module_name'];
		$carrier->need_range = $config['need_range'];

		$languages = Language::getLanguages(true);
		foreach ($languages as $language)
		{
			if (isset($config['delay'][$language['iso_code']])) {
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			} else {
				$carrier->delay[(int)$language['id_lang']] = $config['delay']['en'];
			}
		}

		if ($carrier->add())
		{
			// Add carrier to all customers groups
			$groups = Group::getGroups(true);
			foreach ($groups as $group) {
				Db::getInstance()->insert(  'carrier_group', array(
					'id_carrier' => (int) ( $carrier->id ),
					'id_group'   => (int) ( $group['id_group'] )
				) );
			}

			$rangeWeight = new RangeWeight();
			$rangeWeight->id_carrier = $carrier->id;
			$rangeWeight->delimiter1 = '0';
			$rangeWeight->delimiter2 = '10000'; // dummy value
			$rangeWeight->add();

			// Add carrier to 'Europe' shipping zone
			$id_zone_europe = Zone::getIdByName('Europe');
			$carrier->addZone($id_zone_europe ? $id_zone_europe : 1);

			// Copy Logo
			if (!copy(dirname(__FILE__).'/carrier.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg'))
				return false;

			return (int)($carrier->id);
		}

		return false;
	}

	/**
	 * Required function for carrier module.
	 * With this function we can manipulate with shipping price by package's size but it's free module.
	 *
	 * @param $params
	 * @param $shipping_cost
	 *
	 * @return float
	 */
	public function getOrderShippingCost($params, $shipping_cost)
	{
		// Just returning price which is set by admin
		return (float)$shipping_cost;
	}

	/**
	 * Required function for carrier module.
	 *
	 * @param $params
	 *
	 * @return float
	 */
	public function getOrderShippingCostExternal($params)
	{
		return 0.00;
	}

	/**
	 * Returning all terminals list in array.
	 *
	 * @return array
	 */
	public function getTerminals()
	{
		$terminals = $this->getTerminalsFromCache();

		if ($terminals != null) {
			return $terminals;
		}

		// If we don't have terminals in our database
		// or they are outdated we will download them now
		$terminalsJson  = file_get_contents($this->terminals_url);
		$terminalsJson  = json_decode($terminalsJson);
		$terminals       = array();
		foreach ($terminalsJson as $key => $terminal) {
			if ($terminal->nfqactive == 1) {
				$terminals[$terminal->city][] = array(
					'terminal_id'       => $terminal->oxid,
					'zip'               => $terminal->zip,
					'name'              => $terminal->name,
					'city'              => $terminal->city,
					'address'           => $terminal->address,
					'comment'           => $terminal->comment,
					'collectinghours'   => $terminal->collectinghours
				);
			}
		}

		$this->saveTerminalsToCache($terminals);
		return $terminals;
	}

	/**
	 * Retrieve and return (if exists) array of terminals from Configuration table.
	 *
	 * @return array|null
	 */
	public function getTerminalsFromCache()
	{
		if (Configuration::hasKey('LPEXPRESS_TERMINALS')) {
			// If terminals' list is older than a week, delete them
			if (!Configuration::hasKey('LPEXPRESS_TERMINALS_LAST_UPDATE')  || (time() - Configuration::get('LPEXPRESS_TERMINALS_LAST_UPDATE')) > 604800) {
				Configuration::deleteByName('LPEXPRESS_TERMINALS');
				Configuration::deleteByName('LPEXPRESS_TERMINALS_LAST_UPDATE');

				return null;
			} else {
				return json_decode(Configuration::get('LPEXPRESS_TERMINALS'), true);
			}
		}
		return null;
	}

	/**
	 * Save array of terminals to Configuration table.
	 *
	 * @param $terminals
	 *
	 * @return void
	 */
	public function saveTerminalsToCache($terminals)
	{
		Configuration::updateValue('LPEXPRESS_TERMINALS', json_encode($terminals));
		Configuration::updateValue('LPEXPRESS_TERMINALS_LAST_UPDATE', time());
	}

	/**
	 * Return terminal's array by it's ID.
	 *
	 * @param $terminal_id int
	 *
	 * @return null|array
	 */
	public function getTerminalInfoByTerminalID($terminal_id) {
		$terminals = $this->getTerminals();
		foreach ($terminals as $terminal_group) {
			foreach ($terminal_group as $terminal) {
				if ((int)$terminal['terminal_id'] === (int)$terminal_id) {
					return $terminal;
				}
			}
		}
		return null;
	}

	/**
	 * Return terminal's array by order's cart id.
	 *
	 * @param $cart_id Order's cart id
	 *
	 * @return array|null
	 */
	public function getTerminalInfoByCartID($cart_id) {
		$sql = 'SELECT id_terminal FROM ' . _DB_PREFIX_ . 'lpexpress_terminal_for_cart WHERE id_cart=' . $cart_id;
		if ($row = Db::getInstance()->getRow($sql)) {
			$terminal_id = $row['id_terminal'];
			if ($terminal = $this->getTerminalInfoByTerminalID($terminal_id)) {
				return $terminal;
			}
		}
		return null;
	}

	/**
	 * Hook which called when updating carrier info.
	 * Carrier ID will be changed after every update.
	 *
	 * @param $params
	 *
	 * @return void
	 */
	public function hookUpdateCarrier($params)
	{
		$id_carrier_old = (int)($params['id_carrier']);
		$id_carrier_new = (int)($params['carrier']->id);

		// Update the id for carrier 1
		if ($id_carrier_old == (int)(Configuration::get('LPEXPRESS1_CARRIER_ID')))
			Configuration::updateValue('LPEXPRESS1_CARRIER_ID', $id_carrier_new);

		// Update the id for carrier 2
		if ($id_carrier_old == (int)(Configuration::get('LPEXPRESS2_CARRIER_ID')))
			Configuration::updateValue('LPEXPRESS2_CARRIER_ID', $id_carrier_new);
	}

	/**
	 * Hook which called when customer changes carrier.
	 *
	 * @param $params
	 *
	 * @return mixed
	 */
	public function hookDisplayCarrierExtraContent($params)
	{
		if($params['carrier']['id'] == Configuration::get('LPEXPRESS2_CARRIER_ID')) {
			$this->smarty->assign('terminals', $this->getTerminals());
			return $this->fetch('module:lpexpress/views/templates/hook/terminals-list.tpl');
		}
	}

	/**
	 * Hook which called when customer going to next step in checkout.
	 *
	 *
	 * @param $params
	 *
	 * @return void
	 */
	public function hookActionValidateStepComplete($params)
	{
		// Make sure it's delivery step
		if ($params['step_name'] != 'delivery') {
			return;
		}

		// Check if it's shipping method with terminals
		if($params['cart']->id_carrier != Configuration::get('LPEXPRESS2_CARRIER_ID')) {
			return;
		}

		if (!isset($params['request_params']['lpexpress_terminal_id']) || !$params['request_params']['lpexpress_terminal_id']) {
			$controller           = $this->context->controller;
			$controller->errors[] = $this->l('Please select a parcel terminal!');
			$params['completed']  = false;
		} elseif (!$this->getTerminalInfoByTerminalID($params['request_params']['lpexpress_terminal_id'])) {
			$controller           = $this->context->controller;
			$controller->errors[] = $this->l('Please choose correct parcel terminal!');
			$params['completed']  = false;
		} else {
			Db::getInstance()->insert('lpexpress_terminal_for_cart', array(
				'id_cart'       => $params['cart']->id,
				'id_terminal'   => (int)$params['request_params']['lpexpress_terminal_id'],
			));
		}
	}

	/**
	 * Hook which called when customer viewing his order details.
	 *
	 * @param $params
	 *
	 * @return string Smarty generated html
	 */
	public function hookDisplayOrderDetail($params)
	{
		$terminal = $this->getTerminalInfoByCartID($params['order']->id_cart);

		if ($terminal) {
			$this->smarty->assign('terminal', $terminal);
			return $this->fetch('module:lpexpress/views/templates/hook/order-details.tpl');
		}
	}

	/**
	 * Hook which called when showing page after successfully placed order.
	 *
	 * @param $params
	 *
	 * @return string Smarty generated html
	 */
	public function hookDisplayOrderConfirmation($params)
	{
		$terminal = $this->getTerminalInfoByCartID($params['order']->id_cart);

		if ($terminal) {
			$this->smarty->assign('terminal', $terminal);
			return $this->fetch('module:lpexpress/views/templates/hook/order-confirmation.tpl');
		}
	}

	/**
	 * Hook called when sending email.
	 * Will add extra variables to email templates.
	 *
	 * @param $params
	 *
	 * @return void
	 */
	public function hookActionGetExtraMailTemplateVars($params)
	{
		// Assigning empty values for emails without terminal's info
		$params['extra_template_vars'] = array(
			'{lpexpress_terminal_html}' => '',
			'{lpexpress_terminal_txt}'  => '',
		);

		$terminal = $this->getTerminalInfoByCartID($params['cart']->id);

		if ($terminal) {
			$this->smarty->assign('terminal', $terminal);
			$params['extra_template_vars'] = array(
				'{lpexpress_terminal_html}' => $this->fetch('module:lpexpress/views/templates/hook/email-html.tpl'),
				'{lpexpress_terminal_txt}'  => $this->fetch('module:lpexpress/views/templates/hook/email-txt.tpl'),
			);
		}
	}

	/**
	 * Hook which called in admin order's page.
	 * Showing which terminal is chosen by customer.
	 *
	 * Possible other position for this block: displayAdminOrderContentShip but then also needs displayAdminOrderTabShip
	 *
	 * @param $params
	 *
	 * @return string HTML
	 */
	public function hookDisplayAdminOrder($params)
	{
		$terminal = $this->getTerminalInfoByCartID($params['cart']->id);

		if ($terminal) {
			$this->smarty->assign('terminal', $terminal);
			return $this->display(__FILE__,'views/templates/hook/admin-order.tpl');
		}
	}
}
