<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Consent\Service\Variables;

use EllisLab\ExpressionEngine\Service\Template\Variables;

/**
 * Consent Request Variables
 */
class Consent extends Variables {

	/**
	 * @var array Consent data from ee('Consent')->getConsentDataFor()
	 */
	private $consent;

	/**
	 * Constructor
	 *
	 * @param array $consent Consent data from ee('Consent')->getConsentDataFor()
	 */
	public function __construct(array $consent)
	{
		$this->consent = $consent;

		parent::__construct();
	}

	/**
	 * getTemplateVariables
	 *
	 * @return array fully prepped variables to be parsed
	 */
	public function getTemplateVariables()
	{
		if ( ! empty($this->variables))
		{
			return $this->variables;
		}

		ee()->typography->initialize([
			'parse_images'		=> TRUE,
			'allow_headings'	=> TRUE,
			'word_censor'		=> bool_config_item('comment_word_censoring'),
		]);

		$typography_prefs = [
			'text_format'	=> $this->consent['request_format'],
			'html_format'	=> 'all',
			'auto_links'	=> FALSE,
			'allow_img_url' => FALSE,
		];

		$this->variables = [
			'consent_creation_date'    => $this->date($this->consent['create_date']),
			'consent_double_opt_in'    => $this->consent['double_opt_in'],
			'consent_expiration_date'  => $this->consent['expiration_date'],
			'consent_given_via'        => $this->consent['consent_given_via'],
			'consent_granted'          => $this->consent['has_granted'],
			'consent_granted_date'     => $this->consent['update_date'],
			'consent_id'               => $this->consent['consent_request_id'],
			'consent_request'          => $this->typography($this->consent['request'], $typography_prefs),
			'consent_retention_period' => $this->consent['retention_period'],
			'consent_short_name'       => $this->consent['consent_name'],
			'consent_user_created'     => $this->consent['user_created'],
			'consent_title'            => $this->consent['title'],
			'consent_version_id'       => $this->consent['consent_request_version_id'],
			'consent_withdrawn_date'   => $this->consent['withdrawn_date'],
		];

		return $this->variables;
	}
}
// END CLASS

// EOF
