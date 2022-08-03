<?php

class General_helper
{
    public $module = 'structure';
    public $module_name = 'Structure';

    public function view($view, $vars = array(), $return = false, $string = false)
    {
        if (!isset($vars['base_url'])) {
            $vars['base_url'] = $this->getBaseURL();
        }

        if (!isset($vars['cp_page_title'])) {
            $vars['cp_page_title'] = ee()->view->cp_page_title;
        }

        if ($string) {
            $view_content = ee('View')->makeFromString($string)->render($vars);
        } else {
            $view_content = ee('View')->make($this->module . ':' . $view)->render($vars);
        }

        return array(
            'heading'    => ee()->view->cp_page_title,
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/' . $this->module . '/')->compile() => $this->module_name,
            ),
            'body'       => $view_content,
        );
    }

    public function getBaseURL($method = '', $extra = '')
    {
        if ($method == '/') {
            $method = '';
        } elseif ($method) {
            $method = '/' . $method;
        }

        return ee('CP/URL', 'addons/settings/' . $this->module . $method . $extra);
    }

    public function cpURL($path, $mode = '', $variables = array())
    {
        if ($mode) {
            $mode = '/' . $mode;
        }

        if ($path == 'listing') {
            $path = 'publish';
        }

        if ($path == 'publish') {
            if ($mode == '/create' && isset($variables['channel_id'])) {
                $mode .= '/' . $variables['channel_id'];
                unset($variables['channel_id']);
            } elseif ($mode == '/edit' && isset($variables['entry_id'])) {
                $mode .= '/entry/' . $variables['entry_id'];
                unset($variables['entry_id']);
            }
        }

        $url = ee('CP/URL')->make($path . $mode, $variables);

        return $url;
    }
}
