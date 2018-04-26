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
 * Consent Model
 */
class Consent extends Model {

	protected static $_primary_key = 'consent_id';
	protected static $_table_name = 'consents';

	protected static $_typed_columns = [
		'consent_id'                 => 'int',
		'consent_request_version_id' => 'int',
		'member_id'                  => 'int',
		'consent_given'              => 'boolString',
		'expiration_date'            => 'timestamp',
		'update_date'                => 'timestamp',
		'withdrawn_date'             => 'timestamp',
	];

	protected static $_relationships = [
		'ConsentRequestVersion' => [
			'type' => 'belongsTo'
		],
		'Member' => [
			'type' => 'belongsTo'
		]
	];

	protected static $_validation_rules = [
		'consent_id'                 => 'required',
		'consent_request_version_id' => 'required',
		'member_id'                  => 'required',
		'consent_given'              => 'enum[y,n]',
	];

	// protected static $_events = [];

	// Properties
	protected $consent_id;
	protected $consent_request_version_id;
	protected $member_id;
	protected $request_copy;
	protected $request_format;
	protected $consent_given;
	protected $consent_given_via;
	protected $expiration_date;
	protected $update_date;
	protected $withdrawn_date;

}

// EOF
