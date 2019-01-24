<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_4_3_0;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$steps = new \ProgressIterator(
			[
				'addConsentTables',
				'addConsentModerationPermissions',
				'addMemberFieldAnonExcludeColumn',
				'installConsentModule',
				'addSessionAuthTimeoutColumn',
			]
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	private function addConsentTables()
	{
		ee()->dbforge->add_field(
			[
				'consent_request_id'         => [
					'type'           => 'int',
					'constraint'     => 10,
					'unsigned'       => TRUE,
					'null'           => FALSE,
					'auto_increment' => TRUE
				],
				'consent_request_version_id' => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => TRUE,
					'default'    => NULL
				],
				'user_created'               => [
					'type'       => 'char',
					'constraint' => 1,
					'null'       => FALSE,
					'default'    => 'n',
				],
				'title'                      => [
					'type'       => 'varchar',
					'constraint' => 200,
					'null'       => FALSE
				],
				'consent_name'               => [
					'type'       => 'varchar',
					'constraint' => 50,
					'null'       => FALSE
				],
				'double_opt_in'              => [
					'type'       => 'char',
					'constraint' => 1,
					'null'       => FALSE,
					'default'    => 'n'
				],
				'retention_period'           => [
					'type'       => 'varchar',
					'constraint' => 32,
					'null'       => TRUE,
				],
			]
		);
		ee()->dbforge->add_key('consent_request_id', TRUE);
		ee()->dbforge->add_key('consent_name');
		ee()->smartforge->create_table('consent_requests');

		ee()->dbforge->add_field(
			[
				'consent_request_version_id' => [
					'type'           => 'int',
					'constraint'     => 10,
					'unsigned'       => TRUE,
					'null'           => FALSE,
					'auto_increment' => TRUE
				],
				'consent_request_id'         => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'request'                    => [
					'type'       => 'mediumtext',
					'null'       => TRUE
				],
				'request_format'             => [
					'type'       => 'tinytext',
					'null'       => TRUE
				],
				'create_date'                => [
					'type'       => 'int',
					'constraint' => 10,
					'null'       => FALSE,
					'default'    => 0
				],
				'author_id'                  => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE,
					'default'    => 0
				],
			]
		);
		ee()->dbforge->add_key('consent_request_version_id', TRUE);
		ee()->dbforge->add_key('consent_request_id');
		ee()->smartforge->create_table('consent_request_versions');

		ee()->dbforge->add_field(
			[
				'consent_id'                 => [
					'type'           => 'int',
					'constraint'     => 10,
					'unsigned'       => TRUE,
					'null'           => FALSE,
					'auto_increment' => TRUE
				],
				'consent_request_id' => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'consent_request_version_id' => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'member_id'                  => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'request_copy'               => [
					'type'       => 'mediumtext',
					'null'       => TRUE
				],
				'request_format'             => [
					'type'       => 'tinytext',
					'null'       => TRUE
				],
				'consent_given'              => [
					'type'       => 'char',
					'constraint' => 1,
					'null'       => FALSE,
					'default'    => 'n'
				],
				'consent_given_via'          => [
					'type'       => 'varchar',
					'constraint' => 32,
					'null'       => TRUE
				],
				'expiration_date'            => [
					'type'       => 'int',
					'constraint' => 10,
					'null'       => TRUE
				],
				'response_date'              => [
					'type'       => 'int',
					'constraint' => 10,
					'null'       => TRUE
				],
			]
		);
		ee()->dbforge->add_key('consent_id', TRUE);
		ee()->dbforge->add_key('consent_request_version_id');
		ee()->dbforge->add_key('member_id');
		ee()->smartforge->create_table('consents');

		ee()->dbforge->add_field(
			[
				'consent_audit_id'   => [
					'type'           => 'int',
					'constraint'     => 10,
					'unsigned'       => TRUE,
					'null'           => FALSE,
					'auto_increment' => TRUE
				],
				'consent_request_id' => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'member_id'          => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'action'             => [
					'type'       => 'text',
					'null'       => FALSE
				],
				'log_date'           => [
					'type'       => 'int',
					'constraint' => 10,
					'null'       => FALSE,
					'default'    => 0
				],
			]
		);
		ee()->dbforge->add_key('consent_audit_id', TRUE);
		ee()->dbforge->add_key('consent_request_id');
		ee()->smartforge->create_table('consent_audit_log');
	}

	private function addConsentModerationPermissions()
	{
		ee()->smartforge->add_column(
			'member_groups',
			array(
				'can_manage_consents' => array(
					'type'       => 'CHAR',
					'constraint' => 1,
					'default'    => 'n',
					'null'       => FALSE,
				)
			)
		);

		// Only assume super admins can moderate consent requests
		ee()->db->update('member_groups', array('can_manage_consents' => 'y'), array('group_id' => 1));
	}

	private function addMemberFieldAnonExcludeColumn()
	{
		ee()->smartforge->add_column(
			'member_fields',
			[
				'm_field_exclude_from_anon' => [
					'type'    => 'CHAR(1)',
					'null'    => FALSE,
					'default' => 'n'
				]
			],
			'm_field_show_fmt'
		);
	}

	private function installConsentModule()
	{
		$addon = ee('Addon')->get('consent');

		if ( ! $addon OR ! $addon->isInstalled())
		{
			ee()->load->library('addons');
			ee()->addons->install_modules(['consent']);

			try
			{
				$addon = ee('Addon')->get('consent');
				$addon->installConsentRequests();
			}
			catch (\Exception $e)
			{
				// probably just ran the update again
			}
		}
	}

	private function addSessionAuthTimeoutColumn()
	{
		ee()->smartforge->add_column(
			'sessions',
			[
				'auth_timeout' => [
					'type'       => 'int',
					'constraint' => 10,
					'unsigned'   => TRUE,
					'null'       => FALSE,
					'default'    => 0
				]
			],
			'sess_start'
		);

	}
}

// EOF
