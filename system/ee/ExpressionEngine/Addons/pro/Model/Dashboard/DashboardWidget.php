<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Model\Dashboard;

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
            'model' => 'ee:DashboardLayout',
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

    /**
     * @var Addon associated add-on
     */
    protected $_addon;

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

    /**
     * genereate html for this widget
     */
    public function generateHtml($edit_mode = false, $enabled = true)
    {
        $html = '';
        $file_path = null;
        $fs = new Filesystem();

        //enforce type for template widgets
        if ($this->widget_source == 'template') {
            $this->widget_type = 'html';
        }

        if ($this->widget_source == 'template') { //template
            if (empty($this->widget_data)) {
                return $html;
            }
            $tmpl_params = unserialize($this->widget_data);
            if (!is_array($tmpl_params)) {
                return $html;
            }
            $templateQuery = ee('Model')->get('Template');
            foreach ($tmpl_params as $param => $val) {
                $templateQuery->filter($param, $val);
            }
            $template = $templateQuery->first();
            if (empty($template)) {
                return $html;
            }
            if (!ee('Permission')->isSuperAdmin() && !array_intersect($template->Roles->pluck('role_id'), ee()->session->getMember()->getAllRoles()->pluck('role_id'))) {
                return $html;
            }
            $html = $template->template_data;
        } elseif ($this->widget_source == 'pro') { //first pary
            $file_path = PATH_ADDONS . 'pro/widgets/' . $this->widget_file . '.' . $this->widget_type;
        } else { //third party add-ons
            //do they have permission for it? we only have system for module permissions in place
            if (!$this->_check_addon_access($this->widget_source)) {
                return $html;
            }
            ee()->lang->loadfile($this->widget_source, '', false);
            $file_path = PATH_THIRD . $this->widget_source . '/widgets/' . $this->widget_file . '.' . $this->widget_type;
        }

        switch ($this->widget_type) {
            case 'php':
                if (empty($file_path)) {
                    return $html; //no valid path
                }
                if (!$fs->exists($file_path)) {
                    return $html; // file does not exist
                }
                if (empty($this->_addon)) {
                    $this->_addon = ee('pro:Addon')->get($this->widget_source);
                }
                if (empty($this->_addon)) {
                    return $html; // addon does not exist
                }
                include_once($file_path);
                $widgetClass = trim($this->_addon->getProvider()->getNamespace(), '\\') . '\\Widgets\\' . ucfirst($this->widget_file);
                if (!$this->_addon::implementsDashboardWidgetInterface($widgetClass)) {
                    return $html;
                }
                try {
                    $widget = new $widgetClass($this, $edit_mode, $enabled);// we will load the widget instance into contructor
                    $html = $widget->getHtml();
                } catch (\Throwable $e) {
                }
                break;
            case 'html':
            default:
                if (!empty($file_path) && $fs->exists($file_path)) {
                    $html = $fs->read($file_path);
                } elseif (empty($html)) {
                    $html = $this->widget_data;
                }
                if (!isset(ee()->TMPL)) {
                    ee()->load->library("template", null, "TMPL");
                }
                $html = $this->_parse_widget_declaration($html, $edit_mode, $enabled);
                ee()->TMPL->parse($html, false, ee()->config->item('site_id'));
                $html = ee()->TMPL->parse_globals(ee()->TMPL->final_template);
                break;
        }
        return $html;
    }

    /**
     * Permission check
     */
    private function _check_addon_access($addon_name = null)
    {
        if (empty($addon_name)) {
            return false;
        }

        if (ee('Permission')->isSuperAdmin()) {
            return true;
        }

        $this->_addon = ee('pro:Addon')->get($addon_name);
        if (empty($this->_addon)) {
            return false;
        }
        if ($this->_addon->hasModule()) {
            if (! ee('Permission')->has('can_access_addons')) {
                return false;
            }

            $assigned_modules = ee()->session->getMember()->getAssignedModules()->pluck('module_name');

            if (in_array($this->_addon->getModuleClass(), $assigned_modules)) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Parse {widget} declaration and strip it out
     * {widget class="widget--support" title="ExpressionEngine Support" width="half"}
     * width can be: full, half
     */
    private function _parse_widget_declaration($template, $edit_mode = false, $enabled = true)
    {
        $vars = [
            'class' => '',
            'title' => '',
            'width' => '',
            'right_head' => ''
        ];
        if (preg_match('/(' . LD . 'widget\s)(.*?)' . RD . '/s', $template, $declaration)) {
            $template = str_replace($declaration[0], '', $template);
            foreach ($vars as $var_name => $var) {
                if (preg_match('/(' . $var_name . '\s*=")(.*?)"/s', $declaration[2], $match)) {
                    $vars[$var_name] = $match[2];
                }
            }

            $vars['edit_mode'] = $edit_mode;
            $vars['widget'] = $template;
            $vars['widget_id'] = $this->widget_id;
            $vars['enabled'] = $enabled;

            return ee('View')->make('pro:dashboard/widget')->render($vars);
        }

        //we do not want to break things, so if there is no widget declaration - we just skip this template
        return '';
    }
}
