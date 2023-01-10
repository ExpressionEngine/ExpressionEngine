<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Dashboard;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Dashboard Widget Model
 */
class DashboardWidget extends Model
{
    protected static $_primary_key = 'widget_id';
    protected static $_table_name = 'dashboard_widgets';

    protected static $_validation_rules = array(
        'widget_type' => 'required|enum[html,php]',
        'widget_source' => 'required|validWidgetSources[type]',
        'widget_name' => 'validateWhenSourceIs[template]|required|noHtml',
        'widget_data' => 'validateWhenSourceIs[template]|required',
        'widget_file' => 'validateWhenSourceIsNot[template]|required'
    );

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
    protected $widget_name;
    //template data (only for 'template' widgets)
    protected $widget_data;
    //template type - html or php
    //`.html` files are treated as EE templates, `.php` files are expected to return ready-to-use code & meta info
    protected $widget_type;
    /**
     * widget source
     *
     * `template` for template (editable) widgets
     * `ee` for native widgets
     * `addon_name` for third-party
     * */
    protected $widget_source;
    /**
     * do not include file extension with widget file
     * */
    protected $widget_file;

    public function validateWhenSourceIs($key, $value, $parameters, $rule)
    {
        $source = $this->getProperty('widget_source');

        return in_array($source, $parameters) ? true : $rule->skip();
    }

    public function validateWhenSourceIsNot($key, $value, $parameters, $rule)
    {
        $source = $this->getProperty('widget_source');

        return !in_array($source, $parameters) ? true : $rule->skip();
    }

    public function validWidgetSources($key, $value, $parameters, $rule)
    {
        if ($value == 'template') {
            return true;
        }

        $type = $this->getProperty('widget_type');
        $file = $this->getProperty('widget_file');
        $fs = new Filesystem();

        if ($value == 'ee') {
            if ($fs->exists(PATH_ADDONS . 'pro/widgets/' . $file . '.' . $type)) {
                return true;
            }

            return false;
        }

        //if we got so far, it's third party widget
        //is it installed
        $installed = ee('Addon')->get($value)->isInstalled();
        if (!$installed) {
            return false;
        }

        if ($fs->exists(PATH_THIRD . $value . '/widgets/' . $file . '.' . $type)) {
            return true;
        }

        return false;
    }
}
