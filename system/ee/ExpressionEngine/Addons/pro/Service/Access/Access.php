<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Access;

/**
 * Frontend edit service
 */
class Access
{
    protected static $hasValidLicense = null;
    protected static $requiresLicense = null;

    public function __construct()
    {
    }

    /**
     * Check if user has edit permission for given channel/entry
     */
    public function hasFrontEditPermission($channel_id, $entry_id)
    {
        $has_permission = ee('Permission')->can('edit_other_entries_channel_id_' . $channel_id);
        if (!$has_permission) {
            $author_id = ee()->db->select('author_id')
                ->where('entry_id', $entry_id)
                ->where('channel_id', $channel_id)
                ->where('author_id', ee()->session->userdata('member_id'))
                ->get('channel_titles');
            if ($author_id->num_rows() > 0) {
                $has_permission = ee('Permission')->can('edit_self_entries_channel_id_' . $channel_id);
            }
        }

        return $has_permission;
    }

    /**
     * Check if user has edit permission for any of provided channel/entry
     * if caching is enabled, we'll do a lot of guessing here
     */
    public function hasAnyFrontEditPermission()
    {
        if (ee('Permission')->isSuperAdmin()) {
            return true;
        }

        //the cache below is not reliable since it only had data from the last query
        //so we return true if they have at least single channel permission
        if (ee('Permission')->hasAny('can_edit_other_entries', 'can_edit_self_entries')) {
            return true;
        }

        ee()->session->cache['disable_frontedit'] = true;

        return false;

        $channel_ids = isset(ee()->session->cache['channel']['channel_ids']) ? ee()->session->cache['channel']['channel_ids'] : [];
        $entry_ids = isset(ee()->session->cache['channel']['entry_ids']) ? ee()->session->cache['channel']['entry_ids'] : [];

        // if we don't have $channel_ids this most likely means the request is cached
        // let's grab ALL channels then since we're not particularly sure which one to check against
        if (empty($channel_ids) && empty($entry_ids)) {
            $channel_ids = ee('Model')->get('Channel')->fields('channel_id')->all()->pluck('channel_id');
        }

        $has_permission = false;
        if (!empty($channel_ids)) {
            foreach ($channel_ids as $channel_id) {
                $has_permission = ee('Permission')->can('edit_other_entries_channel_id_' . $channel_id);
                if ($has_permission) {
                    return $has_permission;
                }
            }
        }

        if (!empty($entry_ids)) {
            $check_q = ee()->db->select('channel_id')
                ->where_in('entry_id', $entry_ids)
                ->where('author_id', ee()->session->userdata('member_id'))
                ->get('channel_titles');
            if ($check_q->num_rows() > 0) {
                foreach ($check_q->result_array() as $row) {
                    $has_permission = ee('Permission')->can('edit_self_entries_channel_id_' . $row['channel_id']);
                    if ($has_permission) {
                        return $has_permission;
                    }
                }
            }
        }

        return $has_permission;
    }

    /**
     * Checks whether member can use the Dock
     *
     * @return boolean Dock access allowed
     */
    public function hasDockPermission()
    {
        if (ee()->config->item('enable_dock') !== false && ee()->config->item('enable_dock') != 'y') {
            return false;
        }

        if (ee('Permission')->canUsePro()) {
            return true;
        }

        return false;
    }

    public function hasRequiredLicense()
    {
        if ($this->requiresValidLicense()) {
            return $this->hasValidLicense(true);
        }

        return true;
    }

    /**
     * Checks whether license/subscription is valid and active
     *
     * @param bool $showAlert whether to show alert in CP if license is not valid
     *
     * @return boolean the license is valid and active
     */
    public function hasValidLicense($showAlert = false)
    {
        if (is_null(static::$hasValidLicense)) {
            $addon = ee('Addon')->get('pro');
            $licenseResponse = $addon->checkCachedLicenseResponse();

            switch ($licenseResponse) {
                case 'valid':
                case 'update_available':
                    static::$hasValidLicense = true;

                    break;

                case 'trial':
                    // In the case of a trial, the license will be marked as valid, since we want users
                    // to still have access to all features, but we're still going to throw a banner up
                    static::$hasValidLicense = true;
                    $this->logLicenseError('pro_license_error_trial', $showAlert);

                    break;

                case 'na':
                    static::$hasValidLicense = false;
                    $this->logLicenseError('pro_license_error_na', $showAlert);

                    break;

                case 'invalid':
                    static::$hasValidLicense = false;
                    $this->logLicenseError('pro_license_error_invalid', $showAlert);

                    break;

                case 'expired':
                    static::$hasValidLicense = false;
                    $this->logLicenseError('pro_license_error_expired', $showAlert);

                    break;

                default:
                    static::$hasValidLicense = false;

                    break;
            }
        }

        return static::$hasValidLicense;
    }

    public function requiresValidLicense()
    {
        // If there are multiple members, we require pro
        if (is_null(static::$requiresLicense)) {
            if ($countMemberWithCPAccess = ee()->cache->get('cp_member_count')) {
                static::$requiresLicense = ($countMemberWithCPAccess > 1);

                return static::$requiresLicense;
            }

            $cpRoleIds = ee('db')->distinct()->select('role_id')->from('permissions')->where('permission', 'can_access_cp')->get();
            $cpRoles = [1];
            foreach ($cpRoleIds->result_array() as $row) {
                $cpRoles[] = $row['role_id'];
            }
            $cpRolesList = implode(', ', array_unique($cpRoles));
            $countMemberWithCPAccessQuery = "SELECT COUNT(DISTINCT(exp_members.member_id)) AS count
                FROM exp_members
                LEFT JOIN exp_members_roles ON (exp_members.member_id = exp_members_roles.member_id)
                LEFT JOIN exp_members_role_groups ON (exp_members.member_id = exp_members_role_groups.member_id)
                LEFT JOIN exp_roles_role_groups ON (exp_members_role_groups.group_id = exp_roles_role_groups.group_id)
                WHERE exp_members.role_id IN ({$cpRolesList})
                OR exp_members_roles.role_id IN ({$cpRolesList})
                OR exp_roles_role_groups.role_id IN ({$cpRolesList})";
            $countMemberWithCPAccess = ee()->db->query($countMemberWithCPAccessQuery)->row('count');
            ee()->cache->save('cp_member_count', $countMemberWithCPAccess, 60);

            static::$requiresLicense = ($countMemberWithCPAccess > 1);
        }

        return static::$requiresLicense;
    }

    /**
     * Checks whether front-end editing links should be injected
     *
     * @return boolean
     */
    public function shouldInjectLinks()
    {
        if ($this->hasDockPermission() && $this->hasAnyFrontEditPermission() && ee()->input->cookie('frontedit') != 'off' && (!$this->requiresValidLicense() || $this->hasValidLicense())) {
            return true;
        }

        return false;
    }

    /**
     * Log license error to developer log and display alert in CP
     *
     * @param [type] $message
     * @return void
     */
    private function logLicenseError($message, $showAlert = false)
    {
        ee()->load->library('logger');
        ee()->lang->load('addons');
        ee()->lang->load('pro');
        $isTrial = ($message === 'pro_license_error_trial');

        if (! $this->hasValidLicense()) {
            $message = sprintf(
                lang('pro_license_check_instructions'),
                lang($message),
                ee('CP/URL')->make('settings/general', [], ee()->config->item('cp_url'))->compile() . '#fieldset-site_license_key'
            );
        } else {
            $message = sprintf(
                lang('pro_license_check_trial_instructions'),
                lang($message),
                ee('CP/URL')->make('settings/general', [], ee()->config->item('cp_url'))->compile() . '#fieldset-site_license_key'
            );
        }

        // If the user is running pro as a trial then they should only see the error once
        if ($isTrial && ee('Session')->proBannerSeen()) {
            $showAlert = false;
        }

        ee()->logger->developer($message, true, 60 * 60 * 24 * 7);
        if (REQ == 'CP' && $showAlert) {
            // The user has seen the banner, so we're marking it in the session
            ee('Session')->setProBannerSeen();

            ee('CP/Alert')->makeBanner('pro-license-error')
                ->asIssue()
                ->canClose()
                ->withTitle(lang('pro_license_error'))
                ->addToBody($message)
                ->now();
        }
    }
}

// EOF
