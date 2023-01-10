<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Formatter;

use EE_Lang;
use EE_Session;

/**
 * Formatter
 */
class Formatter
{
    /**
     * @var array Any needed app config settings
     */
    protected $config;

    /**
     * @var mixed $content Content to be formatted, typically a string or int
     **/
    protected $content;

    /**
     * @var object $lang EE_Lang
     **/
    protected $lang;

    /**
     * @var object $session EE_Session
     */
    protected $session;

    /**
     * @var boolean $intl_loaded Whether or not the intl extension is loaded
     */
    protected $intl_loaded = false;

    /**
     * @var binary (1) Bitwise options mask for intl_loaded. Can't use const until PHP 5.6
     */
    private $OPT_INTL_LOADED = 0b00000001;

    /**
     * Constructor
     *
     * @param mixed $content Content to be formatted, typically a string or int
     * @param object EE_Lang
     */
    public function __construct($content, EE_Lang $lang, EE_Session $session, $config, $options)
    {
        $this->config = $config;
        $this->content = $content;
        $this->lang = $lang;
        $this->session = $session;
        $this->lang->load('formatter');

        if ($options & $this->OPT_INTL_LOADED) {
            $this->intl_loaded = true;
        }
    }

    /**
     * Config getter
     * @param  string $item Name of the config item
     * @return mixed Config item value, or FALSE if it does not exist
     */
    protected function getConfig($item)
    {
        return (isset($this->config[$item])) ? $this->config[$item] : false;
    }

    /**
     * When accessed as a string simply complile the content and return that
     *
     * @return string The content
     */
    public function __toString()
    {
        return $this->compile();
    }

    /**
     * Compiles and returns the content as a string. Typically this is used when you
     * need to use the content as an array key, or want to json_encode() the content.
     * Formatters can override this method if they need to handle or return non-string variables
     *
     * @return string The cotnent
     */
    public function compile()
    {
        return (string) $this->content;
    }
}

// EOF
