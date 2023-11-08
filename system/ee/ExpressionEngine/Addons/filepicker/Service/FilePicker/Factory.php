<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\FilePicker\Service\FilePicker;

use Cp;
use ExpressionEngine\Service\URL\URLFactory;
use ExpressionEngine\Service\Modal\ModalCollection;
use ExpressionEngine\Service\View\View;

/**
 * FilePicker Factory
 */
class Factory
{
    protected $url;

    public function __construct(UrlFactory $url)
    {
        $this->url = $url;
    }

    /**
     * Inject the Filepicker modal into the CP. Called from the DI, do not
     * call manually.
     */
    public function injectModal(ModalCollection $modals, View $modal_view, Cp $cp)
    {
        $modal_vars = array('name' => 'modal-file', 'contents' => '');
        $modal = $modal_view->render($modal_vars);

        $modals->addModal('modal-file', $modal);
        $cp->add_js_script('file', 'cp/files/picker');
    }

    /**
     * Construct a filepicker instance
     *
     * @param String $dirs Allowed directories
     * @return FilePicker
     */
    public function make($dirs = 'all')
    {
        $fp = new FilePicker($this->url);
        $fp->setDirectories($dirs);

        return $fp;
    }
}

// EOF
