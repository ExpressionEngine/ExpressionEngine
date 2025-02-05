<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\TemplateGenerator\Exceptions;

use ExpressionEngine\Service\Validation\Result;

class ValidationException extends \Exception
{

    protected $result;

    public function __construct($message, Result $result)
    {
        parent::__construct($message);
        $this->result = $result;
    }

    public function getResult()
    {
        return $this->result;
    }
}
