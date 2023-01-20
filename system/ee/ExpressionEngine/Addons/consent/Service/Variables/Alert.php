<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Consent\Service\Variables;

use ExpressionEngine\Service\Template\Variables;

/**
 * Consent Alert Variables
 */
class Alert extends Variables
{
    /**
     * @var array $alert Alert data
     */
    private $alert;

    /**
     * Constructor
     *
     * @param array $alert The Alert data
     */
    public function __construct($alert)
    {
        $this->alert = $alert;
        parent::__construct();
    }

    public function getTemplateVariables()
    {
        if (! empty($this->variables)) {
            return $this->variables;
        }

        $this->variables = [
            'alert_type' => $this->alert['type'],
            'alert_message' => $this->alert['message']
        ];

        return $this->variables;
    }
}
// END CLASS

// EOF
