<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace EllisLab\ExpressionEngine\Cli;

use EllisLab\ExpressionEngine\Cli\Stdio\Handle;
use EllisLab\ExpressionEngine\Cli\Stdio\Formatter;

/**
 *
 * Provides a wrapper for standard input/output streams.
 *
 * @package Aura.Cli
 *
 */
class Stdio
{
    /**
     *
     * A Handle object for standard input.
     *
     * @var Handle
     *
     */
    protected $stdin;

    /**
     *
     * A Handle object for standard output.
     *
     * @var Handle
     *
     */
    protected $stdout;

    /**
     *
     * A Handle object for standard error.
     *
     * @var Handle
     *
     */
    protected $stderr;

    /**
     *
     * A Formatter object to format output.
     *
     * @var Formatter
     *
     */
    protected $formatter;

    /**
     *
     * Constructor.
     *
     * @param Handle $stdin A Handle object for standard input.
     *
     * @param Handle $stdout A Handle object for standard output.
     *
     * @param Handle $stderr A Handle object for standard error.
     *
     * @param Formatter $formatter A VT100 formatting object.
     *
     */
    public function __construct(
        Handle $stdin,
        Handle $stdout,
        Handle $stderr,
        Formatter $formatter
    ) {
        $this->stdin  = $stdin;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
        $this->formatter  = $formatter;
    }

    /**
     *
     * Returns the standard input Handle object.
     *
     * @return Handle
     *
     */
    public function getStdin()
    {
        return $this->stdin;
    }

    /**
     *
     * Returns the standard output Handle object.
     *
     * @return Handle
     *
     */
    public function getStdout()
    {
        return $this->stdout;
    }

    /**
     *
     * Returns the standard error Handle object.
     *
     * @return Handle
     *
     */
    public function getStderr()
    {
        return $this->stderr;
    }

    /**
     *
     * Gets user input from the command line and trims the end-of-line.
     *
     * @return string
     *
     */
    public function in()
    {
        return rtrim($this->stdin->fgets(), PHP_EOL);
    }

    /**
     *
     * Gets user input from the command line and leaves the end-of-line in
     * place.
     *
     * @return string
     *
     */
    public function inln()
    {
        return $this->stdin->fgets();
    }

    /**
     *
     * Prints formatted text to standard output **without** a trailing newline.
     *
     * @param string $string The text to print to standard output.
     *
     * @return null
     *
     */
    public function out($string = null)
    {
        $string = $this->formatter->format($string, $this->stdout->isPosix());
        $this->stdout->fwrite($string);
    }

    /**
     *
     * Prints formatted text to standard output **with** a trailing newline.
     *
     * @param string $string The text to print to standard output.
     *
     * @return null
     *
     */
    public function outln($string = null)
    {
        $this->out($string . PHP_EOL);
    }

    /**
     *
     * Prints formatted text to standard error **without** a trailing newline.
     *
     * @param string $string The text to print to standard error.
     *
     * @return null
     *
     */
    public function err($string = null)
    {
        $string = $this->formatter->format($string, $this->stderr->isPosix());
        $this->stderr->fwrite($string);
    }

    /**
     *
     * Prints formatted text to standard error **with** a trailing newline.
     *
     * @param string $string The text to print to standard error.
     *
     * @return null
     *
     */
    public function errln($string = null)
    {
        $this->err($string . PHP_EOL);
    }
}
