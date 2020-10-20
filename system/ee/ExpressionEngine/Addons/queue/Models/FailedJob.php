<?php

namespace ExpressionEngine\Addons\Queue\Models;

use ExpressionEngine\Service\Model\Model;

class FailedJob extends Model {

	// Documentation: https://docs.expressionengine.com/latest/development/services/model/building-your-own.html
	// You can get this model by using:
	// ee('Model')->get('{slug}:{class}');

	protected static $_primary_key = 'failed_job_id';

	protected static $_table_name = 'queue_failed_jobs';

	// Add your properties as protected variables here
	protected $failed_job_id;
	protected $payload;
	protected $error;
	protected $failed_at;

}