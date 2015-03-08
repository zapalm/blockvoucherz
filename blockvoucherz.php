<?php
/**
 * Block Voucherz: module for PrestaShop 1.2-1.6
 *
 * @author zapalm <zapalm@ya.ru>
 * @copyright (c) 2011-2015, zapalm
 * @link http://prestashop.modulez.ru/en/ The module's homepage
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

class BlockVoucherz extends Module
{
	public function __construct()
	{
		$this->name = 'blockvoucherz';
		$this->tab = 'Tools';
		$this->version = '1.1.0';
		$this->author = 'zapalm';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.2.0.0', 'max' => '1.6.1.0');
		$this->bootstrap = false;

		parent::__construct();

		$this->displayName = $this->l('Voucher block');
		$this->description = $this->l('Allows to sell gift vouchers.');
	}

	public function install()
	{
		return parent::install()
			&& $this->registerHook('rightColumn')
			&& Configuration::updateValue('BLOCKVOUCHERZ_START', '')
			&& Configuration::updateValue('BLOCKVOUCHERZ_DAYS', 0)
			&& Configuration::updateValue('BLOCKVOUCHERZ_CAT', 1)
			&& Configuration::updateValue('BLOCKVOUCHERZ_NAME', 'Gift voucher')
			&& $this->createTables();
	}

	public function uninstall()
	{
		// @todo: should not be deleted because of lack the procedure of checking
		Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.'voucherz`');

		return parent::uninstall()
			&& Configuration::deleteByName('BLOCKVOUCHERZ_START')
			&& Configuration::deleteByName('BLOCKVOUCHERZ_DAYS')
			&& Configuration::deleteByName('BLOCKVOUCHERZ_CAT')
			&& Configuration::deleteByName('BLOCKVOUCHERZ_NAME');
	}

	public function getContent()
	{
		$output = '';

		if (Tools::isSubmit('submit_save'))
		{
			$res = Configuration::updateValue('BLOCKVOUCHERZ_START', date('Y-m-d'))
				&& Configuration::updateValue('BLOCKVOUCHERZ_DAYS', intval(Tools::getValue('days')))
				&& Configuration::updateValue('BLOCKVOUCHERZ_CAT', Tools::getValue('cat'));

			$output .= $res ? $this->displayConfirmation($this->l('Settings updated')) : $this->displayError($this->l('Some setting not updated'));
		}

		$output .= '
			<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
				<fieldset>
					<legend><img src="'._PS_ADMIN_IMG_.'cog.gif" alt="" title="" />'.$this->l('Settings').'</legend>
					<label>'.$this->l('Days').'</label>
					<div class="margin-form">
						<input type="text" name="days" value="'.(int)Configuration::get('BLOCKVOUCHERZ_DAYS').'" />
						<p class="clear">'.$this->l('Number of day').'</p>
					</div>
					<label>'.$this->l('Category').'</label>
					<div class="margin-form">
						<input type="text" name="cat" value="'.(int)Configuration::get('BLOCKVOUCHERZ_CAT').'" />
						<p class="clear">'.$this->l('Category with vouchers').'</p>
					</div>
					<center><input type="submit" name="submit_save" value="'.$this->l('Save').'" class="button" /></center>
				</fieldset>
			</form>
			<br class="clear" />
		';

		return $output;
	}

	public function hookRightColumn($params)
	{
		global $smarty, $link, $cookie;

		$smarty->assign(array(
			'sales' => $this->getSales(),
			'days' => Configuration::get('BLOCKVOUCHERZ_DAYS'),
			'start' => Configuration::get('BLOCKVOUCHERZ_START'),
			'cat' => $link->getCategoryLink((int)Configuration::get('BLOCKVOUCHERZ_CAT'), null, (int)$cookie->id_lang)
		));

		return $this->display(__FILE__, 'blockvoucherz.tpl');
	}

	public function hookLeftColumn($params)
	{
		return $this->hookRightColumn($params);
	}

	private function createTables()
	{
		if (!defined('_MYSQL_ENGINE_'))
			define('_MYSQL_ENGINE_', 'MyISAM');

		$sql = '
			CREATE TABLE `'._DB_PREFIX_.'voucherz` (
			  `id_voucher` int(10) unsigned NOT NULL auto_increment,
			  `id_order_detail` int(11) NOT NULL,
			  `firstname` varchar(32) NOT NULL,
			  `lastname` varchar(32) NOT NULL,
			  `voucher` varchar(10) NOT NULL,
			  PRIMARY KEY  (`id_voucher`),
			  UNIQUE KEY `id_order_detail` (`id_order_detail`)
			) ENGINE='._MYSQL_ENGINE_.' AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;';

		return Db::getInstance()->execute($sql);
	}

	private function getSales()
	{
		global $cookie;

		$voucher_name = Configuration::get('BLOCKVOUCHERZ_NAME');
		$id_vouchers_cat = (int)Configuration::get('BLOCKVOUCHERZ_CAT');

		// this sql for checking buyed vouchers
		// @todo: optimize the procedure of checking
		$sql = 'SELECT
			o.`id_order`,
			o.`id_customer`,
			o.`date_add`,
			od.`id_order_detail`,
			od.`product_quantity`,
			od.`product_price`,
		    c.`firstname`,
		    c.`lastname`,
			c.`email`
			FROM `'._DB_PREFIX_.'orders` o
			LEFT JOIN `'._DB_PREFIX_.'order_detail` od ON od.`id_order`=o.`id_order`
			LEFT JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer`=o.`id_customer`
			WHERE od.`id_order_detail` NOT IN (SELECT v.`id_order_detail` FROM `'._DB_PREFIX_.'voucherz` v)
				AND od.`product_id` IN (SELECT p.`id_product` FROM `'._DB_PREFIX_.'product` p WHERE p.`id_category_default`='.$id_vouchers_cat.')
				AND o.`id_customer`='.(int)$cookie->id_customer;

		$orders = Db::getInstance()->executeS($sql);

		if ($orders)
		{
			$categories = Db::getInstance()->executeS('SELECT `id_category` FROM `'._DB_PREFIX_.'category` WHERE `id_category` <> '.$id_vouchers_cat);
			foreach ($categories as $category)
				$cats[] = $category['id_category'];

			$languages = Language::getLanguages(true);
			foreach ($orders as $order)
			{
				// create a voucher for each product from order
				for ($i = 0; $i < (int)$order['product_quantity']; $i++)
				{
					$discount = new Discount();
					$discount->id_discount_type = 2;
					$discount->value = floatval($order['product_price']);
					$discount->id_customer = 0;
					$discount->id_currency = $cookie->id_currency;
					$discount->date_to = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m') + 3, date('d'), date('Y')));
					$discount->date_from = date('Y-m-d H:i:s');
					$discount->quantity = 1;
					$discount->quantity_per_user = 1;
					$discount->cumulable = 0;
					$discount->cumulable_reduction = 0;
					$discount->minimal = 0;

					foreach ($languages as $language)
						$discount->description[intval($language['id_lang'])] = $voucher_name;

					$discount->name = strtoupper(Tools::passwdGen(10));
					$discount->active = 1;
					$discount->add(false, false, $cats);

					// add the vaucher to the history
					Db::getInstance()->execute('
						INSERT INTO `'._DB_PREFIX_.'voucherz`
						(
							`id_voucher`,
							`id_order_detail`,
							`firstname`,
							`lastname`,
							`voucher`
						)
						VALUES
						(
							null,
							'.$order['id_order_detail'].',
							"'.$order['firstname'].'",
							"'.$order['lastname'].'",
							"'.$discount->name.'"
						);
					');

					// send an email with the voucher to the customer
					Mail::Send(
						(int)$cookie->id_lang,
						'voucher',
						'Your voucher',
						array(
							'{email}' => $order['email'],
							'{lastname}' => $order['lastname'],
							'{firstname}' => $order['firstname'],
							'{id_order}' => $order['id_order'],
							'{order_name}' => $order['id_order'],
							'{voucher_num}' => $discount->name,
							'{voucher_amount}' => $discount->value,
						),
						$order['email'],
						$order['firstname'].' '.$order['lastname']
					);

					unset($discount);
				}
			}
		}

		// buyed vouchres count
		$sql = '
			SELECT COUNT(d.`id_discount`) as `count`
			FROM `'._DB_PREFIX_.'discount` d
			LEFT JOIN `'._DB_PREFIX_.'discount_lang` dl ON dl.`id_discount`=d.`id_discount` AND dl.`id_lang`='.(int)$cookie->id_lang.'
			WHERE dl.`description`="'.$voucher_name.'"';

		$sales = (int)Db::getInstance()->getValue($sql);

		return $sales;
	}
}