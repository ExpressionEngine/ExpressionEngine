<?php

namespace ExpressionEngine\Addons\Queue\Models;

use ExpressionEngine\Service\Model\Model;

class Job extends Model {

	// Documentation: https://docs.expressionengine.com/latest/development/services/model/building-your-own.html
	// You can get this model by using:
	// ee('Model')->get('{slug}:{class}');

	protected static $_primary_key = 'job_id';

	protected static $_table_name = 'queue_jobs';

	// Add your properties as protected variables here
	protected $job_id;
	protected $payload;
	protected $attempts;
	protected $run_at;
	protected $created_at;

	/**
	 * return decoded payload
	 * @return stdClass
	 */
	public function payload()
	{
		return json_decode($this->payload);
	}

}