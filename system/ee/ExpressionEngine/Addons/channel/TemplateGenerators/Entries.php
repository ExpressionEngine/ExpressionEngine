<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Channel\TemplateGenerators;

use ExpressionEngine\Service\TemplateGenerator\AbstractTemplateGenerator;

class Entries extends Channels
{
    protected $name = 'channel_entries_template_generator';

    protected $excludeFrom = [];

    protected $templates = [
        'index' => 'Listing for all entries',
        'entry' => 'Entry detail page',
        'archive' => 'Entry listing for a given year and month',
        'category' => 'Entry listing for a given category',
        'feed' => ['name' => 'RSS feed for all entries', 'type' => 'feed'],
        'sitemap' => ['name' => 'XML sitemap for all entries', 'type' => 'xml'],
    ];

    protected $includes = [
        '_comment_form' => ['templates' => 'entry']
    ];

    protected $options = [
        'channel' => [
            'desc' => 'select_channels_to_generate',
            'type' => 'checkbox',
            'required' => true,
            'choices' => 'getChannelList',
        ],
        'show_comments' => [
            'desc' => 'show_comments_desc',
            'type' => 'toggle',
            'required' => false,
        ],
    ];


}
