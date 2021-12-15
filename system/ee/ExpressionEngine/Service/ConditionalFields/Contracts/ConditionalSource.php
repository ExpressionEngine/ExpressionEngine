<?php

namespace ExpressionEngine\Service\ConditionalFields\Contracts;

interface ConditionalSource {

    public function getConditionalFieldOperators();

    public function getConditionalFieldOperator($operator);

    public function getConditionalFieldInputType();

    public function getConditionalFieldParameterCountForOperator($operator);

}