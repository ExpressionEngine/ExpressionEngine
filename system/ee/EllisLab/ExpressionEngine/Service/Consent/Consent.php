<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Consent;

use EllisLab\ExpressionEngine\Service\Model\Facade as ModelFacade;
use EllisLab\ExpressionEngine\Model\Member\Member;
use EllisLab\ExpressionEngine\Model\Consent\ConsentRequest;
use InvalidArgumentException;

/**
 * Consent Service
 */
class Consent {

	/**
	 * @var Member $member A Member entity object
	 */
	protected $member;

	/**
	 * @var obj $model_delegate An injected `ee('Model')` object
	 */
	protected $model_delegate;

	/**
	 * @var int $site_id The current site_id
	 */
	protected $site_id;

	/**
	 * @var int $now The current timestamp
	 */
	protected $now;

	public function __construct(ModelFacade $model_delegate, Member $member, $site_id, $now)
	{
		$this->model_delegate = $model_delegate;
		$this->member = $member;
		$this->site_id = $site_id;
		$this->now = $now;
	}

	/**
	 * Creates/updates a consent record for the member for the given consent request
	 *
	 * @param int|string $request_ref The name (url_title) or ID of a consent request
	 * @throws InvalidArgumentException
	 * @return null
	 */
	public function grant($request_ref, $via = 'online_form')
	{
		$request = $this->getConsentRequest($request_ref);
		$consent = $this->getOrMakeConsent($request);
		$consent->consent_given = FALSE;
		$consent->update_date = ee()->localize->now;
		$consent->consent_given_via = $via;
		$consent->save();

		$this->log($request, sprintf(lang('consent_granted_log_msg'), $this->member->getAuthor(), $via));
	}

	/**
	 * Updates a consent record for the member for the given consent request to indicate
	 * that consent has been withdrawn
	 *
	 * @param int|string $request_ref The name (url_title) or ID of a consent request
	 * @throws InvalidArgumentException
	 * @return null
	 */
	public function withdraw($request_ref)
	{
		$request = $this->getConsentRequest($request_ref);
		$consent = $this->getOrMakeConsent($request);
		$consent->consent_given = FALSE;
		$consent->withdrawn_date = $this->now;
		$consent->save();

		$this->log($request, sprintf(lang('consent_withdrawn_log_msg'), $this->member->getAuthor()));
	}

	/**
	 * Has the member granted consent for a given consent request?
	 *
	 * @param int|string $request_ref The name (url_title) or ID of a consent request
	 * @throws InvalidArgumentException
	 * @return bool TRUE if they have, FALSE if they have not
	 */
	public function hasGranted($request_ref)
	{
		try {
			$request = $this->getConsentRequest($request_ref);
		}
		catch (InvalidArgumentException $e)
		{
			return FALSE;
		}

		$consent = $this->getConsent($request->getId());

		// They've never responded to the request, so consent was not given
		if ( ! $consent)
		{
			return FALSE;
		}

		return $consent->isGranted();
	}

	/**
	 * Gets a ConsentRequest entity
	 *
	 * @param int|string $request_ref The name (url_title) or ID of a consent request
	 * @return ConsentRequest|null The consent request entity or NULL if it's not found.
	 */
	protected function getConsentRequest($request_ref)
	{
		$column = (is_numeric($request_ref)) ? 'consent_request_id' : 'url_title';

		$request = $this->model_delegate->get('ConsentRequest')
			->with('CurrentVersion')
			->filter('site_id', 'IN', [0, $this->site_id])
			->filter($column, $request_ref)
			->first();

		if ( ! $request)
		{
			throw new InvalidArgumentException("No such consent: '{$request_ref}'");
		}

		return $request;
	}

	/**
	 * Gets a Consent entity
	 *
	 * @param int $request_id The ID of a consent request
	 * @return Consent|null The consent entity or NULL if it's not found.
	 */
	protected function getConsent($request_id)
	{
		return $this->model_delegate->get('Consent')
			->with('ConsentRequest')
			->with(['ConsentRequest' => 'CurrentVersion'])
			->with('ConsentRequestVersion')
			->filter('member_id', $this->member->getId())
			->filter('consent_id', $request_id)
			->first();
	}

	/**
	 * Gets a Consent entity, and if one doesn't exist a new object is created
	 *
	 * @param ConsentRequest $request A ConsentRequest object
	 * @return Consent The consent entity
	 */
	protected function getOrMakeConsent(ConsentRequest $request)
	{
		$consent = $this->getConsent($request->getId());

		if ( ! $consent)
		{
			$consent = $this->model_delegate->make('Consent');
			$consent->ConsentRequest = $request;
			$consent->ConsentRequestVersion = $request->CurrentVersion;
			$consent->Member = $this->member;
			$consent->request_copy = $request->CurrentVersion->request;
			$consent->request_format = $request->CurrentVersion->request_format;
		}

		return $consent;
	}

	/**
	 * Logs an action
	 *
	 * @param ConsentRequest $request A ConsentRequest object
	 * @param string $msg The log message
	 * @return null
	 */
	protected function log(ConsentRequest $request, $msg)
	{
		$log = $this->model_delegate->make('ConsentAuditLog');
		$log->ConsentRequest = $request;
		$log->Member = $this->member;
		$log->action = $msg;
		$log->log_date = $this->now;
		$log->save();
	}
}
