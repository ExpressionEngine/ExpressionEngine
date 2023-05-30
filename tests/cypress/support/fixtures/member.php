<?php

require('bootstrap.php');

@set_time_limit(300);

$command = array_shift($argv);

$longopts = array(
	"group-id:",
	"username:",
	"screen-name:",
	"email:",
	"number:",
	"help",
    "batch",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} [options]
	--help                   This help message
	--group-id      <number> The group_id to use
	--username      <string> The username to use
	--screen-name   <string> The screen_name to use
	--email         <string> The email to use
	--number        <string> Number of members to create
    --batch                  Use batch insert instead of model save
EOF;
	exit();
}

$number = isset($options['number']) && is_numeric($options['number']) ? (int) $options['number'] : 1;
$group_id = isset($options['group-id']) && is_numeric($options['group-id']) ? (int) $options['group-id'] : 5;
$username = isset($options['username']) ? $options['username'] : 'johndoe';
$screen_name = isset($options['screen-name']) ? $options['screen-name'] : 'John Doe';
$email = isset($options['email']) ? $options['email'] : 'john@nomail.com';
$batch = isset($options['batch']) ? true : false;
//get the total of existing members
$existing = ee('Model')->get('Member')->count();
$start = $existing + 1;
$total = $start + $number;
$rows = [];

echo "$start to $total";

for ($n = $start; $n < $total; $n++) {
	$member = ee('Model')->make('Member');
	$member->role_id = $group_id;
	$member->username = $username . $n;
	$member->screen_name = $screen_name . $n;
	$member->password = sha1("password");
	$member->salt = sha1("password");
	$member->language = 'english';
	$member->timezone = 'America/New_York';
	$member->email = $n . $email;

    if($batch) {
        $member->onBeforeInsert();
        $member = array_merge($member->toArray(), [
            'accept_messages' => 'y',
            'accept_admin_email' => 'y',
            'accept_user_email' => 'y',
            'dismissed_banner' => 'n',
            'display_signatures' => 'y',
            'enable_mfa' => 'n',
            'in_authorlist' => 'n',
            'ip_address' => 0,
            'join_date' => 0,
            'last_activity' => 0,
            'last_bulletin_date' => 0,
            'last_comment_date' => 0,
            'last_email_date' => 0,
            'last_entry_date' => 0,
            'last_forum_post_date' => 0,
            'last_view_bulletins' => 0,
            'last_visit' => 0,
            'notepad_size' => '18',
            'notify_by_default' => 'y',
            'notify_of_pm' => 'y',
            'parse_smileys' => 'y',
            'pending_role_id' => 0,
            'pmember_id' => 0,
            'private_messages' => 0,
            'show_sidebar' => 'n',
            'smart_notifications' => 'y',
            'template_size' => '28',
            'total_comments' => 0,
            'total_entries' => 0,
            'total_forum_posts' => 0,
            'total_forum_topics' => 0,
        ]);
        unset($member['group_id']);
        $rows[] = $member;
    }else{
	    $member->save();
	    unset($member);
    }
	//echo $member->getId() . "\n";
}

if($batch && !empty($rows)) {
    ee()->db->insert_batch('members', $rows);
    ee()->db->insert_batch('member_data', array_map(function($id) {
        return ['member_id' => $id];
    }, range($start, $total-1)));
}
