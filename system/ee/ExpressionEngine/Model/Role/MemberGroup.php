<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Model\Role;

/**
 * Role Model
 */
class MemberGroup extends Role
{
    public function __construct()
    {
        $message = 'MemberGroup model has been removed from ExpressionEngine 6. Please use <a href="https://docs.expressionengine.com/latest/development/v6-add-on-migration.html#required-changes">Role model</a> instead.';

        $debug_backtrace = debug_backtrace(false);
        foreach ($debug_backtrace as $trace) {
            $marker = 'user' . DIRECTORY_SEPARATOR . 'addons' . DIRECTORY_SEPARATOR;
            if (isset($trace['file'])
                && isset($trace['class'])
                && is_string($trace['class'])
                && strpos($trace['file'], $marker) !== false
                && strpos($trace['class'], 'CI_DB_') === false) {
                $addon_name = explode(DIRECTORY_SEPARATOR, substr($trace['file'], strpos($trace['file'], $marker) + strlen($marker)));
                $addon = ee('Addon')->get($addon_name[0]);
                $message = $addon->getName() . ' is making a call to MemberGroup model, which has been removed from ExpressionEngine 6. If you are site owner, try upgrading ' . $addon->getName() . ' to latest available version. If you are the add-on developer, update your code to use <a href="https://docs.expressionengine.com/latest/development/v6-add-on-migration.html#required-changes">Role model</a> instead.';
            }
        }

        ee()->load->library('logger');
        ee()->logger->developer($message, true);

        throw new \Exception($message);
    }
}

// EOF
