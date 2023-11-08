<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\FilePicker;

use ExpressionEngine\Library\Rte\AbstractRteFilebrowser;

class Filepicker_rtefb extends AbstractRteFilebrowser
{
    public function addJs($uploadDir)
    {
        // load the file browser
        // pass in the uploadDir to limit the directory to the one choosen
        $modal_vars = array('name' => 'modal-file', 'contents' => '');
        $modal = ee('View')->make('ee:_shared/modal')->render($modal_vars);
        ee('CP/Modal')->addModal('modal-file', $modal);

        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/files/picker'
            ),
        ));
        $fpUrl = ee('CP/FilePicker')->make($uploadDir)->getUrl();
        $fpUrl->addQueryStringVariables(array(
            'hasUpload' => true,
        ));
        ee()->javascript->set_global([
            'Rte.fpUrl' => $fpUrl->compile(),
        ]);
        ee()->javascript->output("window.Rte_browseImages = function(sourceElement, params) {
            Rte.loadEEFileBrowser(sourceElement, params, '" . $uploadDir . "', 'image');
        }");
    }

    public function getUploadDestinations()
    {
        $uploadDirs = [];
        $uploadDestinations = ee('Model')
            ->get('UploadDestination')
            ->with('Site')
            ->order('Site.site_label', 'asc')
            ->order('UploadDestination.name', 'asc')
            ->all();
        foreach ($uploadDestinations as $destination) {
            $uploadDirs[$destination->getId()] = (ee('Config')->getFile()->getBoolean('multiple_sites_enabled') ? $destination->Site->site_label . ': ' : '') . $destination->name;
        }

        return $uploadDirs;
    }
}
