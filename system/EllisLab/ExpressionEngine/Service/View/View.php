<?php
namespace EllisLab\ExpressionEngine\Service\View;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine View Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class View {

	public function __construct($path)
	{
		$this->path = $path;
	}

	public function render(array $vars)
	{
		return ee()->load->view($this->path, $vars, TRUE);
	}
}
// END CLASS

/* End of file View.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/View/View.php */