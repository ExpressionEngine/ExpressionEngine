<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Rte\Service;

use Cache;

abstract class AbstractRteService implements RteService {

    public function init($settings, $toolset = null)
    {
        $this->settings = $settings;
        $this->toolset = $toolset;
        $this->includeFieldResources();
        $this->insertConfigJsById();
        return $this->handle;
    }

    abstract protected function includeFieldResources();
    abstract protected function insertConfigJsById();

    public function getClass()
    {
        return $this->class;
    }

    protected function includeCustomCSS($configHandle, $templateId, $cssPrefix)
    {
        // Include custom CSS
        if (! empty($templateId)) {
            if (!isset(ee()->CSS_TMPL)) {
                ee()->load->library('template', null, 'CSS_TMPL');
            }
            $templateModel = ee('Model')->get('Template', $templateId)->with('TemplateGroup')->filter('template_type', 'css')->first(true);
            if (! empty($templateModel)) {
                ee()->CSS_TMPL->fetch_and_parse($templateModel->TemplateGroup->group_name, $templateModel->template_name, false, $templateModel->site_id);
                if (! empty(ee()->CSS_TMPL->final_template)) {
                    // check the cached version and see if we can just use it
                    $cache_key = '/rte/' . $configHandle . '/css';
                    $cachedCss = ee()->cache->get($cache_key, Cache::GLOBAL_SCOPE);
                    if ($cachedCss !== false) {
                        $finfo = ee()->cache->file->get_metadata($cache_key, Cache::GLOBAL_SCOPE);
                        if ($finfo['mtime'] >= ee()->CSS_TMPL->template_edit_date) {
                            $prefixedCss = $cachedCss;
                        }
                    }
                    //no cache file, or file too old
                    if (! isset($prefixedCss)) {
                        $cssTemplate = ee()->CSS_TMPL->parse_globals(ee()->CSS_TMPL->final_template);
                        $parser = new \ExpressionEngine\Dependency\Sabberworm\CSS\Parser($cssTemplate);
                        $cssDocument = $parser->parse();
                        foreach ($cssDocument->getAllDeclarationBlocks() as $block) {
                            foreach ($block->getSelectors() as $selectorNode) {
                                // Loop over all selector parts (the comma-separated strings in a
                                // selector) and prepend the class.
                                $selector = $selectorNode->getSelector();
                                if (strpos($selector, '.redactor-toolbar') === false && strpos($selector, '.ck-toolbar') === false) {
                                    if (strpos($selector, '.redactor-styles') === 0 || strpos($selector, '.ck-content') === 0) {
                                        $selectorNode->setSelector($cssPrefix . $selector);
                                    } else {
                                        $selectorNode->setSelector($cssPrefix . ' ' . $selector);
                                    }
                                }
                            }
                        }
                        $prefixedCss = $cssDocument->render(\ExpressionEngine\Dependency\Sabberworm\CSS\OutputFormat::createCompact());
                        ee()->cache->save($cache_key, $prefixedCss, 60*60*24*365, Cache::GLOBAL_SCOPE);
                    }
                    // serve the CSS
                    ee()->cp->add_to_head('<style type="text/css">' . $prefixedCss . '</style>');
                }
            }
        }
    }


}
