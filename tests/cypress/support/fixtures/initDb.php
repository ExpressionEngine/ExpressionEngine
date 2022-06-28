<?php

define('INSTALL_MODE', true);
define('REQ', 'CLI');

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
    "help",
    "version:",
    "username:",
    "password:",
    "email:",
    "url:",
    "db_host:",
    "db_user:",
    'db_password:',
    "db_database:"
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help'])) {
    print <<<EOF
Install initial database structure
Usage: {$command} [options]
    --help                   This help message
    --version                EE Version
    --username               Admin username
    --password               Admin password
    --email                  Admin email address
    --url                    Site base URL
    --db_host                DB hostname
    --db_user                DB username
    --db_password            DB password
    --db_database            DB name
EOF;
    exit();
}

// Load the DB schema
require APPPATH . 'schema/mysqli_schema.php';
$schema = new EE_Schema();
$schema->version = isset($options['version']) ? $options['version'] : '6.0.0';

// Assign the userdata array to the schema class
$schema->userdata = array(
    'app_version' => '',
    'ext' => '.php',
    'ip' => '',
    'database' => 'mysql',
    'db_hostname' => isset($options['db_host']) ? $options['db_host'] : 'localhost',
    'db_username' => isset($options['db_user']) ? $options['db_user'] : '',
    'db_password' => isset($options['db_password']) ? $options['db_password'] : '',
    'db_name' => isset($options['db_database']) ? $options['db_database'] : '',
    'db_prefix' => 'exp',
    'db_char_set' => 'utf8',
    'db_collat' => 'utf8_unicode_ci',
    'site_label' => 'ExpressionEngine ' . $schema->version,
    'site_name' => 'default_site',
    'site_url' => isset($options['url']) ? $options['url'] : 'http://localhost:8888/',
    'site_index' => 'index.php',
    'cp_url' => '',
    'username' => '',
    'password' => '',
    'password_confirm' => '',
    'screen_name' => '',
    'email_address' => '',
    'webmaster_email' => '',
    'deft_lang' => 'english',
    'theme' => 'default',
    'default_site_timezone' => '',
    'redirect_method' => 'redirect',
    'upload_folder' => 'uploads/',
    'cp_images' => 'cp_images/',
    'avatar_path' => '../images/avatars/',
    'avatar_url' => 'images/avatars/',
    'photo_path' => '../images/member_photos/',
    'photo_url' => 'images/member_photos/',
    'signature_img_path' => '../images/signature_attachments/',
    'signature_img_url' => 'images/signature_attachments/',
    'pm_path' => '../images/pm_attachments',
    'captcha_path' => '../images/captchas/',
    'theme_folder_path' => '../themes/',
    'modules' => array(),
    'install_default_theme' => 'n',
    'utf8mb4_supported' => null,
    'share_analytics' => 'n'
);
$schema->userdata['app_version'] = $schema->version;
$schema->userdata['screen_name'] = $schema->userdata['username'] = isset($options['username']) ? $options['username'] : 'admin';
$schema->userdata['password'] = isset($options['password']) ? $options['password'] : 'password';
$schema->userdata['email'] = isset($options['email']) ? $options['email'] : 'cypress@expressionengine.com';
// Time
$time = time();
$schema->now = gmmktime(gmdate("H", $time), gmdate("i", $time), gmdate("s", $time), gmdate("m", $time), gmdate("d", $time), gmdate("Y", $time));
$schema->year = gmdate('Y', $schema->now);
$schema->month = gmdate('m', $schema->now);
$schema->day = gmdate('d', $schema->now);

// Encrypt the password and unique ID
ee()->load->library('auth');
$hashed_password = ee()->auth->hash_password($schema->userdata['password']);
$schema->userdata['password'] = $hashed_password['password'];
$schema->userdata['salt'] = $hashed_password['salt'];
$schema->userdata['unique_id'] = ee('Encrypt')->generateKey();

// --------------------------------------------------------------------

$db = array(
    'port' => '3306',
    'hostname' => $schema->userdata['db_hostname'],
    'username' => $schema->userdata['db_username'],
    'password' => $schema->userdata['db_password'],
    'database' => $schema->userdata['db_name'],
    'dbdriver' => 'mysqli',
    'dbprefix' => 'exp_',
    'swap_pre' => 'exp_',
    'db_debug' => true, // We show our own errors
    'cache_on' => false,
    'autoinit' => false, // We'll initialize the DB manually
    'char_set' => $schema->userdata['db_char_set'],
    'dbcollat' => $schema->userdata['db_collat']
);

db_connect($db);

// Load the email template
require_once SYSPATH . 'ee/language/' . $schema->userdata['deft_lang'] . '/email_data.php';

// Install Database Tables!
$install = $schema->install_tables_and_data();

write_config_data($schema);

install_modules();

exit(!(int) $install);

/**
 * Connect to the database
 *
 * @param array $db Associative array containing db connection data
 * @return boolean  true if successful, false if not
 */
function db_connect($db)
{
    if (count($db) == 0) {
        return false;
    }

    $db_object = ee()->load->database($db, true, true);

    // Force caching off
    $db_object->save_queries = true;

    // Ask for exceptions so we can show proper errors in the form
    $db_object->db_exception = true;

    try {
        $db_object->initialize();
    } catch (Exception $e) {
        // If they're using localhost, fall back to 127.0.0.1
        if ($db['hostname'] == 'localhost') {
            ee('Database')->closeConnection();
            $schema->userdata['db_hostname'] = '127.0.0.1';
            $db['hostname'] = '127.0.0.1';

            return db_connect($db);
        }

        return ($e->getCode()) ?: false;
    }

    ee()->remove('db');
    ee()->set('db', $db_object);

    return true;
}

/**
 * Write the config file
 * @return boolean  true if successful, false if not
 */
function write_config_data($schema)
{
    $captcha_url = '{base_url}images/captchas/';

    $schema->base_path = preg_replace('/\b' . preg_quote(SYSDIR) . '(?!.*' . preg_quote(SYSDIR) . ')\b/', '', SYSPATH);
    $schema->theme_path = $schema->base_path . 'themes/';

    $schema->base_path = str_replace('//', '/', $schema->base_path);
    $schema->root_theme_path = $schema->theme_path;
    $schema->theme_path .= 'ee/site/';
    $schema->theme_path = str_replace('//', '/', $schema->theme_path);
    $schema->root_theme_path = str_replace('//', '/', $schema->root_theme_path);

    foreach (array('avatar_path', 'photo_path', 'signature_img_path', 'pm_path', 'captcha_path', 'theme_folder_path') as $path) {
        $prefix = ($path != 'theme_folder_path') ? $schema->root_theme_path : '';
        $schema->userdata[$path] = rtrim(realpath($prefix . $schema->userdata[$path]), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $schema->userdata[$path] = str_replace(str_replace('/', DIRECTORY_SEPARATOR, $schema->base_path), '{base_path}', $schema->userdata[$path]);
    }

    $config = array(
        'db_port' => '3306',
        'db_hostname' => $schema->userdata['db_hostname'],
        'db_username' => $schema->userdata['db_username'],
        'db_password' => $schema->userdata['db_password'],
        'db_database' => $schema->userdata['db_name'],
        'db_dbprefix' => 'exp_',
        'db_char_set' => $schema->userdata['db_char_set'],
        'db_collat' => $schema->userdata['db_collat'],
        'app_version' => $schema->userdata['app_version'],
        'debug' => '1',
        'site_index' => $schema->userdata['site_index'],
        'site_label' => $schema->userdata['site_label'],
        'base_path' => $schema->base_path,
        'base_url' => $schema->userdata['site_url'],
        'cp_url' => str_replace($schema->userdata['site_url'], '{base_url}', $schema->userdata['cp_url']),
        'site_url' => '{base_url}',
        'theme_folder_url' => '{base_url}themes/',
        'webmaster_email' => $schema->userdata['email_address'],
        'webmaster_name' => '',
        'channel_nomenclature' => 'channel',
        'max_caches' => '150',
        'cache_driver' => 'file',
        'captcha_url' => $captcha_url,
        'captcha_path' => $schema->userdata['captcha_path'],
        'captcha_font' => 'y',
        'captcha_rand' => 'y',
        'captcha_require_members' => 'n',
        'require_captcha' => 'n',
        'enable_sql_caching' => 'n',
        'force_query_string' => 'n',
        'show_profiler' => 'n',
        'include_seconds' => 'n',
        'cookie_domain' => '',
        'cookie_path' => '/',
        'cookie_prefix' => '',
        'website_session_type' => 'c',
        'cp_session_type' => 'c',
        'cookie_httponly' => 'y',
        'allow_username_change' => 'y',
        'allow_multi_logins' => 'y',
        'password_lockout' => 'y',
        'password_lockout_interval' => '1',
        'require_ip_for_login' => 'y',
        'require_ip_for_posting' => 'y',
        'require_secure_passwords' => 'n',
        'allow_dictionary_pw' => 'y',
        'name_of_dictionary_file' => '',
        'xss_clean_uploads' => 'y',
        'redirect_method' => $schema->userdata['redirect_method'],
        'deft_lang' => $schema->userdata['deft_lang'],
        'xml_lang' => 'en',
        'send_headers' => 'y',
        'gzip_output' => 'n',
        'is_system_on' => 'y',
        'allow_extensions' => 'y',
        'date_format' => '%n/%j/%Y',
        'time_format' => '12',
        'include_seconds' => 'n',
        'server_offset' => '',
        'default_site_timezone' => date_default_timezone_get(),
        'mail_protocol' => 'mail',
        'email_newline' => '\n', // single-quoted for portability
        'smtp_server' => '',
        'smtp_username' => '',
        'smtp_password' => '',
        'email_smtp_crypto' => 'ssl',
        'email_debug' => 'n',
        'email_charset' => 'utf-8',
        'email_batchmode' => 'n',
        'email_batch_size' => '',
        'mail_format' => 'plain',
        'word_wrap' => 'y',
        'email_console_timelock' => '5',
        'log_email_console_msgs' => 'y',
        'log_search_terms' => 'y',
        'un_min_len' => '4',
        'pw_min_len' => '5',
        'allow_member_registration' => 'n',
        'allow_member_localization' => 'y',
        'req_mbr_activation' => 'email',
        'new_member_notification' => 'n',
        'mbr_notification_emails' => '',
        'require_terms_of_service' => 'y',
        'default_primary_role' => '5',
        'profile_trigger' => 'member' . $schema->now,
        'member_theme' => 'default',
        'avatar_url' => '{base_url}' . $schema->userdata['avatar_url'],
        'avatar_path' => $schema->userdata['avatar_path'],
        'avatar_max_width' => '100',
        'avatar_max_height' => '100',
        'avatar_max_kb' => '50',
        'enable_photos' => 'n',
        'photo_url' => '{base_url}' . $schema->userdata['photo_url'],
        'photo_path' => $schema->userdata['photo_path'],
        'photo_max_width' => '100',
        'photo_max_height' => '100',
        'photo_max_kb' => '50',
        'allow_signatures' => 'y',
        'sig_maxlength' => '500',
        'sig_allow_img_hotlink' => 'n',
        'sig_allow_img_upload' => 'n',
        'sig_img_url' => '{base_url}' . $schema->userdata['signature_img_url'],
        'sig_img_path' => $schema->userdata['signature_img_path'],
        'sig_img_max_width' => '480',
        'sig_img_max_height' => '80',
        'sig_img_max_kb' => '30',
        'prv_msg_enabled' => 'y',
        'prv_msg_allow_attachments' => 'y',
        'prv_msg_upload_path' => $schema->userdata['pm_path'],
        'prv_msg_max_attachments' => '3',
        'prv_msg_attach_maxsize' => '250',
        'prv_msg_attach_total' => '100',
        'prv_msg_html_format' => 'safe',
        'prv_msg_auto_links' => 'y',
        'prv_msg_max_chars' => '6000',
        'enable_template_routes' => 'y',
        'strict_urls' => 'y',
        'site_404' => '',
        'save_tmpl_revisions' => 'n',
        'max_tmpl_revisions' => '5',
        'save_tmpl_files' => 'y',
        'deny_duplicate_data' => 'y',
        'redirect_submitted_links' => 'n',
        'enable_censoring' => 'n',
        'censored_words' => '',
        'censor_replacement' => '',
        'banned_ips' => '',
        'banned_emails' => '',
        'banned_usernames' => '',
        'banned_screen_names' => '',
        'ban_action' => 'restrict',
        'ban_message' => 'This site is currently unavailable',
        'ban_destination' => 'http://www.yahoo.com/',
        'enable_emoticons' => 'y',
        'emoticon_url' => '{base_url}' . 'images/smileys/',
        'recount_batch_total' => '1000',
        'image_resize_protocol' => 'gd2',
        'image_library_path' => '',
        'thumbnail_prefix' => 'thumb',
        'word_separator' => 'dash',
        'use_category_name' => 'n',
        'reserved_category_word' => 'category',
        'auto_convert_high_ascii' => 'n',
        'new_posts_clear_caches' => 'y',
        'auto_assign_cat_parents' => 'y',
        'new_version_check' => 'y',
        'enable_throttling' => 'n',
        'banish_masked_ips' => 'y',
        'max_page_loads' => '10',
        'time_interval' => '8',
        'lockout_time' => '30',
        'banishment_type' => 'message',
        'banishment_url' => '',
        'banishment_message' => 'You have exceeded the allowed page load frequency.',
        'enable_search_log' => 'y',
        'max_logged_searches' => '500',
        'memberlist_order_by' => "member_id",
        'memberlist_sort_order' => "desc",
        'memberlist_row_limit' => "20",
        'is_site_on' => 'y',
        'show_ee_news' => 'y',
        'theme_folder_path' => $schema->userdata['theme_folder_path'],
    );

    $inserts = [];
    $install_wide = ee()->config->divination('install');
    foreach (ee()->config->divineAll() as $key) {
        if (array_key_exists($key, $config)) {
            $inserts[] = [
                'site_id' => (in_array($key, $install_wide)) ? 0 : 1,
                'key' => $key,
                'value' => $config[$key]
            ];
            unset($config[$key]);
        }
    }
    ee()->db->insert_batch('config', $inserts);
}

function install_modules()
{
    $required_modules = [
        'pro',
        'channel',
        'comment',
        'consent',
        'member',
        'stats',
        'rte',
        'file',
        'filepicker',
        'relationship',
        'search',
    ];

    ee()->load->library('addons');
    ee()->addons->install_modules($required_modules);

    $consent = ee('Addon')->get('consent');
    $consent->installConsentRequests();

    return true;
}
