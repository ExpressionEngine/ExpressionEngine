<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

class Installer_profiler extends CI_profiler {

	/**
	 * Overriding the _compile_config() method in the CI profiler.
	 *
	 * on an update from 1.x to 2.x the database configuration vars are in the
	 * config.php file. So in the event someone has a problems with their upgrade
	 * this will at least keep the database variables obfuscated.
	 */
	protected function _compile_config()
	{
		$output  = "\n\n";
		$output .= '<fieldset id="ci_profiler_config" style="border:1px solid #000;padding:6px 10px 10px 10px;margin:20px 0 20px 0;background-color:#eee">';
		$output .= "\n";
		$output .= '<legend style="color:#000;">&nbsp;&nbsp;'.$this->CI->lang->line('profiler_config').'&nbsp;&nbsp;(<span style="cursor: pointer;" onclick="var s=document.getElementById(\'ci_profiler_config_table\').style;s.display=s.display==\'none\'?\'\':\'none\';this.innerHTML=this.innerHTML==\''.$this->CI->lang->line('profiler_section_show').'\'?\''.$this->CI->lang->line('profiler_section_hide').'\':\''.$this->CI->lang->line('profiler_section_show').'\';">'.$this->CI->lang->line('profiler_section_show').'</span>)</legend>';
		$output .= "\n";

		$output .= "\n\n<table style='width:100%; display:none' id='ci_profiler_config_table'>\n";

		$protected_vals = array(
				'db_hostname', 'db_username', 'db_password', 'db_name',
				'db_type', 'db_prefix', 'db_conntype'
			);

		foreach($this->CI->config->config as $config=>$val)
		{
			if (is_array($val))
			{
				$val = print_r($val, TRUE);
			}

			if (in_array($config, $protected_vals))
			{
				$val = '******************';
			}

			$output .= "<tr><td style='padding:5px; vertical-align: top;color:#900;background-color:#ddd;'>".$config."&nbsp;&nbsp;</td><td style='padding:5px; color:#000;background-color:#ddd;'>".htmlspecialchars($val)."</td></tr>\n";
		}

		$output .= "</table>\n";
		$output .= "</fieldset>";

		return $output;
	}


}