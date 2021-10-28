<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * ExpressionEngine Stop Words
 *
 * This file contains an array of words that the search functions in EE will
 * ignore in order to a) reduce load, and b) generate better results.
 */
ee()->load->library('logger');
ee()->logger->deprecated('3.4.0', 'ee()->config->loadFile("stopwords") to load this config file', true, 604800);

$ignore = ee()->config->loadFile('stopwords');

// EOF
