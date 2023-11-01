<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core Javascript Loader
 */
class Javascript_loader
{
    /**
     * Constructor
     */
    public function __construct()
    {
        if (!defined('PATH_JAVASCRIPT')) {
            define('PATH_JAVASCRIPT', PATH_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/');
            define('PATH_JAVASCRIPT_BUILD', PATH_THEMES . 'cp/js/build/');
        }
    }

    /**
     * Javascript Combo Loader
     *
     * Combo load multiple javascript files to reduce HTTP requests
     * BASE.AMP.'C=javascript&M=combo&ui=ui,packages&file=another&plugin=plugins&package=third,party,packages'
     *
     * @access public
     * @return string
     */
    public function combo_load()
    {
        ee()->output->enable_profiler(false);

        $contents = '';
        $types = array(
            'ui' => PATH_JAVASCRIPT . 'jquery/ui/jquery.ui.',
            'plugin' => PATH_JAVASCRIPT . 'jquery/plugins/',
            'file' => PATH_JAVASCRIPT,
            'package' => PATH_THIRD,
            'fp_module' => PATH_ADDONS,
            'pro_file' => PATH_PRO_THEMES . 'js/',
            'template' => ''
        );

        $mock_name = '';

        foreach ($types as $type => $path) {
            $mock_name .= ee()->input->get_post($type);
            $files = explode(',', ee()->input->get_post($type));

            if ($type == 'template') {
                if (!isset(ee()->JS_TMPL)) {
                    ee()->load->library('template', null, 'JS_TMPL');
                }
                foreach ($files as $templateId) {
                    $templateModel = ee('Model')->get('Template', $templateId)->with('TemplateGroup')->filter('template_type', 'js')->first(true);
                    if (! empty($templateModel)) {
                        ee()->JS_TMPL->fetch_and_parse($templateModel->TemplateGroup->group_name, $templateModel->template_name, false, $templateModel->site_id);
                        if (! empty(ee()->JS_TMPL->final_template)) {
                            $contents .= ee()->JS_TMPL->parse_globals(ee()->JS_TMPL->final_template);
                        }
                    }
                }

                continue;
            }

            foreach ($files as $file) {
                if (empty($file)) {
                    continue;
                }
                if ($type == 'package' or $type == 'fp_module') {
                    if (strpos($file, ':') !== false) {
                        list($package, $file) = explode(':', $file);
                    } else {
                        $package = $file;
                    }

                    $file = ee()->security->sanitize_filename($package . '/javascript/' . $file, true);
                } elseif ($type == 'file' or $type == 'pro_file') {
                    $parts = explode('/', $file);
                    $file = array();

                    foreach ($parts as $part) {
                        if ($part != '..') {
                            $file[] = ee()->security->sanitize_filename($part);
                        }
                    }

                    $file = implode('/', $file);
                } else {
                    $file = ee()->security->sanitize_filename($file);
                }

                // Attempt to load files from the new js folder first
                // TODO: Remove this temporary code once all js assets have been moved
                // from asset/javascript/src to cp/js
                if ($type == 'file') {
                    $new_file = PATH_JAVASCRIPT_BUILD . $file . '.js';

                    if (file_exists($new_file)) {
                        $contents .= file_get_contents($new_file) . "\n\n";

                        continue;
                    }
                }

                $fullFilePath = $path . $file . '.js';

                if (file_exists($fullFilePath)) {
                    $contents .= file_get_contents($fullFilePath) . "\n\n";
                } elseif ($type == 'package') {
                    //fallback to first-party addon package
                    $fullFilePath = PATH_ADDONS . $file . '.js';
                    if (file_exists($fullFilePath)) {
                        $contents .= file_get_contents($fullFilePath) . "\n\n";
                    } else {
                        // if still not found, check third-party themes folder
                        $fullFilePath = PATH_THIRD_THEMES . $file . '.js';
                        if (file_exists($fullFilePath)) {
                            $contents .= file_get_contents($fullFilePath) . "\n\n";
                        }
                    }
                }
            }
        }

        $modified = ee()->input->get_post('v');
        $this->set_headers($mock_name, $modified);

        ee()->output->set_header('Content-Length: ' . strlen($contents));
        ee()->output->set_output($contents);
    }

    /**
     * Set Headers
     *
     * @access  private
     * @param   string
     * @return  string
     */
    public function set_headers($file, $mtime = false)
    {
        ee()->output->out_type = 'cp_asset';
        ee()->output->set_header("Content-Type: text/javascript");

        if (ee()->config->item('send_headers') != 'y') {
            // All we need is content type - we're done
            return;
        }

        $max_age = 5184000;
        $modified = ($mtime !== false) ? $mtime : @filemtime($file);
        $modified_since = ee()->input->server('HTTP_IF_MODIFIED_SINCE');

        // Remove anything after the semicolon

        if ($pos = strrpos($modified_since, ';') !== false) {
            $modified_since = substr($modified_since, 0, $pos);
        }

        // If the file is in the client cache, we'll
        // send a 304 and be done with it.

        if ($modified_since && (strtotime($modified_since) == $modified)) {
            ee()->output->set_status_header(304);
            exit;
        }

        // Send a custom ETag to maintain a useful cache in
        // load-balanced environments

        ee()->output->set_header("ETag: " . md5($modified . $file));

        // All times GMT
        $modified = gmdate('D, d M Y H:i:s', (int) $modified) . ' GMT';
        $expires = gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT';

        ee()->output->set_status_header(200);
        ee()->output->set_header("Cache-Control: max-age={$max_age}, must-revalidate");
        ee()->output->set_header('Vary: Accept-Encoding');
        ee()->output->set_header('Last-Modified: ' . $modified);
        ee()->output->set_header('Expires: ' . $expires);
    }
}

// EOF
