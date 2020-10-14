<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Queue_upd {

	public $version = '1.0';

	/**
	 * installs module
	 * @return boolean
	 */
	public function install()
	{

		$data = [
			'module_name'			=> 'Queue',
			'module_version'		=> $this->version,
			'has_cp_backend'		=> 'n',
			'has_publish_fields'	=> 'n'
		];

		ee()->db->insert('modules', $data);

		// Create jobs tables
		$this->addJobsTable();
		$this->addFailedJobsTable();

		return true;

	}

	/**
	 * updates modules
	 * @param  string $current [current version of EE]
	 * @return boolean
	 */
	public function update($current = '')
	{

		return true;

	}

	/**
	 * uninstalls module
	 * @return boolean
	 */
	public function uninstall()
	{

		ee()->load->dbforge();

		ee()->db->where('module_name', 'Queue');

		ee()->db->delete('modules');

		ee()->dbforge->drop_table('queue_jobs', true);
		ee()->dbforge->drop_table('queue_failed_jobs', true);

		return true;

	}

	/**
	 * Installs jobs table
	 * @return  boolean
	 */
	private function addJobsTable()
	{

		ee()->load->dbforge();

		if( ! ee()->db->table_exists('queue_jobs') )
		{

		    ee()->dbforge->add_field(
		        [
		            'job_id'			=> [
		                'type'              => 'bigint',
		                'constraint'        => 20,
		                'unsigned'          => true,
		                'auto_increment'    => true,
		            ],
		            'payload'           => [
		                'type'              => 'longtext',
		            ],
		            'attempts'          => [
		                'type'              => 'tinyint',
		                'constraint'        => 3,
		                'unsigned'          => true,
		                'default'			=> 0,
		            ],
		            'run_at datetime',
		            'created_at datetime default current_timestamp',
		        ]
		    );

		    ee()->dbforge->add_key('job_id', true);

		    ee()->dbforge->create_table('queue_jobs');

		}

		return true;

	}

	private function addFailedJobsTable()
	{

		ee()->load->dbforge();

		if( ! ee()->db->table_exists('queue_failed_jobs') )
		{

		    ee()->dbforge->add_field(
		        [
		            'failed_job_id'		=> [
		                'type'              => 'bigint',
		                'constraint'        => 20,
		                'unsigned'          => true,
		                'auto_increment'    => true,
		            ],
		            'payload'           => [
		                'type'              => 'longtext',
		            ],
		            'error'          	=> [
		                'type'              => 'longtext',
		            ],
		            'failed_at datetime default current_timestamp'
		        ]
		    );

		    ee()->dbforge->add_key('failed_job_id', true);

		    ee()->dbforge->create_table('queue_failed_jobs');

		}

		return true;
	}

}