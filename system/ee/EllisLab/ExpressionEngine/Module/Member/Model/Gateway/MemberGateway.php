<?php

namespace EllisLab\ExpressionEngine\Module\Member\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

/**
 * Member table
 *
 * Contains the member info
 */
class MemberGateway extends Gateway {

	protected static $_table_name = 'members';
	protected static $_primary_key = 'member_id';
	protected static $_related_gateways = array(
		'group_id' => array(
			'gateway' => 'MemberGroupGateway',
			'key' => 'group_id'
		),
		'member_id' => array(
			'gateway' => 'ChannelTitleGateway',
			'key' => 'author_id'
		)

	);

	// Properties
	public $member_id;
	public $group_id;
	public $username;
	public $screen_name;
	public $password;
	public $salt;
	public $unique_id;
	public $crypt_key;
	public $authcode;
	public $email;
	public $url;
	public $location;
	public $occupation;
	public $interests;
	public $bday_d;
	public $bday_m;
	public $bday_y;
	public $aol_im;
	public $yahoo_im;
	public $msn_im;
	public $icq;
	public $bio;
	public $signature;
	public $avatar_filename;
	public $avatar_width;
	public $avatar_height;
	public $photo_filename;
	public $photo_width;
	public $photo_height;
	public $sig_img_filename;
	public $sig_img_width;
	public $sig_img_height;
	public $ignore_list;
	public $private_messages;
	public $accept_messages;
	public $last_view_bulletins;
	public $last_bulletin_date;
	public $ip_address;
	public $join_date;
	public $last_visit;
	public $last_activity;
	public $total_entries;
	public $total_comments;
	public $total_forum_topics;
	public $total_forum_posts;
	public $last_entry_date;
	public $last_comment_date;
	public $last_forum_post_date;
	public $last_email_date;
	public $in_authorlist;
	public $accept_admin_email;
	public $accept_user_email;
	public $notify_by_default;
	public $notify_of_pm;
	public $display_avatars;
	public $display_signatures;
	public $parse_smileys;
	public $smart_notifications;
	public $language;
	public $timezone;
	public $time_format;
	public $date_format;
	public $include_seconds;
	public $cp_theme;
	public $profile_theme;
	public $forum_theme;
	public $tracker;
	public $template_size;
	public $notepad;
	public $notepad_size;
	public $bookmarklets;
	public $quick_links;
	public $quick_tabs;
	public $show_sidebar;
	public $pmember_id;
	public $rte_enabled;
	public $rte_toolset_id;
}
