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
 * Consent Request Model
 */
class ConsentRequest extends Model {

	protected static $_primary_key = 'consent_request_id';
	protected static $_table_name = 'consent_requests';

	protected static $_typed_columns = [
		'consent_request_id'         => 'int',
		'site_id'                    => 'int',
		'consent_request_version_id' => 'int',
		'double_opt_in'              => 'boolString',
	];

	protected static $_relationships = [
		'Site' => [
			'type' => 'belongsTo'
		],
		'CurrentVersion' => [
			'type' => 'belongsTo',
			'model' => 'ConsentRequestVersion',
			'from_key' => 'consent_request_version_id'
		],
		'Versions' => [
			'type' => 'hasMany',
			'model' => 'ConsentRequestVersion'
		],
	];

	protected static $_validation_rules = [
		'title'         => 'required|maxLength[200]|limitHtml[b,cite,code,del,em,i,ins,markspan,strong,sub,sup]',
		'url_title'     => 'required|unique|maxLength[URL_TITLE_MAX_LENGTH]|alphaDashPeriodEmoji',
		'double_opt_in' => 'enum[y,n]',
	];

	// protected static $_events = [];

	// Properties
	protected $consent_request_id;
	protected $site_id;
	protected $consent_request_version_id;
	protected $title;
	protected $url_title;
	protected $double_opt_in;
	protected $retention_period;

}

// EOF
