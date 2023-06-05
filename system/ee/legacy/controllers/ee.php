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
 * This class doesn't do much work.  Most of the heavy lifting is done via
 * libraries/Core.php, which runs automatically behind the scenes.
 */
class EE extends EE_Controller
{
    /**
     * Index
     */
    public function index()
    {
        // If the REQ constant isn't defined it means the EE has not
        // been initialized.  This can happen if the config/autoload.php
        // file is not set-up to automatically run the libraries/Core.php class.
        // We'll set REQ to FALSE so that it shows an error message below
        if (! defined('REQ')) {
            define('REQ', false);
        }

        $can_view_system = false;

        if ($this->config->item('is_system_on') == 'y' &&
            ($this->config->item('multiple_sites_enabled') != 'y' or $this->config->item('is_site_on') == 'y')) {
            if ($this->session->userdata('can_view_online_system') != 'n') {
                $can_view_system = true;
            }
        } else {
            if ($this->session->userdata('can_view_offline_system') == 'y') {
                $can_view_system = true;
            }
        }

        $can_view_system = (ee('Permission')->isSuperAdmin()) ? true : $can_view_system;

        if (REQ != 'ACTION' && $can_view_system != true) {
            $this->output->system_off_msg();
            exit;
        }

        if (REQ == 'ACTION') {
            $this->core->generate_action($can_view_system);
        } elseif (REQ == 'PAGE') {
            $this->core->generate_page();
        } else {
            show_error('Unable to initialize ExpressionEngine.  The EE core does not appear to be defined in your autoload file.  For more information please contact technical support.');
        }
    }
}

// EOF
