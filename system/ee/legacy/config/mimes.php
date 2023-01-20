<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * ExpressionEngine Allowed Mime Types
 *
 * These are the mime types that are allowed to be uploaded using the
 * upload class.  For security reasons the list is kept as small as
 * possible.  If you need to upload types that are not in the list you can
 * add them.
 */
ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("mimes") to load this config file', true, 604800);

$whitelist = ee()->config->loadFile('mimes');

// EOF
