<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Member;

use ExpressionEngine\Service\Model\Model;

/**
 * HTML Button
 *
 * These are the buttons that appear on the PUBLISH page.
 * Each member can have their own set of buttons
 */
class HTMLButton extends Model
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'html_buttons';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        ),
        'Member' => array(
            'type' => 'belongsTo'
        ),
    );

    protected static $_typed_columns = array(
        'tag_order' => 'int',
        'tag_row' => 'int',
    );

    protected static $_validation_rules = array(
        'tag_name' => 'required',
        'tag_open' => 'required',
        'tag_close' => 'required',
        'accesskey' => 'required',
        'tag_order' => 'required|isNatural',
    );

    // Properties
    protected $id;
    protected $site_id;
    protected $member_id;
    protected $tag_name;
    protected $tag_open;
    protected $tag_close;
    protected $accesskey;
    protected $tag_order;
    protected $tag_row;
    protected $classname;

    public function prepForJSON()
    {
        if (! empty($this->classname) && strpos($this->classname, 'markItUpSeparator') !== false) {
            // separators are purely presentational
            $button_js = array('separator' => '---');
        } else {
            $button_js = array(
                'name' => htmlentities($this->tag_name, ENT_QUOTES, 'UTF-8'),
                'key' => strtoupper($this->accesskey),
                'openWith' => $this->tag_open,
                'closeWith' => $this->tag_close,
                'className' => $this->classname . ' id' . $this->id
            );
        }

        return $button_js;
    }
}

// EOF
