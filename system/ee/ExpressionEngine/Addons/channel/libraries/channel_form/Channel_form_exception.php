<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Channel Form Exception Class
 */
class Channel_form_exception extends Exception
{
    private $_type;

    /**
     * Override the constructor to work more like show_user_error
     */
    public function __construct($message, $type = 'submission')
    {
        if (is_array($message)) {
            $message = implode("</li>\n<li>", $message);
        }

        parent::__construct($message);
        $this->_type = $type;
    }

    /**
     * Custom accessor to show the user error at the catch site
     */
    public function show_user_error()
    {
        return ee()->output->show_user_error($this->_type, $this->getMessage());
    }
}
