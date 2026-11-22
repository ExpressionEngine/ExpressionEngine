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

use ExpressionEngine\Service\Filter\Date;
use ExpressionEngine\Library\Date\DateTrait;

/**
 * Entry Date Filter
 *
 */
class EntryDate extends Date
{
    public function __construct()
    {
        parent::__construct();
        $this->options = array(
            '86400' => ucwords(lang('last') . ' 24 ' . lang('hours')),
            '604800' => ucwords(lang('last') . ' 7 ' . lang('days')),
            '2592000' => ucwords(lang('last') . ' 30 ' . lang('days')),
            '15552000' => ucwords(lang('last') . ' 180 ' . lang('days')),
            '31536000' => ucwords(lang('last') . ' 365 ' . lang('days')),
            '0' => lang('future_entries')
        );
        $this->setSelectedValues();
    }
}

// EOF
