<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model;

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
 * ExpressionEngine Specialty Templates Model
 *
 * @package		ExpressionEngine
 * @subpackage	Template
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class SpecialtyTemplate extends Model {

	// Meta data
	protected static $_primary_key = 'template_id';
	protected static $_gateway_names = array('SpecialtyTemplateGateway');

	protected static $_key_map = array(
		'template_id'	=> 'SpecialtyTemplateGateway',
		'site_id'		=> 'SpecialtyTemplateGateway'
	);

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'many_to_one'
		)
	);

	// Properties
	public $template_id;
	public $site_id;
	public $enable_template;
	public $template_name;
	public $data_title;
	public $template_data;

	public function getSite()
	{
		return $this->getRelated('Site');
	}

	public function setSite(Site $site)
	{
		return $this->setRelated('Site', $site);
	}
}
