<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Logging Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

require_once(EE_APPPATH.'/libraries/Logger.php');

class Installer_Logger extends EE_Logger {

	/**
	 * Installer interface for EE_Logger::deprecate_template_tag
	 *
	 * Deprecate a template tag and replace it in templates and snippets
	 *
	 * @param  String $message     The message to send to the developer log,
	 *                             uses developer() not deprecated()
	 * @param  String $regex       Regular expression to run through
	 *                             preg_replace
	 * @param  String $replacement Replacement to pass to preg_replace
	 * @return void
	 */
	public function deprecate_template_tag($message, $regex, $replacement)
	{
		if ( ! class_exists('Installer_Template'))
		{
			require_once(APPPATH . 'libraries/Template.php');
		}

		ee()->remove('TMPL');
		ee()->set('TMPL', new Installer_Template());

		// Keep installer config around so we can restore it after the
		// parent class is called
		$installer_config = ee()->config;
		ee()->remove('config');
		ee()->set('config', new MSM_Config());

		parent::deprecate_template_tag($message, $regex, $replacement);

		ee()->remove('config');
		ee()->set('config', $installer_config);
	}
}
// END CLASS

// EOF
