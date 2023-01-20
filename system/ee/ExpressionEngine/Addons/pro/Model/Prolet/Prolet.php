<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Model\Prolet;

use ExpressionEngine\Service\Model\Model;
use ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Prolet Model
 */
class Prolet extends Model
{
    protected static $_primary_key = 'prolet_id';
    protected static $_table_name = 'prolets';

    protected static $_validation_rules = array(
        //need to validate class and source
    );

    protected static $_relationships = array(
        'Dock' => array(
            'type' => 'hasAndBelongsToMany',
            'model' => 'pro:Dock',
            'pivot' => array(
                'table' => 'dock_prolets',
                'left' => 'prolet_id',
                'right' => 'dock_id'
            )
        ),
    );

    /**
     * @var int Prolet ID
     */
    public $prolet_id;

    /**
     * @var string Prolet source (add-on name)
     */
    public $source;

    /**
     * @var string FQCN of Prolet
     */
    public $class;

    /**
     * @var Addon associated add-on
     */
    protected $_addon;

    /**
     * @var Object instantiated prolet class
     */
    protected $_prolet;

    /**
     * Instantiate prolet class
     *
     * @return Object self::$_prolet
     */
    public function get__prolet()
    {
        if (is_null($this->_prolet)) {
            $class = $this->class;
            $addon = $this->addon;
            $classIsValid = array_search($class, $addon->getProletClasses());
            if ($classIsValid !== false && $addon::implementsProletInterface($class)) {
                $this->_prolet = new $class();
            }
        }
        return $this->_prolet;
    }

    /**
     * Get associated add-on
     *
     * @return Addon
     */
    public function get__addon()
    {
        if (is_null($this->_addon)) {
            $this->_addon = ee('pro:Addon')->get($this->source);
        }
        return $this->_addon;
    }

    /**
     * Prolet name to display
     *
     * @return string
     */
    public function get__name()
    {
        if ($this->prolet) {
            return ee('Security/XSS')->clean($this->prolet->getName());
        }
        return '';
    }

    /**
     * The name of javascript method that will be invoked by prolet
     *
     * @return string
     */
    public function get__method()
    {
        if ($this->prolet) {
            $method = $this->prolet->getMethod();
            $availableMethods = [
                'ajax',
                'redirect',
                'popup'
            ];
            if (in_array($method, $availableMethods)) {
                return $method;
            }
        }

        return 'popup';
    }

    /**
     * Action or redirect URL
     *
     * @return string
     */
    public function getUrl($params = [])
    {
        if ($this->prolet) {
            $params = ee('Security/XSS')->clean($params);
            if (in_array($this->method, ['ajax', 'redirect'])) {
                $url = $this->prolet->getUrl();
                //we can do replacements as needed
                if (!empty($params)) {
                    $replacements = [];
                    foreach ($params as $key => $value) {
                        $replacements[LD . $key . RD] = $value;
                    }
                    $url = str_replace(array_keys($replacements), $replacements, $url);
                }
                return $url;
            }
            return ee('CP/URL')->make('pro/prolet/' . $this->getId(), $params, ee()->config->item('cp_url'))->compile();
        }
        return '';
    }

    /**
     * Action name
     *
     * @return string
     */
    public function get__action()
    {
        $action = $this->prolet->getAction();
        if (empty($action)) {
            $action = 'index';
        }
        return ee('Security/XSS')->clean($action);
    }

    /**
     * Prolet window size
     *
     * @return string
     */
    public function get__size()
    {
        if ($this->prolet) {
            $size = $this->prolet->getSize();
            $availableSizes = [
                'basic',
                'footer',
                'large',
                'small'
            ];
            if (in_array($size, $availableSizes)) {
                return $size;
            }
        }

        return 'basic';
    }

    /**
     * Buttons for the prolet popup
     *
     * @return Array
     */
    public function get__buttons()
    {
        if ($this->prolet) {
            $buttons = $this->prolet->getButtons();

            if (empty($buttons) || !is_array($buttons)) {
                return [];
            }

            ee()->lang->loadfile($this->source);

            foreach ($buttons as $i => $button) {
                if (!is_array($button)) {
                    $buttons[$i] = [
                        'type'          => 'button',
                        'text'          => lang($button),
                        'buttonStyle'   => 'primary',
                        'callback'      => strtolower($button),
                    ];
                }
                if ($buttons[$i]['text'] == 'save') {
                    $buttons[$i]['text'] = lang('save');
                }
                if (!isset($buttons[$i]['callback']) || !isset($buttons[$i]['text'])) {
                    unset($buttons[$i]);
                }
            }
            return ee('Security/XSS')->clean($buttons);
        }
        return [];
    }

    /**
    * Get icon path
    *
    * @return string URL for add-on's icon, or null
    */
    public function get__icon()
    {
        if ($this->prolet) {
            $icon = $this->prolet->getIcon();
            //don't allow going up the directories
            if (strpos($icon, '../') !== false) {
                return null;
            }
            return $this->addon->getPath() . '/' . $icon;
        }
        return null;
    }

    /**
    * Get icon URL
    *
    * @param bool append action URL, or just the get unique part
    * @return string URL for add-on's icon
    */
    public function getIconUrl($appendActionUrl = true)
    {
        $url = '';
        if ($appendActionUrl) {
            $action_id = ee()->db->select('action_id')
                ->where('class', 'File')
                ->where('method', 'addonIcon')
                ->get('actions');
            $url = ee()->functions->fetch_site_index() . QUERY_MARKER . 'ACT=' . $action_id->row('action_id');
        }
        $url .= AMP . 'prolet=' . $this->getId();

        return $url;
    }

    /**
     * Ensures action method exists in prolet
     *
     * @return bool
     */
    public function ensureActionExists()
    {
        return method_exists($this->prolet, $this->action);
    }

    /**
     * Generate raw output
     *
     * @return string, or array
     */
    private function generateRawOutput()
    {
        if ($this->method != 'popup' || !$this->ensureActionExists()) {
            show_error(lang('prolet_action_does_not_exist'), 403);
        }

        $func = $this->action;
        return $this->prolet->$func();
    }

    /**
     * Generates complete output inside our wrapper
     *
     * @return string
     */
    public function generateOutput()
    {
        $rawOutput = $this->generateRawOutput();
        if (is_array($rawOutput)) {
            if (isset($rawOutput['html'])) {
                return $rawOutput;
            }
            $vars = array_merge($rawOutput, [
                'base_url' => ee('CP/URL')->make('pro/prolet/' . $this->getId(), [], ee()->config->item('cp_url'))->compile()
            ]);
            if (!isset($vars['form_hidden'])) {
                $vars['form_hidden'] = [];
            }
            foreach ($_GET as $key => $val) {
                if (!in_array($key, ['D', 'C', 'M', 'S'])) {
                    $vars['form_hidden'][$key] = ee('Security/XSS')->clean($val);
                }
            }
            $rawOutput = ee('View')->make('pro:_shared/form')->render($vars);
        } else {
            if (!is_string($rawOutput)) {
                show_error(lang('prolet_did_not_return_valid_data'), 403);
            }
        }

        $view = preg_match('/<div(.+)class="(.*)panel([\"\s])/i', $rawOutput) === 1 ? 'pro:prolet-unwrapped' : 'pro:prolet';
        return ee('View')->make($view)->render([
            'pro_class'   => 'pro-frontend-modal',
            'hide_topbar' => true,
            'output'      => $rawOutput
        ]);
    }
}

// EOF
