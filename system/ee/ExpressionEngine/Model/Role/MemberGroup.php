<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Model\Role;

/**
 * Role Model
 */
class MemberGroup extends Role {
    
    public function __construct()
    {
        $message = 'MemberGroup model has been removed from ExpressionEngine 6. Please use <a href="https://docs.expressionengine.com/v6/development/v6-add-on-migration.html#required-changes">Role model</a> instead.';

        ee()->load->library('logger');
        ee()->logger->developer($message, true);
        
        throw new \Exception($message);
    }

}

// EOF
