<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Consent\Service\Variables;

use EllisLab\ExpressionEngine\Model\Consent\Consent as ConsentModel;
use EllisLab\ExpressionEngine\Service\Template\Variables;

/**
 * Consent Request Variables
 */
class ConsentRequest extends Variables {

	/**
	 * @var object EllisLab\ExpressionEngine\Model\Consent\Consent
	 */
	private $consent;

	/**
	 * Constructor
	 *
	 * @param object $consent EllisLab\ExpressionEngine\Model\Consent\Consent
	 */
	public function __construct(ConsentModel $consent)
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
			'text_format'	=> $consent->request_format,
			'html_format'	=> 'all',
			'auto_links'	=> FALSE,
			'allow_img_url' => FALSE,
		];

		$this->variables = [

		];

		return $this->variables;
	}
}
// END CLASS

// EOF
