<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Library;
use ExpressionEngine\Library\Filesystem;
use ExpressionEngine\Library\Curl;
use ExpressionEngine\Library\Emoji;
use ExpressionEngine\Library\Resource;
use ExpressionEngine\Library\String\Str;
use ExpressionEngine\Service\Addon;
use ExpressionEngine\Service\Alert;
use ExpressionEngine\Service\Category;
use ExpressionEngine\Service\Channel;
use ExpressionEngine\Service\ChannelSet;
use ExpressionEngine\Service\ConditionalFields;
use ExpressionEngine\Service\Config;
use ExpressionEngine\Service\Consent;
use ExpressionEngine\Service\Cookie;
use ExpressionEngine\Service\CustomMenu;
use ExpressionEngine\Service\Database;
use ExpressionEngine\Service\Encrypt;
use ExpressionEngine\Service\EntryListing;
use ExpressionEngine\Service\Event;
use ExpressionEngine\Service\File;
use ExpressionEngine\Service\Filter;
use ExpressionEngine\Service\Formatter;
use ExpressionEngine\Service\IpAddress;
use ExpressionEngine\Service\JumpMenu;
use ExpressionEngine\Service\License;
use ExpressionEngine\Service\LivePreview;
use ExpressionEngine\Service\Logger;
use ExpressionEngine\Service\Member;
use ExpressionEngine\Service\Memory;
use ExpressionEngine\Service\Migration;
use ExpressionEngine\Service\Modal;
use ExpressionEngine\Service\Model;
use ExpressionEngine\Service\Permission;
use ExpressionEngine\Service\Profiler;
use ExpressionEngine\Service\Session;
use ExpressionEngine\Service\Sidebar;
use ExpressionEngine\Service\Theme;
use ExpressionEngine\Service\Thumbnail;
use ExpressionEngine\Service\URL;
use ExpressionEngine\Service\Updater;
use ExpressionEngine\Service\Validation;
use ExpressionEngine\Service\Template;
use ExpressionEngine\Service\View;
use ExpressionEngine\Addons\Spam\Service\Spam;
use ExpressionEngine\Addons\FilePicker\Service\FilePicker;
use ExpressionEngine\Service\Generator\ActionGenerator;
use ExpressionEngine\Service\Generator\AddonGenerator;
use ExpressionEngine\Service\Generator\CommandGenerator;
use ExpressionEngine\Service\Generator\CpRouteGenerator;
use ExpressionEngine\Service\Generator\ExtensionHookGenerator;
use ExpressionEngine\Service\Generator\ExtensionGenerator;
use ExpressionEngine\Service\Generator\FieldtypeGenerator;
use ExpressionEngine\Service\Generator\JumpsGenerator;
use ExpressionEngine\Service\Generator\ModelGenerator;
use ExpressionEngine\Service\Generator\ProletGenerator;
use ExpressionEngine\Service\Generator\SidebarGenerator;
use ExpressionEngine\Service\Generator\TemplateTagGenerator;
use ExpressionEngine\Service\Generator\WidgetGenerator;
use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Model\Channel\Reindex;

// TODO should put the version in here at some point ...
$setup = [

    'author' => 'ExpressionEngine',
    'name' => 'ExpressionEngine',
    'description' => "The world's most flexible content management system.",

    'namespace' => 'ExpressionEngine',

    'services' => array(

        'Category' => function ($ee) {
            return new Category\Factory();
        },

        'CP/CustomMenu' => function ($ee) {
            return new CustomMenu\Menu();
        },

        'CP/EntryListing' => function ($ee, $search_value, $search_in = null, $include_author_filter = false, $view_id = null, $extra_filters = []) {
            return new EntryListing\EntryListing(
                ee()->config->item('site_id'),
                (ee('Permission')->isSuperAdmin()),
                array_keys(ee()->session->userdata['assigned_channels']),
                ee()->localize->now,
                $search_value,
                $search_in,
                $include_author_filter,
                $view_id,
                $extra_filters
            );
        },

        'CP/Filter' => function ($ee) {
            $filters = new Filter\FilterFactory($ee->make('View', '_shared/filters'));
            $filters->setDIContainer($ee);

            return $filters;
        },

        'CP/GridInput' => function ($ee, $config = array()) {
            ee()->lang->load('content');
            $grid = new Library\CP\GridInput(
                $config,
                ee()->cp,
                ee()->config,
                ee()->javascript
            );

            return $grid;
        },

        'CP/Form' => function ($ee) {
            return new Library\CP\Form();
        },

        'CP/JumpMenu' => function ($ee) {
            return new JumpMenu\JumpMenu();
        },

        'CP/MiniGridInput' => function ($ee, $config = array()) {
            ee()->lang->load('content');
            $grid = new Library\CP\MiniGridInput(
                $config,
                ee()->cp,
                ee()->config,
                ee()->javascript
            );

            return $grid;
        },

        'CP/Table' => function ($ee, $config = array()) {
            $table = Library\CP\Table::fromGlobals($config);
            $table->setLocalize(ee()->localize);

            return $table;
        },

        'CP/URL' => function ($ee, $path = null) {
            if (! isset(ee()->session)) {
                ee()->load->library('session');
            }

            $cp_url = ee()->config->item('cp_url');
            $site_index = ee()->functions->fetch_site_index(0, 0);
            $uri_string = ee()->uri->uri_string();
            $session_id = ee()->session->session_id();
            $default_cp_url = SELF;

            $factory = new URL\URLFactory($cp_url, $site_index, $uri_string, $session_id, $default_cp_url, $ee->make('Encrypt'));

            return (is_null($path)) ? $factory : $factory->make($path);
        },

        'CP/Pagination' => function ($ee, $total_count) {
            $view = $ee->make('View')->make('_shared/pagination');

            return new Library\CP\Pagination($total_count, $view);
        },

        'CSV' => function ($ee) {
            return new Library\Data\CSV();
        },

        'db' => function ($ee) {
            return $ee->make('Database')->newQuery();
        },

        'Database/Backup' => function ($ee, $file_path) {
            $filesystem = $ee->make('Filesystem');
            $backup_query = $ee->make('Database/Backup/Query');
            $row_limit = ee()->config->item('db_backup_row_limit');

            return new Database\Backup\Backup($filesystem, $backup_query, $file_path, $row_limit);
        },

        'Database/Backup/Query' => function ($ee) {
            return new Database\Backup\Query($ee->make('db'));
        },

        'Database/Restore' => function ($ee) {
            $filesystem = $ee->make('Filesystem');

            return new Database\Backup\Restore($ee->make('db'), $filesystem);
        },

        'Event' => function ($ee) {
            return new Event\Emitter();
        },

        'Format' => function ($ee) {
            static $format_opts;
            if ($format_opts === null) {
                $format_opts += (extension_loaded('intl')) ? 0b00000001 : 0;
            }

            $config_items = [
                'censor_replacement' => ee()->config->item('censor_replacement'),
                'censored_words' => ee()->config->item('censored_words'),
                'foreign_chars' => ee()->config->loadFile('foreign_chars'),
                'stopwords' => ee()->config->loadFile('stopwords'),
                'word_separator' => ee()->config->item('word_separator'),
            ];

            return new Formatter\FormatterFactory(ee()->lang, ee()->session, $config_items, $format_opts);
        },

        'Curl' => function ($ee) {
            return new Curl\RequestFactory();
        },

        'View' => function ($ee) {
            return new View\ViewFactory($ee);
        },

        'Memory' => function ($ee) {
            return new Memory\Memory();
        },

        'Model' => function ($ee) {
            $facade = new Model\Facade($ee->make('Model/Datastore'));
            $facade->setValidationFactory($ee->make('Validation'));

            return $facade;
        },

        'Migration' => function ($ee, $migration = null) {
            return new Migration\Factory($ee->make('db'), $ee->make('Filesystem'), $migration);
        },

        'Spam' => function ($ee) {
            return new Spam();
        },

        'Theme' => function ($ee) {
            return new Theme\Theme(PATH_THEME_TEMPLATES, URL_THEMES, PATH_THIRD_THEME_TEMPLATES, URL_THIRD_THEMES, PATH_THEMES, PATH_THIRD_THEMES);
        },

        'ThemeInstaller' => function ($ee) {
            return new Theme\ThemeInstaller();
        },

        'Thumbnail' => function ($ee) {
            return new Thumbnail\ThumbnailFactory();
        },

        'Profiler' => function ($ee) {
            return new Profiler\Profiler(ee()->lang, ee('View'), ee()->uri, ee('Format'));
        },

        'Resource' => function () {
            return new Resource\Request();
        },

        'Resource/Javascript' => function () {
            return new Resource\Javascript();
        },

        'Resource/Stylesheet' => function () {
            return new Resource\Stylesheet();
        },

        'Updater/Runner' => function ($ee) {
            return new Updater\Runner();
        },

        'Updater/Downloader' => function ($ee) {
            $config = $ee->make('Config')->getFile();

            if (! $config->has('site_url')) {
                $config->set('site_url', ee()->config->item('site_url'));
            }

            return new Updater\Downloader\Downloader(
                $ee->make('License')->getEELicense(),
                $ee->make('Curl'),
                $ee->make('Filesystem'),
                $ee->make('Updater/Logger'),
                $config
            );
        },

        'Updater/Preflight' => function ($ee) {
            $theme_paths = $ee->make('Model')->get('Config')
                ->filter('key', 'theme_folder_path')
                ->all()
                ->pluck('parsed_value');

            return new Updater\Downloader\Preflight(
                $ee->make('Filesystem'),
                $ee->make('Updater/Logger'),
                $ee->make('Config')->getFile(),
                array_unique($theme_paths)
            );
        },

        'Updater/PrepMajorUpgrade' => function ($ee) {
            return new Updater\Downloader\PrepMajorUpgrade(
                $ee->make('Filesystem'),
                $ee->make('Updater/Logger')
            );
        },

        'Updater/Unpacker' => function ($ee) {
            $filesystem = $ee->make('Filesystem');

            return new Updater\Downloader\Unpacker(
                $filesystem,
                new \ZipArchive(),
                new Updater\Verifier($filesystem),
                $ee->make('Updater/Logger'),
                new Updater\RequirementsCheckerLoader($filesystem)
            );
        },

        'Updater/Logger' => function ($ee) {
            return new Updater\Logger(
                PATH_CACHE . 'ee_update/update.log',
                $ee->make('Filesystem'),
                php_sapi_name() === 'cli'
            );
        },

        'Encrypt' => function ($ee, $key = null) {
            if (empty($key)) {
                $key = (ee()->config->item('encryption_key')) ?: ee()->db->username . ee()->db->password;
            }

            return new Encrypt\Encrypt($key);
        },

        'LivePreview' => function ($ee) {
            return new LivePreview\LivePreview(ee()->session);
        },

        'Str' => function ($ee) {
            return new Str();
        },

        'Variables/Parser' => function ($ee) {
            return new Template\Variables\LegacyParser();
        },

        'ActionGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new ActionGenerator($filesystem, $str, $data);
        },

        'AddonGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new AddonGenerator($filesystem, $str, $data);
        },

        'CommandGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new CommandGenerator($filesystem, $str, $data);
        },

        'ExtensionHookGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new ExtensionHookGenerator($filesystem, $str, $data);
        },

        'ExtensionGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new ExtensionGenerator($filesystem, $str, $data);
        },

        'FieldtypeGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new FieldtypeGenerator($filesystem, $str, $data);
        },

        'JumpsGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new JumpsGenerator($filesystem, $str, $data);
        },

        'ModelGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new ModelGenerator($filesystem, $str, $data);
        },

        'CpRouteGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new CpRouteGenerator($filesystem, $str, $data);
        },

        'ProletGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new ProletGenerator($filesystem, $str, $data);
        },

        'SidebarGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new SidebarGenerator($filesystem, $str, $data);
        },

        'TemplateTagGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new TemplateTagGenerator($filesystem, $str, $data);
        },

        'WidgetGenerator' => function ($ee, $data) {
            $filesystem = $ee->make('Filesystem');
            $str = $ee->make('Str');

            return new WidgetGenerator($filesystem, $str, $data);
        },

        'Consent' => function ($ee, $member_id = null) {
            $actor_userdata = ee()->session->userdata;
            if (! ee()->session->userdata('member_id')) {
                $actor_userdata['screen_name'] = lang('anonymous');
                $actor_userdata['username'] = lang('anonymous');
            }

            if (! $member_id) {
                $member_id = ee()->session->userdata('member_id');
            }

            return new Consent\Consent(
                $ee->make('Model'),
                ee()->input,
                ee()->session,
                $member_id,
                $actor_userdata,
                ee()->localize->now
            );
        },

        'ConditionalFieldEvaluator' => function ($ee, ChannelEntry $channelEntry) {
            return new ConditionalFields\Evaluator($channelEntry);
        },
    ),

    'services.singletons' => array(

        'Addon' => function ($ee) {
            return new Addon\Factory($ee->make('App'));
        },

        'Captcha' => function ($ee) {
            return new Library\Captcha();
        },

        'Channel/ChannelEntry' => function ($ee) {
            return new Channel\ChannelEntry();
        },

        'Channel/Reindex' => function ($ee) {
            return new Channel\Reindex();
        },

        'ChannelSet' => function ($ee) {
            return new ChannelSet\Factory(
                ee()->config->item('site_id')
            );
        },

        'Cookie' => function ($ee) {
            return new Cookie\Cookie();
        },

        'CookieRegistry' => function ($ee) {
            return new Consent\CookieRegistry();
        },

        'CP/Alert' => function ($ee) {
            $view = $ee->make('View')->make('_shared/alert');

            return new Alert\AlertCollection(ee()->session, $view, ee()->lang);
        },

        'CP/FilePicker' => function ($ee) {
            $fp = new FilePicker\Factory(
                $ee->make('CP/URL')
            );

            $fp->injectModal(
                $ee->make('CP/Modal'),
                $ee->make('View')->make('ee:_shared/modal'),
                ee()->cp
            );

            return $fp;
        },

        'CP/Modal' => function ($ee) {
            return new Modal\ModalCollection();
        },

        'CP/Sidebar' => function ($ee) {
            $view = $ee->make('View');

            return new Sidebar\Sidebar($view);
        },

        'CP/NavigationSidebar' => function ($ee) {
            $view = $ee->make('View');

            return new Sidebar\Navigation\NavigationSidebar($view);
        },

        'ConditionalFields' => function ($ee) {
            return new ConditionalFields\Factory();
        },

        'Config' => function ($ee) {
            return new Config\Factory($ee);
        },

        'Database' => function ($ee) {
            $config = $ee->make('Config')->getFile();

            $db_config = new Database\DBConfig($config);
            $db = new Database\Database($db_config);

            // we'll go by what's in the config file first - site prefs
            // may end up turning this off, but we load those so late that
            // it has led to some early query loops being missed. Better to
            // be more aggressive on this front.
            $save_queries = ($config->get('show_profiler', 'n') == 'y' or DEBUG == 1);
            $db->getLog()->saveQueries($save_queries);

            return $db;
        },

        'Emoji' => function ($ee) {
            return new Emoji\Emoji();
        },

        'Encrypt/Cookie' => function ($ee) {
            return new Encrypt\Cookie();
        },

        'File' => function ($ee) {
            return new File\Factory();
        },

        'FileUsage' => function ($ee) {
            return new File\Usage();
        },

        'Filesystem' => function ($ee) {
            return new Filesystem\Filesystem();
        },

        'Filesystem/Adapter' => function ($ee) {
            return new Filesystem\AdapterManager();
        },

        'IpAddress' => function ($ee) {
            return new IpAddress\Factory();
        },

        'License' => function ($ee) {
            $default_key_path = SYSPATH . 'ee/ExpressionEngine/ExpressionEngine.pub';
            $default_key = (is_readable($default_key_path)) ? file_get_contents($default_key_path) : '';

            return new License\LicenseFactory($default_key);
        },

        'Member' => function ($ee) {
            return new Member\Member();
        },

        'MimeType' => function ($ee) {
            $mimeType = new ExpressionEngine\Library\Mime\MimeType();
            $mimeType->whitelistMimesFromConfig();

            return $mimeType;
        },

        'Model/Datastore' => function ($ee) {
            $app = $ee->make('App');
            $addons = $ee->make('Addon')->installed();

            $installed_prefixes = array('ee');

            foreach ($addons as $addon) {
                $installed_prefixes[] = $addon->getProvider()->getPrefix();
            }

            $config = new Model\Configuration();
            $config->setDefaultPrefix($ee->getPrefix());
            $config->setModelAliases($app->getModels());
            $config->setEnabledPrefixes($installed_prefixes);
            $config->setModelDependencies($app->forward('getModelDependencies'));

            $app->setClassAliases();

            return new Model\DataStore($ee->make('Database'), $config);
        },

        'Permission' => function ($ee, $site_id = null) {
            $userdata = (REQ !== 'CLI') ? ee()->session->all_userdata() : [];
            $member = (REQ !== 'CLI') ? ee()->session->getMember() : false;
            $site_id = ($site_id) ?: ee()->config->item('site_id');

            return new Permission\Permission(
                $ee->make('Model'),
                $userdata,
                ($member) ? $member->getPermissions() : [],
                ($member) ? $member->Roles->getDictionary('role_id', 'name') : (REQ === 'CLI' ? [1 => 'SuperAdmin'] : []),
                $site_id
            );
        },

        'Request' => function ($ee) {
            return $ee->make('App')->getRequest();
        },

        'Response' => function ($ee) {
            return $ee->make('App')->getResponse();
        },

        'Security/XSS' => function ($ee) {
            return new Library\Security\XSS();
        },

        'Session' => function ($ee) {
            $session = ee()->session->getSessionModel();

            return new Session\Session($session);
        },

        'Validation' => function ($ee) {
            return new Validation\Factory();
        },

        'Variables/Modifiers' => function ($ee) {
            return new Template\Variables\Modifiers();
        },

        'View/Helpers' => function ($ee) {
            return new View\ViewHelpers();
        }
    ),

    // models exposed on the model service
    'models' => array(

        # ExpressionEngine\Model..

        // ..\Addon
        'Action' => 'Model\Addon\Action',
        'Extension' => 'Model\Addon\Extension',
        'Module' => 'Model\Addon\Module',
        'Plugin' => 'Model\Addon\Plugin',
        'Fieldtype' => 'Model\Addon\Fieldtype',

        // ..\Category
        'Category' => 'Model\Category\Category',
        'CategoryGroup' => 'Model\Category\CategoryGroup',
        'CategoryGroupSettings' => 'Model\Category\CategoryGroupSettings',
        'CategoryField' => 'Model\Category\CategoryField',

        // ..\File
        'UploadDestination' => 'Model\File\UploadDestination',
        'FileDimension' => 'Model\File\FileDimension',
        'FileSystemEntity' => 'Model\File\FileSystemEntity',
        'File' => 'Model\File\File',
        'Directory' => 'Model\File\Directory',
        //'FileField' => 'Model\File\FileField',
        'Watermark' => 'Model\File\Watermark',

        // ..\Log
        'CpLog' => 'Model\Log\CpLog',
        'DeveloperLog' => 'Model\Log\DeveloperLog',
        'EmailConsoleCache' => 'Model\Log\EmailConsoleCache',

        // ..\Security
        'Captcha' => 'Model\Security\Captcha',
        'Throttle' => 'Model\Security\Throttle',
        'ResetPassword' => 'Model\Security\ResetPassword',

        // ..\Session
        'Session' => 'Model\Session\Session',
        'RememberMe' => 'Model\Session\RememberMe',

        // ..\Site
        'Site' => 'Model\Site\Site',
        'Stats' => 'Model\Site\Stats',

        // ..\Status
        'Status' => 'Model\Status\Status',

        // ..\Template
        'Template' => 'Model\Template\Template',
        'TemplateGroup' => 'Model\Template\TemplateGroup',
        'TemplateRoute' => 'Model\Template\TemplateRoute',
        'GlobalVariable' => 'Model\Template\GlobalVariable',
        'Snippet' => 'Model\Template\Snippet',
        'SpecialtyTemplate' => 'Model\Template\SpecialtyTemplate',

        // ..\Channel
        'Channel' => 'Model\Channel\Channel',
        'ChannelFieldGroup' => 'Model\Channel\ChannelFieldGroup',
        'ChannelField' => 'Model\Channel\ChannelField',
        'ChannelEntry' => 'Model\Channel\ChannelEntry',
        'ChannelEntryAutosave' => 'Model\Channel\ChannelEntryAutosave',
        'ChannelEntryVersion' => 'Model\Channel\ChannelEntryVersion',
        'ChannelFormSettings' => 'Model\Channel\ChannelFormSettings',
        'ChannelLayout' => 'Model\Channel\ChannelLayout',
        'FieldData' => 'Model\Content\FieldData',

        // ..\ConditionalFields
        'FieldConditionSet' => 'Model\ConditionalFields\FieldConditionSet',
        'FieldCondition' => 'Model\ConditionalFields\FieldCondition',

        // ..\Comment
        'Comment' => 'Model\Comment\Comment',
        'CommentSubscription' => 'Model\Comment\CommentSubscription',

        // ..\Message
        'Message' => 'Model\Message\Message',
        'MessageAttachment' => 'Model\Message\Attachment',
        'MessageFolder' => 'Model\Message\Folder',
        'ListedMember' => 'Model\Message\ListedMember',
        'MessageCopy' => 'Model\Message\Copy',

        // ..\Member
        'HTMLButton' => 'Model\Member\HTMLButton',
        'Member' => 'Model\Member\Member',
        'MemberField' => 'Model\Member\MemberField',
        'MemberNewsView' => 'Model\Member\NewsView',
        'OnlineMember' => 'Model\Member\Online',

        // ..\Menu
        'MenuSet' => 'Model\Menu\MenuSet',
        'MenuItem' => 'Model\Menu\MenuItem',

        // ..\Migration
        'Migration' => 'Model\Migration\Migration',

        // ..\Dashboard
        'DashboardLayout' => 'Model\Dashboard\DashboardLayout',

        // ..\Search
        'SearchLog' => 'Model\Search\SearchLog',

        // ..\Email
        'EmailCache' => 'Model\Email\EmailCache',
        'EmailTracker' => 'Model\Email\EmailTracker',

        // ..\Revision
        'RevisionTracker' => 'Model\Revision\RevisionTracker',

        // ..\Consent
        'Consent' => 'Model\Consent\Consent',
        'ConsentAuditLog' => 'Model\Consent\ConsentAuditLog',
        'ConsentRequest' => 'Model\Consent\ConsentRequest',
        'ConsentRequestVersion' => 'Model\Consent\ConsentRequestVersion',

        // ..\Cookie
        'CookieSetting' => 'Model\Cookie\CookieSetting',

        // ..\Permission
        'Permission' => 'Model\Permission\Permission',

        // ..\Role
        'Role' => 'Model\Role\Role',
        'RoleGroup' => 'Model\Role\RoleGroup',
        'RoleSetting' => 'Model\Role\RoleSetting',
        'MemberGroup' => 'Model\Role\MemberGroup',

        // ..\Config
        'Config' => 'Model\Config\Config',

        // ..\EntryManager
        'EntryManagerView' => 'Model\EntryManager\View',

        // ..\FileManager
        'FileManagerView' => 'Model\File\FileManagerView',

        // ..\FileManager
        'MemberManagerView' => 'Model\Member\MemberManagerView',
    ),

    'cookies.necessary' => [
        'csrf_token',
        'flash',
        'remember',
        'sessionid',
        'visitor_consents',
    ],
    'cookies.functionality' => [
        'last_activity',
        'last_visit',
        'anon',
        'tracker',
        'viewtype',
        'cp_last_site_id',
        'ee_cp_viewmode',
        'secondary_sidebar',
        'collapsed_nav'
    ],
    'cookie_settings' => [
        'csrf_token' => [
            'description' => 'lang:cookie_csrf_token_desc',
        ],
        'flash' => [
            'description' => 'lang:cookie_flash_desc',
        ],
        'remember' => [
            'description' => 'lang:cookie_remember_desc',
        ],
        'sessionid' => [
            'description' => 'lang:cookie_sessionid_desc',
        ],
        'visitor_consents' => [
            'description' => 'lang:cookie_visitor_consents_desc',
        ],
        'last_activity' => [
            'description' => 'lang:cookie_last_activity_desc',
        ],
        'last_visit' => [
            'description' => 'lang:cookie_last_visit_desc',
        ],
        'anon' => [
            'description' => 'lang:cookie_anon_desc',
        ],
        'tracker' => [
            'description' => 'lang:cookie_tracker_desc',
        ],
        'viewtype' => [
            'description' => 'lang:cookie_viewtype_desc',
            'provider' => 'cp',
        ],
        'cp_last_site_id' => [
            'description' => 'lang:cookie_cp_last_site_id_desc',
            'provider' => 'cp',
        ],
        'collapsed_nav' => [
            'description' => 'lang:cookie_collapsed_nav_desc',
            'provider' => 'cp',
        ],
        'secondary_sidebar' => [
            'description' => 'lang:cookie_secondary_sidebar_desc',
            'provider' => 'cp',
        ],
        'ee_cp_viewmode' => [
            'description' => 'lang:cookie_ee_cp_viewmode_desc',
            'provider' => 'cp',
        ],
    ],
];

if (is_dir(SYSPATH . 'ee/ExpressionEngine/Addons/pro/')) {
    foreach ($setup['models'] as $model => $namespace) {
        $pro_file = SYSPATH . 'ee/ExpressionEngine/Addons/pro/' . str_replace("\\", "/", $namespace) . '.php';
        if (file_exists($pro_file)) {
            $setup['models'][$model] = "\ExpressionEngine\Addons\pro\\" . $namespace;
        }
    }
}

return $setup;

// EOF
