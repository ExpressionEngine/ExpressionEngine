<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Consent\Service\Variables;

use EllisLab\ExpressionEngine\Model\Consent\ConsentRequest as ConsentRequestModel;
use EllisLab\ExpressionEngine\Service\Template\Variables;

/**
 * Consent Request Variables
 */
class ConsentRequest extends Variables {

	/**
	 * @var object EllisLab\ExpressionEngine\Model\Consent\ConsentRequest
	 */
	private $request;

	/**
	 * Constructor
	 *
	 * @param object $consent EllisLab\ExpressionEngine\Model\Consent\ConsentRequest
	 */
	public function __construct(ConsentRequestModel $request)
	{
		$this->request = $request;

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
			'text_format'	=> $this->request->CurrentVersion->request_format,
			'html_format'	=> 'all',
			'auto_links'	=> FALSE,
			'allow_img_url' => FALSE,
		];

		$this->variables = [
			'consent_creation_date' => $this->date($this->request->CurrentVersion->create_date),
			'consent_edit_date' => $this->date($this->request->CurrentVersion->edit_date),
			//'consent_granted' => $this->request->granted,
			//'consent_granted_date' => $this->request->granted_date,
			'consent_id' => $this->request->getId(),
			'consent_request' => $this->typography($this->request->CurrentVersion->request, $typography_prefs),
			'consent_short_name' => $this->request->consent_name,
			'consent_title' => $this->request->title,
			'consent_version_id' => $this->request->consent_request_version_id,
		];

		return $this->variables;
	}
}
// END CLASS

// EOF
