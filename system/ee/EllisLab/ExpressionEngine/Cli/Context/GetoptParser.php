<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace EllisLab\ExpressionEngine\Cli\Context;

use EllisLab\ExpressionEngine\Cli\Exception;
use StdClass;

/**
 *
 * Parses command line input for named option and numeric argument values.
 *
 * @package Aura.Cli
 *
 */
class GetoptParser
{
    /**
     *
     * Any parsing errors.
     *
     * @var array
     *
     */
    protected $errors;

    /**
     *
     * The command line input to be parsed.
     *
     * @var array
     *
     */
    protected $input;

    /**
     *
     * Use these option definitions when parsing input.
     *
     * @var array
     *
     */
    protected $options;

    /**
     *
     * The values parsed from the command line input.
     *
     * @var array
     *
     */
    protected $values;

    /**
     *
     * A factory to create option objects.
     *
     * @var OptionFactory
     *
     */
    protected $option_factory;

    /**
     *
     * Constructor.
     *
     * @param OptionFactory $option_factory A factory to create option objects.
     *
     */
    public function __construct(OptionFactory $option_factory)
    {
        $this->option_factory = $option_factory;
    }

    /**
     *
     * Sets the options to be used when parsing input.
     *
     * @param array $options The array of option definitions.
     *
     */
    public function setOptions(array $options)
    {
        $this->options = array();
        foreach ($options as $string => $descr) {
            $this->setOption($string, $descr);
        }
    }

    /**
     *
     * Sets one option to be used when parsing input.
     *
     * @param string $string The option definition string.
     *
     * @param string $descr The option help description.
     *
     */
    protected function setOption($string, $descr)
    {
        $option = $this->option_factory->newInstance($string, $descr);
        if (! $option->name) {
            $this->options[] = $option;
        } else {
            $this->options[$option->name] = $option;
        }
    }

    /**
     *
     * Parses the input array according to the defined options.
     *
     * @param array $input The input array.
     *
     * @return bool False if there were parsing errors, true if there were no
     * errors.
     *
     */
    public function parseInput(array $input = array())
    {
        $this->input = $input;
        $this->errors = array();
        $this->values = array();

        // flag to say when we've reached the end of options
        $done = false;

        // sequential argument count;
        $args = 0;

        // loop through a copy of the input values to be parsed
        while ($this->input) {

            // shift each element from the top of the $this->input source
            $arg = array_shift($this->input);

            // after a plain double-dash, all values are args (not options)
            if ($arg == '--') {
                $done = true;
                continue;
            }

            // long option, short option, or numeric argument?
            if (! $done && substr($arg, 0, 2) == '--') {
                $this->setLongOptionValue($arg);
            } elseif (! $done && substr($arg, 0, 1) == '-') {
                $this->setShortFlagValue($arg);
            } else {
                $this->values[$args ++] = $arg;
            }
        }

        // done
        return $this->errors ? false : true;
    }

    /**
     *
     * Gets a single option definition converted to an array.
     *
     * Looking for an undefined option will cause an error message, but will
     * otherwise proceed. Undefined short flags are treated as rejecting a
     * param, and undefined long options are treated as taking an optional
     * param.
     *
     * @param string $name The definition key to look for.
     *
     * @return StdClass An option struct.
     *
     */
    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            $option = $this->options[$name];
        } else {
            $option = $this->getOptionByAlias($name);
        }

        if (! $option) {
            $this->errors[] = new Exception\OptionNotDefined(
                "The option '$name' is not defined."
            );
            $option = $this->option_factory->newUndefined($name);
        }

        return $option;
    }

    /**
     *
     * Gets an option by its alias.
     *
     * @param string $alias The option alias.
     *
     * @return StdClass|null Returns the matching option struct, or null if no
     * option was found with that alias.
     *
     */
    protected function getOptionByAlias($alias)
    {
        foreach ($this->options as $option) {
            if ($option->alias == $alias) {
                return $option;
            }
        }
    }

    /**
     *
     * Sets the value for a long option.
     *
     * @param string $input The current input element, e.g. "--foo" or
     * "--bar=baz" or "--bar baz".
     *
     * @return bool|null
     *
     */
    protected function setLongOptionValue($input)
    {
        list($name, $value) = $this->splitLongOptionInput($input);
        $option = $this->getOption($name);

        if ($this->longOptionRequiresValue($option, $value)) {
            $value = array_shift($this->input);
        }

        return $this->longOptionRequiresValue($option, $value, $name)
            || $this->longOptionRejectsValue($option, $value, $name)
            || $this->setValue($option, trim($value) === '' ? true : $value);
    }

    /**
     *
     * Splits the long option input into name and value.
     *
     * @param string $input The current input element, e.g. "--foo" or
     * "--bar=baz".
     *
     * @return array An array of the long option name and value.
     *
     */
    protected function splitLongOptionInput($input)
    {
        $pos = strpos($input, '=');
        if ($pos === false) {
            $name = $input;
            $value = null;
        } else {
            $name = substr($input, 0, $pos);
            $value = substr($input, $pos + 1);
        }
        return array($name, $value);
    }

    /**
     *
     * Does the long option require a param value?
     *
     * @param StdClass $option An option struct.
     *
     * @param mixed $value The option value.
     *
     * @param string $name The option name as passed.
     *
     * @return bool
     *
     */
    protected function longOptionRequiresValue($option, $value, $name = null)
    {
        if ($option->param == 'required' && trim($value) === '') {
            if ($name !== null) {
                $this->errors[] = new Exception\OptionParamRequired(
                    "The option '$name' requires a parameter."
                );
            }
            return true;
        }
        return false;
    }

    /**
     *
     * Does the long option reject a param value?
     *
     * @param StdClass $option An option struct.
     *
     * @param mixed $value The option value.
     *
     * @param string $name The option name as passed.
     *
     * @return bool
     *
     */
    protected function longOptionRejectsValue($option, $value, $name)
    {
        if ($option->param == 'rejected' && trim($value) !== '') {
            $this->errors[] = new Exception\OptionParamRejected(
                "The option '$name' does not accept a parameter."
            );
            return true;
        }
        return false;
    }

    /**
     *
     * Parses a short option or cluster of short options.
     *
     * @param string $name The current input element, e.g. "-f" or "-fbz".
     *
     * @return null
     *
     */
    protected function setShortFlagValue($name)
    {
        if (strlen($name) > 2) {
            return $this->setShortFlagValues($name);
        }

        $option = $this->getOption($name);

        return $this->shortOptionRejectsValue($option)
            || $this->shortOptionCapturesValue($option)
            || $this->shortOptionRequiresValue($option, $name)
            || $this->setValue($option, true);
    }

    /**
     *
     * Does the short option reject a value?
     *
     * @param StdClass $option An option struct.
     *
     * @return bool
     *
     */
    protected function shortOptionRejectsValue($option)
    {
        if ($option->param == 'rejected') {
            $this->setValue($option, true);
            return true;
        }
        return false;
    }

    /**
     *
     * Does the short option capture the next input element as a value?
     *
     * @param StdClass $option An option struct.
     *
     * @return bool
     *
     */
    protected function shortOptionCapturesValue($option)
    {
        $value = reset($this->input);
        $is_value = ! empty($value) && substr($value, 0, 1) != '-';
        if ($is_value) {
            $this->setValue($option, array_shift($this->input));
            return true;
        }
        return false;
    }

    /**
     *
     * Does the short option require the next input element to be a value?
     *
     * @param StdClass $option An option struct.
     *
     * @param string $name The option name.
     *
     * @return bool
     *
     */
    protected function shortOptionRequiresValue($option, $name)
    {
        if ($option->param == 'required') {
            $this->errors[] = new Exception\OptionParamRequired(
                "The option '$name' requires a parameter."
            );
            return true;
        }
        return false;
    }

    /**
     *
     * Parses a cluster of short options.
     *
     * @param string $chars The short-option cluster (e.g. "-abcd").
     *
     * @return null
     *
     */
    protected function setShortFlagValues($chars)
    {
        // drop the leading dash in the cluster and split into single chars
        $chars = str_split(substr($chars, 1));
        while ($char = array_shift($chars)) {
            $name = "-$char";
            $option = $this->getOption($name);
            if (! $this->shortOptionRequiresValue($option, $name)) {
                $this->setValue($option, true);
            }
        }
    }

    /**
     *
     * Sets an option value, adding to a value array for multi-values.
     *
     * @param StdClass $option The option struct.
     *
     * @param mixed $value The option value.
     *
     * @return null
     *
     */
    protected function setValue($option, $value)
    {
        if ($option->multi) {
            $this->addMultiValue($option, $value);
        } else {
            $this->setSingleValue($option, $value);
        }
    }

    /**
     *
     * Adds to an array of multi-values for the option.
     *
     * @param StdClass $option The option struct.
     *
     * @param mixed $value The value to add to the array of multi-values.
     *
     * @return null
     *
     */
    protected function addMultiValue($option, $value)
    {
        $this->values[$option->name][] = $value;
        if ($option->alias) {
            $this->values[$option->alias][] = $value;
        }
    }

    /**
     *
     * Sets the single value for an option.
     *
     * @param StdClass $option The option struct.
     *
     * @param mixed $value The value to set.
     *
     * @return null
     *
     */
    protected function setSingleValue($option, $value)
    {
        $this->values[$option->name] = $value;
        if ($option->alias) {
            $this->values[$option->alias] = $value;
        }
    }

    /**
     *
     * Returns the defined options.
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
     * Returns the parsed values of named options and sequential arguments.
     *
     * @return array
     *
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     *
     * Returns the parsing errors.
     *
     * @return array
     *
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
