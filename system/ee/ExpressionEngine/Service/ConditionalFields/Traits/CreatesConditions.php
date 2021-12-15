<?php

namespace ExpressionEngine\Service\ConditionalFields\Traits;

use ExpressionEngine\Service\ConditionalFields\Operator;

trait CreatesConditions
{
    public function getConditionalFieldOperators()
    {
        $operators = [
            'is' => function ($left, $right) {
                return $left == $right;
            },
            'is not' => function ($left, $right) {
                return $left !== $right;
            },
            'is empty' => function ($left) {
                return empty($left);
            },
            'is not empty' => function ($left) {
                return !empty($left);
            },
            'less than' => function ($left, $right) {
                return $left < $right;
            },
            'greater than' => function ($left, $right) {
                return $left > $right;
            },
            'less than or equal to' => function ($left, $right) {
                return $left <= $right;
            },
            'greater than or equal to' => function ($left, $right) {
                return $left >= $right;
            },
            'contains' => function ($left, $right) {
                return str_contains(strtolower($left), strtolower($right));
            }
        ];

        return (isset($this->conditionalFieldOperators)) ? array_intersect_key($operators, array_flip($this->conditionalFieldOperators)) : $operators;
    }

    /**
     * The input type that should be used to get a value for conditions involving this fieldtype
     *
     * @return string
     */
    public function getConditionalFieldInputType()
    {
        return 'text';
    }

    public function getConditionalFieldOperator($operator)
    {
        $operators = $this->getConditionalFieldOperators();

        if (!array_key_exists($operator, $operators)) {
            throw new \Exception(
                vsprintf('Fieldtype "%s" does not support the "%s" operator', [
                    static::class,
                    $operator
                ])
            );
        }

        return $operators[$operator];
    }

    public function getConditionalFieldParameterCountForOperator($operator)
    {
        $operator = $this->getConditionalFieldOperator($operator);

        return (new \ReflectionFunction($operator))->getNumberOfRequiredParameters() - 1;
    }
}
