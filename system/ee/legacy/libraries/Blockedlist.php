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
 * Core Blocked List
 */
class EE_Blockedlist
{
    public $allowed = 'n';		// Is this request allowed
    public $blocked = 'n';		// Is this request blocked.

    public $whitelisted = 'n';
    public $blacklisted = 'n';

    public function deprecate()
    {
        $this->whitelisted = $this->allowed;
        $this->blacklisted = $this->blocked;

        ee()->load->library('logger');
        $deprecated = array(
            'function' => 'ee()->blacklist',
            'deprecated_since' => '6.0.0',
            'use_instead' => 'ee()->blockedlist->blocked and ee()->blockedlist->allowed'
        );
        ee()->logger->developer($deprecated, true, 604800);
    }

    /**
     * Block and Allow Checker
     *
     * This function checks all of the available blocked lists, such as urls,
     * IP addresses, and user agents. URLs are checked as both referrers and
     * in all $_POST'ed contents (such as comments).
     *
     * @access	private
     * @return	bool
     */
    public function _check_blockedlist()
    {
        // Check the referrer
        // Since we already need to check all post values for illegal urls
        // below, we'll temporarily write our referrer to $_POST.
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '') {
            $test_ref = ee('Security/XSS')->clean($_SERVER['HTTP_REFERER']);

            if (! preg_match("#^http://\w+\.\w+\.\w*#", $test_ref)) {
                if (substr($test_ref, 0, 7) == 'http://' and substr($test_ref, 0, 11) != 'http://www.') {
                    $test_ref = preg_replace("#^http://(.+?)#", "http://www.\\1", $test_ref);
                }
            }

            $_POST['HTTP_REFERER'] = $test_ref;
        }

        // No referrer, and no posted data - no need to block.
        // In other words, if your ip is blocked you can still see the
        // site, but you can not contribute content.
        if (count($_POST) == 0) {
            return true;
        }

        ee()->load->model('addons_model');
        $installed = ee()->addons_model->module_installed('block_and_allow');

        if (! $installed) {
            unset($_POST['HTTP_REFERER']);

            return true;
        }

        // Allowed Items
        $allowedlist_ip = array();
        $allowedlist_url = array();
        $allowedlist_agent = array();

        $results = ee()->db->query("SELECT allowedlist_type, allowedlist_value FROM exp_allowedlist
										 WHERE allowedlist_value != ''");

        if ($results->num_rows() > 0) {
            foreach ($results->result_array() as $row) {
                if ($row['allowedlist_type'] == 'url') {
                    $allowedlist_url = explode('|', $row['allowedlist_value']);
                } elseif ($row['allowedlist_type'] == 'ip') {
                    $allowedlist_ip = explode('|', $row['allowedlist_value']);
                } elseif ($row['allowedlist_type'] == 'agent') {
                    $allowedlist_agent = explode('|', $row['allowedlist_value']);
                }
            }
        }

        if (ee()->config->item('cookie_domain') !== false && ee()->config->item('cookie_domain') != '') {
            $allowedlist_url[] = ee()->config->item('cookie_domain');
        }

        $site_url = ee()->config->item('site_url');

        $allowedlist_url[] = $site_url;

        if (! preg_match("#^http://\w+\.\w+\.\w*#", $site_url)) {
            if (substr($site_url, 0, 7) == 'http://' and substr($site_url, 0, 11) != 'http://www.') {
                $allowedlist_url[] = preg_replace("#^http://(.+?)#", "http://www.\\1", $site_url);
            }
        }

        // Domain Names Array
        $domains = array('net','com','org','info', 'name','biz','us','de', 'uk');

        // blockedlist Checking
        $query = ee()->db->query("SELECT blockedlist_type, blockedlist_value FROM exp_blockedlist");

        if ($query->num_rows() == 0) {
            unset($_POST['HTTP_REFERER']);

            return true;
        }

        foreach ($query->result_array() as $row) {
            if ($row['blockedlist_type'] == 'url' && $row['blockedlist_value'] != '' && $this->allowed != 'y') {
                $blocked_values = explode('|', $row['blockedlist_value']);

                if (! is_array($blocked_values) or count($blocked_values) == 0) {
                    continue;
                }

                foreach ($_POST as $key => $value) {
                    // Smallest URL Possible
                    // Or no external links
                    if (is_array($value) or strlen($value) < 8) {
                        continue;
                    }

                    // Convert Entities Before Testing
                    $value = ee('Security/XSS')->entity_decode($value);
                    $value .= ' ';

                    // Clear period from the end of URLs
                    $value = preg_replace("#(^|\s|\()((http://|http(s?)://|www\.)\w+[^\s\)]+)\.([\s\)])#i", "\\1\\2{{PERIOD}}\\4", $value);

                    // Sometimes user content such as comments contain multiple
                    // urls, so we need to check them individually.
                    if (preg_match_all("/([f|ht]+tp(s?):\/\/[a-z0-9@%_.~#\/\-\?&=]+.)" .
                                        "|(www.[a-z0-9@%_.~#\-\?&]+.)" .
                                        "|([a-z0-9@%_~#\-\?&]*\.(" . implode('|', $domains) . "))/si", $value, $matches)) {
                        for ($i = 0; $i < count($matches['0']); $i++) {
                            // If this is a referrer or the comment module's
                            // url field we know that it's just a single match.
                            if ($key == 'HTTP_REFERER' or $key == 'url') {
                                $matches['0'][$i] = $value;
                            }

                            foreach ($blocked_values as $bad_url) {
                                if ($bad_url != '' && stristr($matches['0'][$i], $bad_url) !== false) {
                                    $bad = 'y';

                                    // Check Bad Against Whitelist - URLs

                                    if (is_array($allowedlist_url) && count($allowedlist_url) > 0) {
                                        $parts = explode('?', $matches['0'][$i]);

                                        foreach ($allowedlist_url as $pure) {
                                            if ($pure != '' && stristr($parts['0'], $pure) !== false) {
                                                $bad = 'n';
                                                $this->allowed = 'y';

                                                break;
                                            }
                                        }
                                    }

                                    // Check Bad Against Whitelist - IPs
                                    if (is_array($allowedlist_ip) && count($allowedlist_ip) > 0) {
                                        foreach ($allowedlist_ip as $pure) {
                                            if ($pure != '' && strpos(ee()->input->ip_address(), $pure) !== false) {
                                                $bad = 'n';
                                                $this->allowed = 'y';

                                                break;
                                            }
                                        }
                                    }

                                    if ($bad == 'y') {
                                        // Referer mismatches get a access denied error
                                        // since the url error doesn't make sense for a
                                        // user who didn't take any actions.
                                        if ($key == 'HTTP_REFERER') {
                                            $this->blocked = 'y';
                                        } else {
                                            exit('Action Denied: Blocked Item Found' . "\n<br/>" . $matches['0'][$i]);
                                        }
                                    } else {
                                        break;  // Free to move on
                                    }
                                }
                            }
                        }
                    }
                }
            } elseif ($row['blockedlist_type'] == 'ip' && $row['blockedlist_value'] != '' && $this->allowed != 'y') {
                $blocked_values = explode('|', $row['blockedlist_value']);

                if (! is_array($blocked_values) or count($blocked_values) == 0) {
                    continue;
                }

                foreach ($blocked_values as $bad_ip) {
                    if ($bad_ip != '' && strpos(ee()->input->ip_address(), $bad_ip) === 0) {
                        $bad = 'y';

                        if (is_array($allowedlist_ip) && count($allowedlist_ip) > 0) {
                            foreach ($allowedlist_ip as $pure) {
                                if ($pure != '' && strpos(ee()->input->ip_address(), $pure) !== false) {
                                    $bad = 'n';
                                    $this->allowed = 'y';

                                    break;
                                }
                            }
                        }

                        if ($bad == 'y') {
                            $this->blocked = 'y';

                            break;
                        } else {
                            unset($_POST['HTTP_REFERER']);

                            return true; // allowed, so end
                        }
                    }
                }
            } elseif ($row['blockedlist_type'] == 'agent' && $row['blockedlist_value'] != '' && ee()->input->user_agent() != '' && $this->allowed != 'y') {
                $blocked_values = explode('|', $row['blockedlist_value']);

                if (! is_array($blocked_values) or count($blocked_values) == 0) {
                    continue;
                }

                foreach ($blocked_values as $bad_agent) {
                    if ($bad_agent != '' && stristr(ee()->input->user_agent(), $bad_agent) !== false) {
                        $bad = 'y';

                        if (is_array($allowedlist_ip) && count($allowedlist_ip) > 0) {
                            foreach ($allowedlist_ip as $pure) {
                                if ($pure != '' && strpos(ee()->input->user_agent(), $pure) !== false) {
                                    $bad = 'n';
                                    $this->allowed = 'y';

                                    break;
                                }
                            }
                        }

                        if (is_array($allowedlist_agent) && count($allowedlist_agent) > 0) {
                            foreach ($allowedlist_agent as $pure) {
                                if ($pure != '' && strpos(ee()->input->agent, $pure) !== false) {
                                    $bad = 'n';
                                    $this->allowed = 'y';

                                    break;
                                }
                            }
                        }

                        if ($bad == 'y') {
                            $this->blocked = 'y';
                        } else {
                            unset($_POST['HTTP_REFERER']);

                            return true; // allowed, so end
                        }
                    }
                }
            }
        }

        unset($_POST['HTTP_REFERER']);

        return true;
    }
}

// EOF
