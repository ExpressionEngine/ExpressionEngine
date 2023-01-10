<?php
/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Library\CP\EntryManager;

use ExpressionEngine\Library\CP\EntryManager as Core;

/**
 * Entry Manager Column Factory
 */
class ColumnFactory extends Core\ColumnFactory
{
    protected static $standard_columns = [
        'entry_id' => Core\Columns\EntryId::class,
        'title' => Columns\Title::class,
        'url_title' => Core\Columns\UrlTitle::class,
        'author' => Core\Columns\Author::class,
        'status' => Core\Columns\Status::class,
        'entry_date' => Core\Columns\EntryDate::class,
        'expiration_date' => Core\Columns\ExpirationDate::class,
        'channel' => Core\Columns\ChannelName::class,
        'comments' => Core\Columns\Comments::class,
        'categories' => Core\Columns\Categories::class,
        'checkbox' => Core\Columns\Checkbox::class
    ];
}
