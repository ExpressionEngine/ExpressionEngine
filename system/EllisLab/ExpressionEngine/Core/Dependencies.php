<?php
namespace EllisLab\ExpressionEngine\Core;

use \EllisLab\ExpressionEngine\Model\ModelBuilder;
use \EllisLab\ExpressionEngine\Core\Validation\Validation;

class Dependencies {

    protected $registry;

    public function __construct(Dependencies $old_di = NULL)
    {
        if (isset($old_id))
        {
            $this->registry =& $old_di->registry;
        }
        else
        {
            $this->registry = array();
        }
    }

    protected function singleton(\Closure $object)
    {
        $hash = spl_object_hash($object);

        if ( ! isset($this->registry[$hash]))
        {
            $this->registry[$hash] = $object($this);
        }

        return $this->registry[$hash];
    }

    public function getModelBuilder()
    {
        return $this->singleton(function($di)
        {
            return new ModelBuilder($di);
        });
    }

    public function getValidation()
    {
        return $this->singleton(function($di)
        {
            return new Validation($di);
        });
    }
}