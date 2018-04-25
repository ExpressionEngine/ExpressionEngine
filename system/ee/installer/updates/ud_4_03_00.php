<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'site_id'                    => [
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'consent_request_version_id' => [
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'title'                      => [
					'type'       => 'varchar',
					'constraint' => 200,
					'null'       => FALSE
				],
				'url_title'                  => [
					'type'       => 'varchar',
					'constraint' => URL_TITLE_MAX_LENGTH,
					'null'       => FALSE
				],
				'double_opt_in'              => [
					'type'       => 'varchar',
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
		ee()->dbforge->add_key('site_id');
		ee()->smartforge->create_table('consent_requests');

		ee()->dbforge->add_field(
			[
				'consent_request_version_id' => [
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'consent_request_id'         => [
					'type'       => 'int',
					'constraint' => 4,
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
				'created_on'                 => [
					'type'       => 'int',
					'constraint' => 10,
					'null'       => FALSE,
					'default'    => 0
				],
				'created_by'                 => [
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE,
					'default'    => 0
				],
				'edited_on'                  => [
					'type'       => 'int',
					'constraint' => 10,
					'null'       => FALSE,
					'default'    => 0
				],
				'edited_by'                  => [
					'type'       => 'int',
					'constraint' => 4,
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
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'consent_request_version_id' => [
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'member_id'                  => [
					'type'       => 'int',
					'constraint' => 4,
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
					'type'       => 'varchar',
					'constraint' => 1,
					'null'       => FALSE,
					'default'    => 'n'
				],
				'consent_given_via'          => [
					'type'       => 'varchar',
					'constraint' => 32,
					'null'       => TRUE
				],
				'expires_on'                 => [
					'type'       => 'int',
					'constraint' => 10,
					'null'       => TRUE
				],
				'updated_on'                 => [
					'type'       => 'int',
					'constraint' => 10,
					'null'       => TRUE
				],
				'withdrawn_on'               => [
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
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'consent_request_id' => [
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'member_id'          => [
					'type'       => 'int',
					'constraint' => 4,
					'unsigned'   => TRUE,
					'null'       => FALSE
				],
				'action'             => [
					'type'       => 'varchar',
					'constraint' => 200,
					'null'       => FALSE
				],
			]
		);
		ee()->dbforge->add_key('consent_audit_id', TRUE);
		ee()->dbforge->add_key('consent_request_id');
		ee()->smartforge->create_table('consent_audit_log');
	}
}

// EOF
