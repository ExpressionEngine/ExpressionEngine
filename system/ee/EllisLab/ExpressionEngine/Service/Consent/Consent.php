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
use EllisLab\ExpressionEngine\Service\Model\Collection;
use InvalidArgumentException;

/**
 * Consent Service
 */
class Consent {

	const COOKIE_NAME = 'visitor_consents';

	/**
	 * @var Member $member The Member the consent relates to
	 */
	protected $member;

	/**
	 * @var Member $actor The Member acting, usually the member being acted upon, but may be a super admin.
	 */
	protected $actor;

	/**
	 * @var string The addon prefix, if any, e.g. (addon_name:)
	 */
	private $addon_prefix = '';

	/**
	 * @var object Collection of granted consents for this Member
	 */
	private $cached_consents;

	/**
	 * @var object $model_delegate An injected `ee('Model')` object
	 */
	protected $model_delegate;

	/**
	 * @var object $input_delegate An injected `ee()->input` object
	 */
	protected $input_delegate;

	/**
	 * @var int $now The current timestamp
	 */
	protected $now;

	public function __construct(ModelFacade $model_delegate, $input_delegate, Member $member, Member $actor, $now)
	{
		$this->model_delegate = $model_delegate;
		$this->input_delegate = $input_delegate;
		$this->member = $member;
		$this->actor = $actor;
		$this->now = $now;

		// load up the member's consent grants if we haven't yet
		if (($cached_consents = ee()->session->cache(__CLASS__, 'cached_consents_'.$member->getId())) === FALSE)
		{
			$cached_consents = $this->getGrantedRequests()->indexBy('consent_name');
			ee()->session->set_cache(__CLASS__, 'cached_consents_'.$member->getId(), $cached_consents);
		}

		$this->cached_consents = $cached_consents;
	}

	/**
	 * Creates/updates a consent record for the member for the given consent request
	 *
	 * @param int|string $request_ref The name or ID of a consent request
	 * @param string $via How the consent was granted
	 * @throws InvalidArgumentException
	 * @return NULL
	 */
	public function grant($request_ref, $via = 'online_form')
	{
		try
		{
			$request = $this->getConsentRequest($request_ref);
		}
		catch (InvalidArgumentException $e)
		{
			// bubble up any exceptions so they are able to be handled by the caller
			throw $e;
		}

		// Can't consent to an empty consent request
		if ( ! $request->consent_request_version_id)
		{
			return;
		}

		if ( ! $this->callerHasPermission($request))
		{
			throw new \Exception("Invalid Consent access, {$this->addon_prefix} cannot grant: '{$request_ref}'");
		}

		if ($this->isAnonymous())
		{
			$cookie = $this->getConsentCookie();
			$cookie[$request->getId()] = $this->now;
			$this->saveConsentCookie($cookie);
		}
		else
		{
			$consent = $this->getOrMakeConsent($request);
			$consent->consent_given = TRUE;
			$consent->ConsentRequestVersion = $request->CurrentVersion;
			$consent->response_date = $this->now;
			$consent->consent_given_via = $via;
			$consent->request_copy = $request->CurrentVersion->request;
			$consent->request_format = $request->CurrentVersion->request_format;

			$consent->save();

			if ($this->memberIsActor())
			{
				$consent->log(sprintf(lang('consent_granted_log_msg'), $via));
			}
			else
			{
				$consent->log(sprintf(lang('consent_granted_by_log_msg'), $this->getActorName(), $via));
			}
		}

		$this->addGrantedRequestToCache($request);
	}

	/**
	 * Updates a consent record for the member for the given consent request to indicate
	 * that consent has been withdrawn
	 *
	 * @param int|string $request_ref The name or ID of a consent request
	 * @throws InvalidArgumentException
	 * @return NULL
	 */
	public function withdraw($request_ref)
	{
		try
		{
			$request = $this->getConsentRequest($request_ref);
		}
		catch (InvalidArgumentException $e)
		{
			// bubble up any exceptions so they are able to be handled by the caller
			throw $e;
		}

		if ( ! $this->callerHasPermission($request))
		{
			throw new \Exception("Invalid Consent access, {$this->addon_prefix} cannot withdraw: '{$request_ref}'");
		}

		if ($this->isAnonymous())
		{
			$cookie = $this->getConsentCookie();
			unset($cookie[$request->getId()]);
			$this->saveConsentCookie($cookie);
		}
		else
		{
			$consent = $this->getOrMakeConsent($request);
			$consent->consent_given = FALSE;
			$consent->response_date = $this->now;
			$consent->save();

			if ($this->memberIsActor())
			{
				$consent->log(lang('consent_withdrawn_log_msg'));
			}
			else
			{
				$consent->log(sprintf(lang('consent_withdrawn_by_log_msg'), $this->getActorName()));
			}
		}
	}

	/**
	 * Has the member granted consent for a given consent request?
	 *
	 * @param int|string $request_ref The name or ID of a consent request
	 * @throws InvalidArgumentException
	 * @return bool TRUE if they have, FALSE if they have not
	 */
	public function hasGranted($request_ref)
	{
		if ($this->grantIsCached($request_ref))
		{
			return TRUE;
		}

		try
		{
			$request = $this->getConsentRequest($request_ref);
		}
		catch (InvalidArgumentException $e)
		{
			return FALSE;
		}

		// Anonymous visitor/guest consent check: it's in a cookie, if we can set cookies
		if ($this->isAnonymous())
		{
			return array_key_exists($request->getId(), $this->getConsentCookie());
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
	 * Gets all the consent requests the member (or anonymous visitor) has granted
	 * consent.
	 *
	 * @return object A Collection of ConsentRequest objects
	 */
	public function getGrantedRequests()
	{
		if ($this->isAnonymous())
		{
			$request_ids = array_keys($this->getConsentCookie());

			if (empty($request_ids))
			{
				return new Collection([]);
			}

			return $this->model_delegate->get('ConsentRequest')
				->with('CurrentVersion')
				->filter('consent_request_id', $request_ids)
				->all();
		}

		if ( ! $this->member->Consents)
		{
			return new Collection([]);
		}

		$consents = $this->model_delegate->get('Consent')
			->with('ConsentRequest')
			->with(['ConsentRequest' => 'CurrentVersion'])
			->with('ConsentRequestVersion')
			->filter('member_id', $this->member->getId())
			->filter('consent_given', 'y')
			->all()
			->filter(function($consent) {
				return $consent->isGranted();
			});

		return new Collection($consents->map(function($consent) {
			return $consent->ConsentRequest;
		}));
	}

	/**
	 * Gets all the granted consents for a specific request
	 *
	 * @param int|string $request_ref The name or ID of a consent request
	 * @throws InvalidArgumentException
	 * @return object A Collection of Consent objects
	 */
	public function getGrantedConsentsFor($request_ref)
	{
		$request = $this->getConsentRequest($request_ref);

		return $this->model_delegate->get('Consent')
			->with('ConsentRequest')
			->with(['ConsentRequest' => 'CurrentVersion'])
			->with('ConsentRequestVersion')
			->filter('consent_id', $request_id)
			->all()
			->filter(function($consent) {
				return $consent->isGranted();
			});
	}

	/**
	 * Gets the values for a specific request and the member's consent
	 *
	 * @param int|string|array $request_refs The name or an array of names, or id or array of ids
	 * @return object A Collection of associative arrays for each Consent Request
	 */
	public function getConsentDataFor($request_refs)
	{
		if (empty($request_refs))
		{
			return new Collection([]);
		}

		if ( ! is_array($request_refs))
		{
			$request_refs = [$request_refs];
		}

		$data = [];
		$consents = ($this->isAnonymous()) ? $this->getConsentCookie() : $this->member->Consents->indexBy('consent_request_id');

		$requests = $this->model_delegate->get('ConsentRequest')->with('CurrentVersion');

		$numeric_refs = TRUE;
		foreach ($request_refs as $ref)
		{
			if ( ! is_numeric($ref))
			{
				$numeric_refs = FALSE;
				break;
			}
		}

		if ($numeric_refs)
		{
			$requests->filter('consent_request_id', 'IN', $request_refs);
		}
		else
		{
			$requests->filter('consent_name', 'IN', $request_refs);
		}

		$requests = $requests->all();

		foreach ($requests as $request)
		{
			$key = $request->consent_name;
			$data[$key] = array_merge($request->getValues(), $request->CurrentVersion->getValues());
			$data[$key]['has_granted'] = FALSE;
			$data[$key]['create_date'] = $request->CurrentVersion->create_date->format('U');

			// these keys may not be present if the user hasn't responded, but we want a consistent array
			$data[$key]['consent_given_via'] = NULL;
			$data[$key]['consent_id'] = NULL;
			$data[$key]['expiration_date'] = NULL;
			$data[$key]['member_id'] = NULL;
			$data[$key]['request_copy'] = NULL;
			$data[$key]['response_date'] = NULL;

			if ($this->isAnonymous())
			{
				$data[$key]['consent_given_via'] = 'online_form';
				$data[$key]['member_id'] = 0;
				$data[$key]['has_granted'] = array_key_exists($request->getId(), $consents);
				if ($data[$key]['has_granted'])
				{
					$data[$key]['response_date'] = $consents[$request->getId()];

				}
			}
			else
			{
				if (array_key_exists($request->getId(), $consents))
				{
					$consent = $consents[$request->getId()];
					$data[$key] = array_merge($consent->getValues(), $data[$key]);
					unset($data[$key]['consent_given']);
					$data[$key]['has_granted'] = $consent->isGranted();

					foreach (['expiration_date', 'response_date'] as $property)
					{
						if ( ! is_null($data[$key][$property]))
						{
							$data[$key][$property] = $data[$key][$property]->format('U');
						}
					}
				}
			}
		}

		return new Collection($data);
	}

	/**
	 * Checks to make sure the caller has write access premission to a Consent Request
	 *
	 * @param  object $request EllisLab\ExpressionEngine\Model\Consent\ConsentRequest
	 * @return bool whether or not the caller has permission to modify the user's consent
	 */
	protected function callerHasPermission($request)
	{
		list($this_class, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		if (strpos($caller['file'], PATH_THIRD) === 0)
		{
			if (empty($this->addon_prefix))
			{
				$relative_path = str_replace(PATH_THIRD, '', $caller['file']);
				list($addon_name, $extra_path) = explode('/', $relative_path, 2);

				$addon = ee('Addon')->get($addon_name);
				$this->addon_prefix = $addon->getPrefix();
			}

			return (strpos($request->consent_name, $this->addon_prefix . ':') === 0);
		}

		return TRUE;
	}

	/**
	 * Is the member we are checking anonymous?
	 *
	 * @return bool TRUE if they are, FALSE if not
	 */
	protected function isAnonymous()
	{
		return ($this->member->getId() == 0);
	}

	/**
	 * Is the member granting/withdrawing consent the member initiating the action (actor)?
	 *
	 * @return bool TRUE if they are, FALSE if not
	 */
	protected function memberIsActor()
	{
		return ($this->member->getId() == $this->actor->getId());
	}

	/**
	 * Gets the name and UID of the actor for logging purposes
	 *
	 * @return string The member name and UID.
	 */
	protected function getActorName()
	{
		return $this->actor->getMemberName() . ' (#' . $this->actor->getId() . ')';
	}

	/**
	 * Gets the consent cookie
	 *
	 * @return array An associative array of granted consents
	 */
	protected function getConsentCookie()
	{
		$cookie = $this->input_delegate->cookie(self::COOKIE_NAME);
		$cookie = ee('Encrypt/Cookie')->getVerifiedCookieData($cookie);
		$cookie = json_decode($cookie, TRUE);

		if ( ! $cookie)
		{
			$cookie = [];
		}

		return $cookie;
	}

	/**
	 * Encodes, signs, and saves the consent cookie
	 *
	 * @param array $consented_to An associative array of granted consents with the
	 *   request's ID as the array key.
	 */
	protected function saveConsentCookie(array $consented_to)
	{
		$payload = ee('Encrypt/Cookie')->signCookieData(json_encode($consented_to));
		// 60 * 60 * 24 * 365 = 31556952; A year of seconds
		$this->input_delegate->set_cookie(self::COOKIE_NAME, $payload, 31556952);
	}

	/**
	 * Gets a ConsentRequest entity
	 *
	 * @param int|string $request_ref The name or ID of a consent request
	 * @return ConsentRequest|null The consent request entity or NULL if it's not found.
	 */
	protected function getConsentRequest($request_ref)
	{
		$column = (is_numeric($request_ref)) ? 'consent_request_id' : 'consent_name';

		$request = $this->model_delegate->get('ConsentRequest')
			->with('CurrentVersion')
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
			->filter('consent_request_id', $request_id)
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
		}

		return $consent;
	}

	/**
	 * Check if we know a request is granted already
	 *
	 * @param  int|string $request_ref The name or ID of a consent request
	 * @return boolean Whether a request is in the cached consents
	 */
	protected function grantIsCached($request_ref)
	{
		if (is_numeric($request_ref))
		{
			$grant_ids = $this->cached_consents->pluck('consent_request_id');
			return isset($grant_ids[$request_ref]);
		}

		return isset($this->cached_consents[$request_ref]);
	}

	/**
	 * Adds a request to the cached consents
	 *
	 * @param object $request EllisLab\ExpressionEngine\Model\Consent\ConsentRequest
	 * @return void
	 */
	protected function addGrantedRequestToCache($request)
	{
		$this->cached_consents[$request->consent_name] = $request;
		ee()->session->set_cache(__CLASS__, 'cached_consents_'.$this->member->getId(), $this->cached_consents);
	}

	/**
	 * Removes a request from the cached consents for the affected member
	 *
	 * @param  object $request EllisLab\ExpressionEngine\Model\Consent\ConsentRequest
	 * @return void
	 */
	protected function removeGrantedRequestFromCache($request)
	{
		unset($this->cached_consents[$request->consent_name]);
		ee()->session->set_cache(__CLASS__, 'cached_consents_'.$this->member->getId(), $this->cached_consents);
	}
}
