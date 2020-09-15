<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace EllisLab\ExpressionEngine\Cli\Context;

/**
 *
 * A read-only representation of named option and numeric argument values.
 *
 * @package Aura.Cli
 *
 */
class Getopt extends AbstractValues
{
    /**
     *
     * Any getopt parsing errors.
     *
     * @var array
     *
     */
    protected $errors = array();

    /**
     *
     * Constructor.
     *
     * @param array $values The values to be represented by this object.
     *
     * @param array $errors Any getopt parsing errors.
     *
     */
    public function __construct(
        array $values = array(),
        array $errors = array()
    ) {
        parent::__construct($values);
        $this->errors = $errors;
    }

    /**
      *
      * Are there error messages related to getopt parsing?
      *
      * @return bool
      *
      */
    public function hasErrors()
    {
        return $this->errors ? true : false;
    }

    /**
     *
     * Returns the error messages related to getopt parsing.
     *
     * @return array
     *
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
