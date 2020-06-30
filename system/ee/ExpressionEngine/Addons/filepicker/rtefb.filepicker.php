<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
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
        $fp = new FilePicker();
        $fp->inject(ee()->view);
        ee()->javascript->set_global([
            'Artee.fpUrl' => ee('CP/FilePicker')->make($uploadDir)->getUrl()->compile(),
        ]);
        ee()->javascript->output("window.Artee_browseImages = function(sourceElement, params) {
            Artee.loadEEFileBrowser(sourceElement, params, '" . $uploadDir . "', 'image');
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
            $uploadDirs[$destination->id] = (ee('Config')->getFile()->getBoolean('multiple_sites_enabled') ? $destination->Site->site_label . ': ' : '') . $destination->name;
        }
        return $uploadDirs;
    }
}
