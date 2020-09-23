<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace EllisLab\ExpressionEngine\Cli;

use EllisLab\ExpressionEngine\Cli\Context\OptionFactory;

/**
 *
 * Represents the "help" information for a command.
 *
 * @package Aura.Cli
 *
 */
class Help
{
    /**
     *
     * The long-form help text.
     *
     * @var string
     *
     */
    protected $descr;

    /**
     *
     * A option factory.
     *
     * @var OptionFactory
     *
     */
    protected $option_factory;

    /**
     *
     * The option definitions.
     *
     * @var array
     *
     */
    protected $options = array();

    /**
     *
     * A single-line summary for the command.
     *
     * @var string
     *
     */
    protected $summary;

    /**
     *
     * One or more single-line usage examples.
     *
     * @var string|array
     *
     */
    protected $usage;

    /**
     *
     * Constructor.
     *
     * @param OptionFactory $option_factory An option factory.
     *
     */
    public function __construct(OptionFactory $option_factory)
    {
        $this->option_factory = $option_factory;
        $this->init();
    }

    /**
     *
     * Use this to initialize the help object in child classes.
     *
     * @return null
     *
     */
    protected function init()
    {
        $this->summary = '';

        return $this;
    }

    /**
     *
     * Sets the option definitions.
     *
     * @param array $options The option definitions.
     *
     * @return null
     *
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     *
     * Gets the option definitions.
     *
     * @return array
     *
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     *
     * Sets the single-line summary.
     *
     * @param string $summary The single-line summary.
     *
     * @return null
     *
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     *
     * Gets the single-line summary.
     *
     * @return string
     *
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     *
     * Sets the custom usage line(s).
     *
     * @param string|array $usage The usage line(s).
     *
     * @return null
     *
     */
    public function setUsage($usage)
    {
        $this->usage = $usage;

        return $this;
    }

    /**
     *
     * Sets the long-form description.
     *
     * @param string $descr The long-form description.
     *
     * @return null
     *
     */
    public function setDescr($descr)
    {
        $this->descr = $descr;

        return $this;
    }

    /**
     *
     * Gets the formatted help output.
     *
     * @param string $name The command name.
     *
     * @return string
     *
     */
    public function getHelp($name)
    {
        $help = $this->getHelpSummary($name)
              . $this->getHelpUsage($name)
              . $this->getHelpDescr()
              . $this->getHelpArguments()
              . $this->getHelpOptions()
        ;

        if (! $help) {
            $help = "No help available.";
        }

        return rtrim($help) . PHP_EOL;
    }

    /**
     *
     * Gets the formatted summary output.
     *
     * @param string $name The command name.
     *
     * @return string
     *
     */
    protected function getHelpSummary($name)
    {
        if (! $this->summary) {
            return;
        }

        return "<<bold>>SUMMARY<<reset>>" . PHP_EOL
             . "    <<bold>>{$name}<<reset>> -- " . $this->getSummary()
             . PHP_EOL . PHP_EOL;
    }

    /**
     *
     * Gets the formatted usage output.
     *
     * @param string $name The command name.
     *
     * @return string
     *
     */
    protected function getHelpUsage($name)
    {
        // hack to maintain back-compat. basically, if there's nothing else,
        // there shouldn't be automatic usage displayed either.
        $empty = ! $this->usage
              && ! $this->options
              && ! $this->summary
              && ! $this->descr;

        if ($empty) {
            return '';
        }

        $usages = $this->getUsage();
        if (! $usages) {
            return;
        }

        $text = "<<bold>>USAGE<<reset>>" . PHP_EOL;
        foreach ($usages as $usage) {
            if ($usage) {
                $usage = " {$usage}";
            }
            $text .= "    <<ul>>$name<<reset>>{$usage}" . PHP_EOL;
        }
        return $text . PHP_EOL;
    }

    /**
     *
     * Returns the custom usage line(s), or the default usage line.
     *
     * @return array
     *
     */
    protected function getUsage()
    {
        $usage = $this->usage;
        if (! $this->usage) {
            $usage = $this->getUsageArguments();
        }
        return (array) $usage;
    }

    /**
     *
     * Returns the arguments for the default usage line.
     *
     * @return string
     *
     */
    protected function getUsageArguments()
    {
        $args = array();
        foreach ($this->options as $string => $descr) {
            $option = $this->option_factory->newInstance($string, $descr);
            $this->addUsageArgument($args, $option);
        }
        return implode(' ', $args);
    }

    /**
     *
     * Adds a default usage argument.
     *
     * @param array $args The default usage arguments.
     *
     * @param StdClass $option An option struct.
     *
     */
    protected function addUsageArgument(&$args, $option)
    {
        if ($option->name) {
            // an argument, not an option
            return;
        }

        $arg =  '<' . $option->alias . '>';

        if ($option->param == 'argument-optional') {
            $arg = "[{$arg}]";
        }

        $args[] = $arg;
    }

    /**
     *
     * Returns the formatted argument descriptions.
     *
     * @return string
     *
     */
    protected function getHelpArguments()
    {
        $args = array();
        foreach ($this->options as $string => $descr) {
            $option = $this->option_factory->newInstance($string, $descr);
            if (! $option->name) {
                $args[] = $option;
            }
        }

        if (! $args) {
            return;
        }

        $text = '';
        foreach ($args as $arg) {
            $text .= "    <{$arg->alias}>" . PHP_EOL;
            if ($arg->descr) {
                $text .= "        {$arg->descr}";
            } else {
                $text .= "        No description.";
            }
            $text .= PHP_EOL . PHP_EOL;
        }

        return "<<bold>>ARGUMENTS<<reset>>" . PHP_EOL
             . "    " . trim($text) . PHP_EOL . PHP_EOL;
    }

    /**
     *
     * Gets the formatted options output.
     *
     * @return string
     *
     */
    protected function getHelpOptions()
    {
        if (! $this->options) {
            return;
        }

        $text = "<<bold>>OPTIONS<<reset>>" . PHP_EOL;
        foreach ($this->options as $string => $descr) {
            $option = $this->option_factory->newInstance($string, $descr);
            $text .= $this->getHelpOption($option);
        }
        return $text;
    }

    /**
     *
     * Gets the formatted output for one option.
     *
     * @param \stdClass $option An option structure.
     *
     * @return string
     *
     */
    protected function getHelpOption($option)
    {
        if (! $option->name) {
            // it's an argument
            return '';
        }

        $text = "    "
              . $this->getHelpOptionParam($option->name, $option->param, $option->multi)
              . PHP_EOL;

        if ($option->alias) {
            $text .= "    "
                   . $this->getHelpOptionParam($option->alias, $option->param, $option->multi)
                   . PHP_EOL;
        }

        if (! $option->descr) {
            $option->descr = 'No description.';
        }

        return $text
             . "        " . trim($option->descr) . PHP_EOL . PHP_EOL;
    }

    /**
     *
     * Gets the formatted output for an option param.
     *
     * @param string $name The option name.
     *
     * @param string $param The option param flag.
     *
     * @param bool $multi The option multi flag.
     *
     * @return string
     *
     */
    protected function getHelpOptionParam($name, $param, $multi)
    {
        $text = "{$name}";
        if (strlen($name) == 2) {
            $text .= $this->getHelpOptionParamShort($param);
        } else {
            $text .= $this->getHelpOptionParamLong($param);
        }

        if ($multi) {
            $text .= " [$text [...]]";
        }
        return $text;
    }

    /**
     *
     * Gets the formatted output for a short option param.
     *
     * @param string $param The option param flag.
     *
     * @return string
     *
     */
    protected function getHelpOptionParamShort($param)
    {
        if ($param == 'required') {
            return " <value>";
        }

        if ($param == 'optional') {
            return " [<value>]";
        }

        return "";
    }

    /**
     *
     * Gets the formatted output for a long option param.
     *
     * @param string $param The option param flag.
     *
     * @return string
     *
     */
    protected function getHelpOptionParamLong($param)
    {
        if ($param == 'required') {
            return "=<value>";
        }

        if ($param == 'optional') {
            return "[=<value>]";
        }

        return "";
    }

    /**
     *
     * Gets the formatted output for the long-form description.
     *
     * @return string
     *
     */
    public function getHelpDescr()
    {
        if (! $this->descr) {
            return;
        }

        return "<<bold>>DESCRIPTION<<reset>>" . PHP_EOL
             . "    " . trim($this->descr) . PHP_EOL . PHP_EOL;
    }
}
