<?php

namespace ExpressionEngine\Service\ConditionalFields\Contracts;

interface Conditionable {

    public function getConditionSets();

    public function setConditionSets($sets);

}