<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */

namespace ExpressionEngine\Cli\Stdio;

/**
 *
 * An object-oriented wrapper for file/stream resources.
 *
 * @package Aura.Cli
 *
 */
class Handle
{
    /**
     *
     * The resource represented by the handle.
     *
     * @var resource
     *
     */
    protected $resource;

    /**
     *
     * The resource name, typically a file or stream name.
     *
     * @var string
     *
     */
    protected $name;

    /**
     *
     * The mode under which the resource was opened.
     *
     * @var string
     *
     */
    protected $mode;

    /**
     *
     * What is the current OS? (Used for POSIX checks.)
     *
     * @var string
     *
     */
    protected $php_os;

    /**
     *
     * Is this a POSIX terminal?
     *
     * @var bool
     *
     */
    protected $posix;

    /**
     *
     * Constructor.
     *
     * @param string $name The resource to open, typically a PHP stream
     * like "php://stdout".
     *
     * @param string $mode Open the resource in this mode; e.g., "w+".
     *
     * @param string $php_os Force this PHP OS value; generally for testing.
     *
     * @param bool $posix Force this POSIX flag; generally for testing.
     *
     */
    public function __construct($name, $mode, $php_os = null, $posix = null)
    {
        $this->name = $name;
        $this->mode = $mode;
        $this->resource = fopen($this->name, $this->mode);
        $this->setPhpOs($php_os);
        $this->setPosix($posix);
    }

    /**
     *
     * Sets `$php_os`.
     *
     * @param string $php_os The operating system; if empty, uses the value of
     * the PHP_OS constant.
     *
     * @return null
     *
     */
    protected function setPhpOs($php_os)
    {
        $this->php_os = $php_os;
        if (! $this->php_os) {
            $this->php_os = PHP_OS;
        }
    }

    /**
     *
     * Sets `$posix`.
     *
     * @param mixed $posix If boolean, forces the $posix value; otherwise,
     * checks the `$php_os` value and `posix_isatty()` to auto-determine the
     * value.
     *
     * @return null
     *
     */
    protected function setPosix($posix)
    {
        // forcing it off or on?
        if (is_bool($posix)) {
            $this->posix = $posix;

            return;
        }

        // windows?
        if (strtolower(substr($this->php_os, 0, 3)) == 'win') {
            // windows is not posix
            $this->posix = false;

            return;
        }

        // auto-determine; silence posix_isatty() errors regarding
        // non-standard resources, e.g. php://memory
        if (function_exists('posix_isatty')) {
            $level = error_reporting(0);
            $this->posix = posix_isatty($this->resource);
            error_reporting($level);
        }
    }

    /**
     *
     * Destructor; closes the resource if it is not already closed.
     *
     * @return null
     *
     */
    public function __destruct()
    {
        if ($this->resource) {
            fclose($this->resource);
        }
    }

    /**
     *
     * Returns the resource name.
     *
     * @return string
     *
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * Returns the resource mode.
     *
     * @return string
     *
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     *
     * Reads 8192 bytes from the resource.
     *
     * @return mixed The string read on success, or boolean false on failure.
     *
     */
    public function fread()
    {
        return fread($this->resource, 8192);
    }

    /**
     *
     * Writes a string to the resource.
     *
     * @param string $string
     *
     * @return int The number of bytes written on success, or boolean false on
     * failure.
     *
     */
    public function fwrite($string)
    {
        return fwrite($this->resource, $string);
    }

    /**
     *
     * Rewinds the resource pointer.
     *
     * @return bool
     *
     */
    public function rewind()
    {
        return rewind($this->resource);
    }

    /**
     *
     * Reads a line from the resource.
     *
     * @return string The line on success, or boolean false on failure.
     *
     */
    public function fgets()
    {
        return fgets($this->resource);
    }

    /**
     *
     * Does the resource represent an interactive terminal?
     *
     * @return bool
     *
     */
    public function isPosix()
    {
        return $this->posix;
    }
}
