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
		'response_date'              => 'timestamp',
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

	protected static $_events = [
		'afterSave',
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
	protected $response_date;

	/**
	 * Is this consent expired?
	 *
	 * @return bool TRUE if it is, FALSE otherwise.
	 */
	public function isExpired()
	{
		$now = ee()->localize->now;

		if ($this->expiration_date && $this->expiration_date > $now)
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Checks to see if the request version matches, and that it hasn't been edited
	 * since the member responded, and that the consent isn't expired, and that
	 * consent was granted.
	 *
	 * @return bool TRUE if it is, FALSE otherwise.
	 */
	public function isGranted()
	{
		$request = $this->ConsentRequest->CurrentVersion;

		if ( ! $request->getId())
		{
			return FALSE;
		}

		// If the consent is not for the current version of the request, then the consent
		// is void. The request has changed.
		if ($this->ConsentRequestVersion->getId() != $request->getId())
		{
			return FALSE;
		}

		// If the current request version was edited after the consent was granted,
		// then the consent is void. The request has changed.
		if ($request->create_date > $this->response_date)
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

	/**
	 * Adds a record to the Consent Audit Log
	 *
	 * @param string $action The action/log message
	 * @return NULL
	 */
	public function log($action)
	{
		$log = $this->getModelFacade()->make('ConsentAuditLog');
		$log->ConsentRequest = $this->ConsentRequest;
		$log->Member = $this->Member;
		$log->action = $action;
		$log->log_date = ee()->localize->now;
		$log->save();

	}

	public function onAfterSave()
	{
		// make sure date fields are objects, or we'll get fatal errors
		// when isGranted() is called on the same request after a save()
		if (is_int($this->response_date))
		{
			$this->set(['response_date' => new \DateTime("@{$this->response_date}")]);
		}
	}
}

// EOF
