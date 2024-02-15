<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Filter;

use ExpressionEngine\Library\CP\URL;
use ExpressionEngine\Service\View\ViewFactory;
use ExpressionEngine\Model\Content\StructureModel;

/**
 * Columns Filter for Logs
 */
class LogManagerColumns extends Columns
{
    //view_id might hold view type here
    public function __construct(array $columns = array(), StructureModel $channelStructure = null, $view_id_or_type = null)
    {
        parent::__construct($columns, $channelStructure, $view_id_or_type);

        $this->default_value = ['log_date', 'channel', 'level', 'message'];
    }

    // get columns from view
    public function value()
    {
        $value = '';

        $channel = null;
        if (!empty(ee('Request')->get('channel'))) {
            $channel = ee('Security/XSS')->clean(ee('Request')->get('channel'));
        }

        $query = ee('Model')->get('LogManagerView')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->filter('channel', $channel);
        $view = $query->first(true);

        if (!empty($view)) {
            $value = $view->getColumns();
        }

        if (empty($value)) {
            $value = $this->default_value;
        }

        return $value;
    }
}

// EOF
