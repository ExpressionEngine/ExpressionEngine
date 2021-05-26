<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */

namespace ExpressionEngine\Cli\Context;

/**
 *
 * A factory for option objects.
 *
 * @package Aura.Cli
 *
 */
class OptionFactory
{
    /**
     *
     * Returns a new option struct from an option definition string and
     * description.
     *
     * @param string $string The option definition string.
     *
     * @param string $descr The option description.
     *
     * @return StdClass
     *
     */
    public function newInstance($string, $descr = null)
    {
        if (is_int($string)) {
            $string = $descr;
            $descr = null;
        }

        $string = trim($string);

        $option = (object) array(
            'name' => null,
            'alias' => null,
            'multi' => false,
            'param' => 'rejected',
            'descr' => $descr,
        );

        if (substr($string, 0, 1) == '#') {
            $this->setArgument($option, $string);

            return $option;
        }

        $this->setNewOptionMulti($option, $string);
        $this->setNewOptionParam($option, $string);
        $this->setNewOptionMulti($option, $string);
        $this->setNewOptionNameAlias($option, $string);

        return $option;
    }

    /**
     *
     * Sets an option as an argument, to be ignored when parsing options.
     *
     * @param StdClass $option The option struct.
     *
     * @param string $string The argument name.
     *
     * @return null
     *
     */
    protected function setArgument($option, $string)
    {
        $string = ltrim($string, '# -');

        $option->param = 'argument-required';
        if (substr($string, -1) == '?') {
            $option->param = 'argument-optional';
            $string = rtrim($string, '? -');
        }

        $option->alias = $string;
    }

    /**
     *
     * Given an undefined option name, returns a default option struct for it.
     *
     * @param string $name The undefined option name.
     *
     * @return StdClass An option struct.
     *
     */
    public function newUndefined($name)
    {
        if (strlen($name) == 1) {
            return $this->newInstance($name);
        }

        return $this->newInstance("{$name}::");
    }

    /**
     *
     * Sets the $param property on a new option struct.
     *
     * @param StdClass $option The option struct.
     *
     * @param $string The option definition string.
     *
     * @return null
     *
     */
    protected function setNewOptionParam($option, &$string)
    {
        if (substr($string, -2) == '::') {
            $option->param = 'optional';
            $string = substr($string, 0, -2);
        } elseif (substr($string, -1) == ':') {
            $option->param = 'required';
            $string = substr($string, 0, -1);
        }

        $string = rtrim($string, ':');
    }

    /**
     *
     * Sets the $multi property on a new option struct.
     *
     * @param StdClass $option The option struct.
     *
     * @param $string The option definition string.
     *
     * @return null
     *
     */
    protected function setNewOptionMulti($option, &$string)
    {
        if (substr($string, -1) == '*') {
            $option->multi = true;
            $string = substr($string, 0, -1);
        }
    }

    /**
     *
     * Sets the $name and $alias properties on a new option struct.
     *
     * @param StdClass $option The option struct.
     *
     * @param $string The option definition string.
     *
     * @return null
     *
     */
    protected function setNewOptionNameAlias($option, &$string)
    {
        $names = explode(',', $string);
        $option->name = $this->fixOptionName($names[0]);
        if (isset($names[1])) {
            $option->alias = $this->fixOptionName($names[1]);
        }
    }

    /**
      *
      * Normalizes the option name.
      *
      * @param string $name The option character or long name.
      *
      * @return The fixed name with a leading dash or dashes.
      *
      */
    protected function fixOptionName($name)
    {
        $name = trim($name, ' -');
        if (strlen($name) == 1) {
            return "-$name";
        }

        return "--$name";
    }
}
