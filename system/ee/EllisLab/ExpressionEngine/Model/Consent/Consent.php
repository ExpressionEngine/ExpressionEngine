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
		'consent_request_id'         => 'int',
		'consent_request_version_id' => 'int',
		'member_id'                  => 'int',
		'consent_given'              => 'boolString',
		'expiration_date'            => 'timestamp',
		'update_date'                => 'timestamp',
		'withdrawn_date'             => 'timestamp',
	];

	protected static $_relationships = [
		'ConsentRequest' => [
			'type' => 'belongsTo'
		],
		'ConsentRequestVersion' => [
			'type' => 'belongsTo'
		],
		'Member' => [
			'type' => 'belongsTo'
		]
	];

	protected static $_validation_rules = [
		'consent_id'                 => 'required',
		'consent_request_id'         => 'required',
		'consent_request_version_id' => 'required',
		'member_id'                  => 'required',
		'consent_given'              => 'enum[y,n]',
	];

	// protected static $_events = [];

	// Properties
	protected $consent_id;
	protected $consent_request_id;
	protected $consent_request_version_id;
	protected $member_id;
	protected $request_copy;
	protected $request_format;
	protected $consent_given;
	protected $consent_given_via;
	protected $expiration_date;
	protected $update_date;
	protected $withdrawn_date;

	public function isExpired()
	{
		$now = ee()->localize->now;

		if ($this->expiration_date && $this->expiration_date > $now)
		{
			return TRUE;
		}

		return FALSE;
	}

	public function isGranted()
	{
		$request = $this->ConsentRequest->CurrentVersion;

		// If the consent is not for the current version of the request, then the consent
		// is void. The request has changed.
		if ($this->ConsentRequestVersion->getId() != $request->getId())
		{
			return FALSE;
		}

		// If the current request version was edited after the consent was granted,
		// then the consent is void. The request has changed.
		if ($request->edit_date > $this->updated_date)
		{
			return FALSE;
		}

		// If the consent has expired it's no longer granted
		if ($this->isExpired())
		{
			return FALSE;
		}

		return $this->getProperty('consent_given');
	}

	public function log($action)
	{
		$log = $this->getModelFacade()->make('ConsentAuditLog');
		$log->ConsentRequest = $this->ConsentRequest;
		$log->Member = $this->member;
		$log->action = $action;
		$log->log_date = ee()->localize->now;
		$log->save();

	}
}

// EOF
