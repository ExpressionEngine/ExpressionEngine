<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\MemberManager\Columns;

use ExpressionEngine\Library\CP\EntryManager;
use ExpressionEngine\Library\CP\Table;

/**
 * Username Column
 */
class Username extends EntryManager\Columns\Title
{
    public function getTableColumnLabel()
    {
        return 'column_username';
    }

    public function renderTableCell($data, $field_id, $member, $viewtype = 'list', $pickerMode = false, $addQueryString = [], $isFullyEditable = true)
    {
        if (ee('Permission')->isSuperAdmin() || $member->member_id == ee()->session->userdata('member_id')) {
            $canEdit = true;
        } else {
            $canEdit = (bool) ($member->PrimaryRole->is_locked != 'y' && ee('Permission')->can('edit_members'));
        }

        if ($canEdit) {
            $editLink = ee('CP/URL')->make('members/profile/', array('id' => $member->member_id));
            $username_display = "<a href=\"" . $editLink . "\">" . $member->username . "</a>";
        } else {
            $username_display = $member->username;
        }

        if (!empty($member->screen_name)) {
            $username_display .= '<br><span class="meta-info">' . $member->screen_name . '</span>';
        }

        $avatar_url = ($member->avatar_filename) ? ee()->config->slash_item('avatar_url') . $member->avatar_filename : (URL_THEMES . 'asset/img/default-avatar.png');
        $avatar = "<img src=\"$avatar_url\" alt=\"" . $member->username . "\" class=\"avatar-icon add-mrg-right\">";

        $out = "<div class=\"d-flex align-items-center\">";
        if ($canEdit) {
            $out .= "<a href=\"" . $editLink . "\">" . $avatar . "</a>";
        } else {
            $out .= $avatar;
        }
        $out .= "<div>$username_display</div></div>";

        return $out;
    }
}
