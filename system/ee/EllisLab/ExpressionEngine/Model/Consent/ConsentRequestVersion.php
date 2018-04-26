<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Consent;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Consent Request Version Model
 */
class ConsentRequestVersion extends Model {

	protected static $_primary_key = 'consent_request_version_id';
	protected static $_table_name = 'consent_request_versions';

	protected static $_typed_columns = [
		'consent_request_version_id' => 'int',
		'consent_request_id'         => 'int',
		'created_on'                 => 'timestamp',
		'created_by'                 => 'int',
		'edited_on'                  => 'timestamp',
		'edited_by'                  => 'int',
	];

	protected static $_relationships = [
		'ConsentRequest' => [
			'type' => 'belongsTo',
		]
	];

	protected static $_validation_rules = [
		'created_on' => 'required',
		'created_by' => 'required',
		'edited_on'  => 'required',
		'edited_by'  => 'required',
	];

	// protected static $_events = [];

	// Properties
	protected $consent_request_version_id;
	protected $consent_request_id;
	protected $request;
	protected $request_format;
	protected $created_on;
	protected $created_by;
	protected $edited_on;
	protected $edited_by;

}

// EOF
