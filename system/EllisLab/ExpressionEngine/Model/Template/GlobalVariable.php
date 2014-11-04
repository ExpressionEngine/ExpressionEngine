<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * ExpressionEngine Global Variable Model
 *
 * @package		ExpressionEngine
 * @subpackage	Template
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class GlobalVariable extends Model {

	// Meta data
	protected static $_primary_key = 'variable_id';
	protected static $_gateway_names = array('GlobalVariableGateway');

	protected static $_key_map = array(
		'variable_id'	=> 'GlobalVariableGateway',
		'site_id'		=> 'GlobalVariableGateway'
	);

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'many_to_one'
		)
	);

	// Properties
	protected $variable_id;
	protected $site_id;
	protected $variable_name;
	protected $variable_data;

	public function getSite()
	{
		return $this->getRelated('Site');
	}

	public function setSite(Site $site)
	{
		return $this->setRelated('Site', $site);
	}
}
