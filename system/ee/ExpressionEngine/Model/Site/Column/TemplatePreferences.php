<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Site\Column;

use ExpressionEngine\Service\Model\Column\Serialized\Base64Native;
use ExpressionEngine\Service\Model\Column\CustomType;

/**
 * Template Preferences Columns
 */
class TemplatePreferences extends CustomType
{
    protected $enable_template_routes;
    protected $strict_urls;
    protected $site_404;
    protected $save_tmpl_revisions;
    protected $max_tmpl_revisions;
    protected $tmpl_file_basepath;

    /**
    * Called when the column is fetched from db
    */
    public function unserialize($db_data)
    {
        return Base64Native::unserialize($db_data);
    }

    /**
    * Called before the column is written to the db
    */
    public function serialize($data)
    {
        return Base64Native::serialize($data);
    }
}

// EOF
