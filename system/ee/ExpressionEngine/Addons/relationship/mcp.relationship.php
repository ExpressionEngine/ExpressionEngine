<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

 /**
 * Relationship Fieldtype control panel
 */
class Relationship_mcp
{
    public function ajaxFilter()
    {
        ee()->load->library('EntryList');
        ee()->output->send_ajax_response(ee()->entrylist->ajaxFilter());
    }
}

// EOF
