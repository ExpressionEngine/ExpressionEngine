<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
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
		'consent_request_version_id' => 'int',
		'double_opt_in'              => 'boolString',
		'user_created'               => 'boolString',
	];

	protected static $_relationships = [
		'CurrentVersion' => [
			'type' => 'belongsTo',
			'model' => 'ConsentRequestVersion',
			'from_key' => 'consent_request_version_id'
		],
		'Versions' => [
			'type' => 'hasMany',
			'model' => 'ConsentRequestVersion'
		],
		'Consents' => [
			'type' => 'hasMany',
			'model' => 'Consent'
		],
		'Logs' => [
			'type' => 'hasMany',
			'model' => 'ConsentAuditLog'
		],
	];

	protected static $_validation_rules = [
		'user_created'  => 'enum[y,n]',
		'title'         => 'required|maxLength[200]|limitHtml[b,cite,code,del,em,i,ins,markspan,strong,sub,sup]',
		'consent_name'  => 'required|unique|maxLength[50]|validateName[user_created]',
		'double_opt_in' => 'enum[y,n]',
	];

	// protected static $_events = [];

	// Properties
	protected $consent_request_id;
	protected $consent_request_version_id;
	protected $user_created;
	protected $title;
	protected $consent_name;
	protected $double_opt_in;
	protected $retention_period;

	public function validateName($name, $value, $params, $object)
	{
		$user_created = $params[0];

		$pattern = "-a-z0-9_-";

		if ($user_created == 'n')
		{
			$pattern .= ':';
		}

		if (preg_match("/^([" . $pattern . "])+$/i", $value))
		{
			return TRUE;
		}

		return 'alpha_dash';
	}

	public function render()
	{
		if ( ! $this->CurrentVersion)
		{
			return '';
		}

		return $this->CurrentVersion->render();
	}

}

// EOF
