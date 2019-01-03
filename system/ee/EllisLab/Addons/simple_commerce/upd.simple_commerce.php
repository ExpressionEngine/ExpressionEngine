<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Simple Commerce Module update class
 */
class Simple_commerce_upd {

	var $version			= '2.2.0';

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	function install()
	{
		$sql[] = "INSERT INTO exp_modules
				  (module_name, module_version, has_cp_backend)
				  VALUES
				  ('Simple_commerce', '$this->version', 'y')";

		$sql[] = "INSERT INTO exp_actions (class, method, csrf_exempt) VALUES ('Simple_commerce', 'incoming_ipn', 1)";

		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_simple_commerce_items` (
  `item_id` int(8) unsigned NOT NULL auto_increment,
  `entry_id` int(8) unsigned NOT NULL,
  `item_enabled` char(1) NOT NULL default 'y',
  `item_regular_price` decimal(7,2) NOT NULL default '0.00',
  `item_sale_price` decimal(7,2) NOT NULL default '0.00',
  `item_use_sale` char(1) NOT NULL default 'n',
  `recurring` char(1) NOT NULL default 'n',
  `subscription_frequency` int(10) unsigned NULL default NULL,
  `subscription_frequency_unit` varchar(10) NULL default NULL,
  `item_purchases` int(8) NOT NULL default '0',
  `current_subscriptions` int(8) NOT NULL default '0',
  `new_member_group` int(8) default '0',
  `member_group_unsubscribe` int(8) default '0',
  `admin_email_address` varchar(75) NULL default NULL,
  `admin_email_template` int(5) default '0',
  `customer_email_template` int(5) default '0',
  `admin_email_template_unsubscribe` int(5) default '0',
  `customer_email_template_unsubscribe` int(5) default '0',

  PRIMARY KEY `item_id` (`item_id`),
  KEY `entry_id` (`entry_id`)
) DEFAULT CHARACTER SET ".ee()->db->escape_str(ee()->db->char_set)." COLLATE ".ee()->db->escape_str(ee()->db->dbcollat);

		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_simple_commerce_purchases` (
  `purchase_id` int(8) unsigned NOT NULL auto_increment,
  `txn_id` varchar(20) NOT NULL default '',
  `member_id` varchar(50) NOT NULL default '',
  `paypal_subscriber_id` VARCHAR(100) NULL default NULL,
  `item_id` int(8) unsigned NOT NULL default '0',
  `purchase_date` int(12) unsigned NOT NULL default '0',
  `item_cost` decimal(10,2) NOT NULL default '0.00',
  `paypal_details` TEXT	NULL default NULL,
  `subscription_end_date` INT(10) unsigned NOT NULL default '0',
  PRIMARY KEY `purchase_id` (`purchase_id`),
  KEY `item_id` (`item_id`),
  KEY `member_id` (`member_id`),
  KEY `txn_id` (`txn_id`)
) DEFAULT CHARACTER SET ".ee()->db->escape_str(ee()->db->char_set)." COLLATE ".ee()->db->escape_str(ee()->db->dbcollat);

		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_simple_commerce_emails` (
  `email_id` int(8) unsigned NOT NULL auto_increment,
  `email_name` varchar(50) NOT NULL default '',
  `email_subject` varchar(125) NOT NULL default '',
  `email_body` text NOT NULL,
  PRIMARY KEY `email_id` (`email_id`)
) DEFAULT CHARACTER SET ".ee()->db->escape_str(ee()->db->char_set)." COLLATE ".ee()->db->escape_str(ee()->db->dbcollat);


		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		// update the config file based on whether this install is from the CP or the install wizard
		if (method_exists(ee()->config, 'divination'))
		{
			ee()->config->_update_config(array('sc_paypal_account' 	=> '',
											'sc_encrypt_buttons' 	=> 'n',
											'sc_certificate_id'		=> '',
											'sc_public_certificate' => '',
											'sc_private_key'		=> '',
											'sc_paypal_certificate' => '',
											'sc_temp_path'			=> '/tmp'));
		}
		else
		{
			ee()->config->_assign_to_config(array('sc_paypal_account' 	=> '',
												'sc_encrypt_buttons' 	=> 'n',
												'sc_certificate_id'		=> '',
												'sc_public_certificate' => '',
												'sc_private_key'		=> '',
												'sc_paypal_certificate' => '',
												'sc_temp_path'			=> '/tmp'));
		}



		return TRUE;
	}



	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Simple_commerce'");

		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Simple_commerce'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Simple_commerce'";
		$sql[] = "DROP TABLE IF EXISTS exp_simple_commerce_items";
		$sql[] = "DROP TABLE IF EXISTS exp_simple_commerce_purchases";
		$sql[] = "DROP TABLE IF EXISTS exp_simple_commerce_emails";


		foreach ($sql as $query)
		{
			ee()->db->query($query);
		}

		/** ----------------------------------------
		/**  Remove a couple items to the config file
		/** ----------------------------------------*/

		ee()->config->_update_config('', array('sc_paypal_account' => '',
											'sc_encrypt_buttons' => '',
											'sc_certificate_id' => '',
											'sc_public_certificate' => '',
											'sc_private_key' => '',
											'sc_paypal_certificate' => '',
											'sc_temp_path' => ''));

		return TRUE;
	}


	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current = '')
	{
		ee()->load->dbforge();

		if (version_compare($current, '2.0', '<'))
		{
			ee()->db->query("ALTER TABLE `exp_simple_commerce_purchases` CHANGE `paypal_details` `paypal_details` TEXT NULL DEFAULT NULL");


			ee()->db->query("ALTER TABLE `exp_simple_commerce_items` ADD COLUMN `recurring` char(1) NOT NULL default 'n'");
			ee()->db->query("ALTER TABLE `exp_simple_commerce_items` ADD COLUMN `subscription_frequency` int(10) unsigned NULL default NULL");
			ee()->db->query("ALTER TABLE `exp_simple_commerce_items` ADD COLUMN `subscription_frequency_unit` varchar(10) NULL default NULL");
			ee()->db->query("ALTER TABLE `exp_simple_commerce_items` ADD COLUMN `current_subscriptions` int(8) NOT NULL default '0'");
			ee()->db->query("ALTER TABLE `exp_simple_commerce_items` ADD COLUMN `admin_email_template_unsubscribe`  int(5) default '0'");
			ee()->db->query("ALTER TABLE `exp_simple_commerce_items` ADD COLUMN `customer_email_template_unsubscribe`  int(5) default '0'");
			ee()->db->query("ALTER TABLE `exp_simple_commerce_purchases` ADD COLUMN `subscription_end_date`  int(10) NOT NULL default '0'");

		}

		if (version_compare($current, '2.1', '<'))
		{
			// This was left out of update, but added to install
			if ( ! ee()->db->field_exists('member_group_unsubscribe', 'simple_commerce_items'))
			{
				$details = array('member_group_unsubscribe' => array(
					'type' => 'INT',
					'constraint' => 8,
					'default' => 0
				));

				ee()->dbforge->add_column('simple_commerce_items', $details, 'new_member_group');
			}
		}

		if (version_compare($current, '2.2', '<'))
		{
			$query = ee()->db->select('t.title, i.admin_email_address')
				->from('simple_commerce_items i')
				->join('channel_titles t', 't.entry_id = i.entry_id')
				->where('LENGTH(i.admin_email_address) >', 75)
				->get();

			if ($query->row('count') > 0)
			{
				ee()->load->library('logger');
				foreach ($query->result() as $item)
				{
					ee()->logger->developer('The admin email address for "'.$item->title.'" was truncated.  Original address was "'.$item->admin_email_address.'".');
				}
			}

			ee()->dbforge->modify_column(
				'simple_commerce_items',
				array(
					'admin_email_address' => array(
						'name' 			=> 'admin_email_address',
						'type' 			=> 'varchar',
						'constraint'	=> '75',
						'null'			=> TRUE,
						'default'		=> NULL
					)
				)
			);

			$data = array(
				'csrf_exempt' => 1
				);

			ee()->db->where('class', 'Simple_commerce');
			ee()->db->where('method', 'incoming_ipn');
			ee()->db->update('actions', $data);
		}

		if (version_compare($current, '2.2.1', '<'))
		{
			ee('Model')->make('Extension', [
				'class'    => 'Simple_commerce_ext',
				'method'   => 'anonymizeMember',
				'hook'     => 'member_anonymize',
				'settings' => [],
				'version'  => $current,
				'enabled'  => 'y'
			])->save();
		}

		return TRUE;
	}
}

// EOF
