<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\FilePicker\FilePicker as Picker;

/**
 * File Picker Module
 */
class Filepicker
{
    public $return_data;

    /**
     * Constructor
    */
    public function __construct()
    {
        $this->return_data = '';
    }

    public function ajaxUpload()
    {
        exit('ok');
        $picker = new Picker();
        echo 'ajaUpload';
        /*$upload = $picker->ajaxUpload();
        echo 'loaded';
        dd($upload);
        ee()->output->send_ajax_response();*/
    }
}

// EOF
