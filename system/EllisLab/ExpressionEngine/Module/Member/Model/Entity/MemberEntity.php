<?php
namespace EllisLab\ExpressionEngine\Module\Member\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

/**
 * Member table
 *
 * Contains the member info
 */
class MemberEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'members',
		'primary_key' => 'member_id',
		'related_entities' => array(
			'group_id' => array(
				'entity' => 'MemberGroupEntity',
				'key' => 'group_id'
			)
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
	public $cp_theme;
	public $profile_theme;
	public $forum_theme;
	public $tracker;
	public $template_size;
	public $notepad;
	public $notepad_size;
	public $quick_links;
	public $quick_tabs;
	public $show_sidebar;
	public $pmember_id;
}
