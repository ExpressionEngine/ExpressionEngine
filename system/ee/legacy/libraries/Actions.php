<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Action Handler
 *
 * Actions are events that require processing. Normally when you use ExpressionEngine,
 * either a web page (template), or the control panel is displayed. There are times,
 * however, when we need to process user-submitted data. Examples of these include:
 *
 * - Logging in
 * - Logging out
 * - New member registration
 *  etc...
 *
 * In these examples, information submitted from a user needs to be received and processed.  Since
 * ExpressionEngine uses only one execution file (index.php) we need a way to know that an
 * action is being requested.
 *
 * The way actions work is this:
 *
 * Anytime a GET or POST request contains the ACT variable, ExpressionEngine will run the Actions class and
 * process the requested action.
 *
 * Note: The database contains a table called "exp_actions".  This table contains a list
 * of every available action (and the associated class and method).  When an action is requested,
 * the database is queried to get the information needed to process the action.
 *
 * When a new module is installed, ExpressionEngine will update the action table.  When a module
 * is de-installed, the actions are deleted.
 */
class EE_Actions
{
    /**
     * Constructor
     *
     * Loads the class and calls the method associated with
     * a particular action request
     *
     */
    public function __construct($can_view_system = false, $callback = null)
    {
        // Define special actions
        // These are actions that are triggered manually
        // rather than doing a lookup in the actions table.
        $specials = array(
            'jquery' => array('Jquery', 'output_javascript'),
            'comment_editor' => array('Comment', 'comment_editor')
        );

        // Make sure the ACT variable is set
        if (! $action_id = ee()->input->get_post('ACT')) {
            return false;
        }

        // Fetch the class and method name (checks to make sure module is installed too)
        // If the ID is numeric we need to do an SQL lookup
        if (is_numeric($action_id)) {
            ee()->db->select('class, method, csrf_exempt');
            ee()->db->where('action_id', $action_id);
            $query = ee()->db->get('actions');

            if ($query->num_rows() == 0) {
                if (ee()->config->item('debug') >= 1) {
                    ee()->output->fatal_error(ee()->lang->line('invalid_action'));
                } else {
                    return false;
                }
            }

            $class = ucfirst($query->row('class'));
            $method = $query->row('method');
            $csrf_exempt = (bool) $query->row('csrf_exempt');
        } else {
            // If the ID is not numeric we'll invoke the class/method manually
            if (! isset($specials[$action_id])) {
                return false;
            }

            $class = $specials[$action_id]['0'];
            $method = $specials[$action_id]['1'];
            $csrf_exempt = false;

            // Double check that the module is actually installed
            ee()->db->select('module_version');
            ee()->db->where('module_name', ucfirst($class));
            $query = ee()->db->get('modules');

            if ($query->num_rows() == 0) {
                if (ee()->config->item('debug') >= 1) {
                    ee()->output->fatal_error(ee()->lang->line('invalid_action'));
                } else {
                    return false;
                }
            }
        }

        // What type of module is being requested?
        if (substr($class, -4) == '_mcp') {
            $type = 'mcp';

            $base_class = strtolower(substr($class, 0, -4));
        } else {
            if ($can_view_system === false) {
                ee()->output->system_off_msg();
                exit;
            }

            $type = 'mod';

            $base_class = strtolower($class);
        }

        // Assign the path
        $package_path = PATH_ADDONS . $base_class . '/';

        // Third parties have a different package and view path
        if (! in_array($base_class, ee()->core->native_modules)) {
            $package_path = PATH_THIRD . $base_class . '/';
        }

        ee()->load->add_package_path($package_path, false);

        $addon = ee('Addon')->get($base_class);

        if (! $addon) {
            if (ee()->config->item('debug') >= 1) {
                ee()->output->fatal_error(ee()->lang->line('invalid_action'));
            } else {
                return false;
            }
        }

        if ($type == 'mcp') {
            $fqcn = $addon->getControlPanelClass();
        } else {
            $fqcn = $addon->getModuleClass();
        }

        // Instantiate the class/method
        $ACT = new $fqcn(0);

        $flags = 0;

        if (! AJAX_REQUEST || $ACT instanceof Strict_XID) {
            $flags |= EE_Security::CSRF_STRICT;
        }

        if ($csrf_exempt) {
            $flags |= EE_Security::CSRF_EXEMPT;
        }

        ee()->core->process_secure_forms($flags);

        if ($method != '') {
            // If the stored method name is not callable, we use the snakecase version
            if (! is_callable(array($ACT, $method))) {
                $method = ee('Str')->snakecase($method);
            }

            // If it's still not callable, stop here
            if (! is_callable(array($ACT, $method))) {
                if (ee()->config->item('debug') >= 1) {
                    ee()->output->fatal_error(ee()->lang->line('invalid_action'));
                } else {
                    return false;
                }
            }

            if (is_callable($callback)) {
                call_user_func($callback, $class, $method);
            }

            $ACT->$method();
        }

        // remove the temporarily added path for local libraries, models, etc.
        ee()->load->remove_package_path($package_path);
    }
}
// END CLASS

// EOF
