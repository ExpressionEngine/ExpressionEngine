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
 * ExpressionEngine ViewFactory Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class ViewFactory {

	protected $basepath;

	public function __construct($basepath)
	{
		$this->basepath = $basepath;
	}

	public function make($path)
	{
		return new View($this->basepath.'/'.$path);
	}

}
// END CLASS

/* End of file Factory.php */
/* Location: ./system/EllisLab/ExpressionEngine/Service/View/Factory.php */