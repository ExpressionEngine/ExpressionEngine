<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Dashboard;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Dashboard Widget Model
 */
class DashboardWidget extends Model {

	protected static $_primary_key = 'widget_id';
	protected static $_table_name = 'dashboard_widgets';

	/*protected static $_validation_rules = array(
		'type' 	=> 'required|enum[html,php]',
		'source'=> 'required|validWidgetSources[type]',
		'name' 	=> 'noHtml|required',
		'data' 	=> 'validateWhenSourceIs[template]|required'
	);*/

	protected static $_relationships = array(
		'DashboardLayouts' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'DashboardLayout',
			'pivot' => array(
				'table' => 'dashboard_layout_widgets',
				'left' => 'widget_id',
				'right' => 'layout_id'
			)
		)
	);

	protected $widget_id;
	//name as displayed in control panel
	protected $name;
	//template data (only for 'template' widgets)
	protected $data;
	//template type - html or php
	//`.html` files are treated as EE templates, `.php` files are expected to return ready-to-use code & meta info
	protected $type;
	/**
	 * widget source
	 * 
	 * `template` for template (editable) widgets
	 * `ee:widget_name` for first-party widgets 
	 * `addon_name:widget_name` for third-party
	 * do not include file extension with widget name
	 * */
	protected $source;

	public function validateWhenSourceIs($key, $value, $parameters, $rule)
	{
		$source = $this->getProperty('source');

		return in_array($source, $parameters) ? TRUE : $rule->skip();
	}

	public function validWidgetSources($key, $value, $parameters=[], $rule)
	{
		if ($value=='template') return TRUE;

		$type = $this->getProperty('type');
		$fs = new Filesystem();

		if (strpos($value, 'ee:')===0)
		{
			$checker = substr($value, 3);
			if ($fs->exists(PATH_ADDONS.'pro/widgets/'.$checker.'.'.$type))
			{
				return TRUE;
			}
		}

		//if we got so far, it's third party widget
		$widget_data = explode(":", $value);
		if (count($widget_data)!=2)
		{
			return FALSE;
		}

		//is it installed
		$installed = ee('Addon')->get($widget_data[0])->isInstalled();
		if (!$installed) return FALSE;

		if ($fs->exists(PATH_THIRD.$widget_data[0].'/widgets/'.$widget_data[1].'.'.$type))
		{
			return TRUE;
		}

		return FALSE;

	}

}
