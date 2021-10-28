<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core Throttling
 */
class EE_Throttling
{
    public $throttling_enabled = false;
    public $max_page_loads = 10;
    public $time_interval = 5;
    public $lockout_time = 30;
    public $current_data = false;

    /** ----------------------------------------------
    /**  Runs the throttling funcitons
    /** ----------------------------------------------*/
    public function run()
    {
        if (ee()->config->item('enable_throttling') != 'y') {
            return;
        }

        if (! is_numeric(ee()->config->item('max_page_loads'))) {
            return;
        }

        $this->max_page_loads = ee()->config->item('max_page_loads');

        if (is_numeric(ee()->config->item('time_interval'))) {
            $this->time_interval = ee()->config->item('time_interval');
        }

        if (is_numeric(ee()->config->item('lockout_time'))) {
            $this->lockout_time = ee()->config->item('lockout_time');
        }

        $this->throttle_ip_check();
        $this->throttle_check();
        $this->throttle_update();
    }

    /** ----------------------------------------------
    /**  Is there a valid IP for this user?
    /** ----------------------------------------------*/
    public function throttle_ip_check()
    {
        if (ee()->config->item('banish_masked_ips') == 'y' and ee()->input->ip_address() == '0.0.0.0' or ee()->input->ip_address() == '') {
            $this->banish();
        }
    }

    /** ----------------------------------------------
    /**  Throttle Check
    /** ----------------------------------------------*/
    public function throttle_check()
    {
        $expire = time() - $this->time_interval;

        $query = ee()->db->query("SELECT hits, locked_out, last_activity FROM exp_throttle WHERE ip_address= '" . ee()->db->escape_str(ee()->input->ip_address()) . "'");

        if ($query->num_rows() == 0) {
            $this->current_data = array();
        }

        if ($query->num_rows() == 1) {
            $this->current_data = $query->row_array();

            $lockout = time() - $this->lockout_time;

            if ($query->row('locked_out') == 'y' and $query->row('last_activity') > $lockout) {
                $this->banish();
                exit;
            }

            if ($query->row('last_activity') > $expire) {
                if ($query->row('hits') >= $this->max_page_loads) {
                    // Lock them out and banish them...
                    ee()->db->query("UPDATE exp_throttle SET locked_out = 'y', last_activity = '" . time() . "' WHERE ip_address= '" . ee()->db->escape_str(ee()->input->ip_address()) . "'");
                    $this->banish();
                    exit;
                }
            }
        }
    }

    /** ----------------------------------------------
    /**  Throttle Update
    /** ----------------------------------------------*/
    public function throttle_update()
    {
        if ($this->current_data === false) {
            $query = ee()->db->query("SELECT hits, last_activity FROM exp_throttle WHERE ip_address= '" . ee()->db->escape_str(ee()->input->ip_address()) . "'");
            $this->current_data = ($query->num_rows() == 1) ? $query->row_array() : array();
        }

        if (count($this->current_data) > 0) {
            $expire = time() - $this->time_interval;

            if ($this->current_data['last_activity'] > $expire) {
                $hits = $this->current_data['hits'] + 1;
            } else {
                $hits = 1;
            }

            ee()->db->query("UPDATE exp_throttle SET hits = '{$hits}', last_activity = '" . time() . "', locked_out = 'n' WHERE ip_address= '" . ee()->db->escape_str(ee()->input->ip_address()) . "'");
        } else {
            ee()->db->query("INSERT INTO exp_throttle (ip_address, last_activity, hits) VALUES ('" . ee()->db->escape_str(ee()->input->ip_address()) . "', '" . time() . "', '1')");
        }
    }

    /** ----------------------------------------------
    /**  Banish User
    /** ----------------------------------------------*/
    public function banish()
    {
        $type = ((ee()->config->item('banishment_type') == 'redirect' and ee()->config->item('banishment_url') == '') or (ee()->config->item('banishment_type') == 'message' and ee()->config->item('banishment_message') == '')) ? '404' : ee()->config->item('banishment_type');

        switch ($type) {
            case 'redirect':	$loc = (strncasecmp(ee()->config->item('banishment_url'), 'http://', 7) != 0) ? 'http://' . ee()->config->item('banishment_url') : ee()->config->item('banishment_url');
                                header("location:$loc");

                break;
            case 'message':	echo ee()->config->item('banishment_message');

                break;
            default:	header("Status: 404 Not Found"); echo "Status: 404 Not Found";

                break;
        }

        exit;
    }
}
// END CLASS

// EOF
