<?php

namespace ExpressionEngine\Service\ConditionalFields;

use ExpressionEngine\Field;

class Operator {

    public function __construct($name) {
        $this->name = $name;
    }

    public function handler(callable $handler)
    {
        return $this->handler = $handler;
    }

    public function handle()
    {
        return $this->handler;
    }

    public function requiredParameters()
    {
        return (new \ReflectionFunction($this->handle))->getNumberOfRequiredParameters();
    }



}