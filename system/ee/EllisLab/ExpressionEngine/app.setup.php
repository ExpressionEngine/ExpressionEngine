<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\ExpressionEngine\Library;
use EllisLab\ExpressionEngine\Library\Filesystem;
use EllisLab\ExpressionEngine\Library\Curl;
use EllisLab\ExpressionEngine\Service\Addon;
use EllisLab\ExpressionEngine\Service\Alert;
use EllisLab\ExpressionEngine\Service\Category;
use EllisLab\ExpressionEngine\Service\ChannelSet;
use EllisLab\ExpressionEngine\Service\Config;
use EllisLab\ExpressionEngine\Service\Consent;
use EllisLab\ExpressionEngine\Service\Cookie;
use EllisLab\ExpressionEngine\Service\CustomMenu;
use EllisLab\ExpressionEngine\Service\Database;
use EllisLab\ExpressionEngine\Service\Encrypt;
use EllisLab\ExpressionEngine\Service\EntryListing;
use EllisLab\ExpressionEngine\Service\Event;
use EllisLab\ExpressionEngine\Service\File;
use EllisLab\ExpressionEngine\Service\Filter;
use EllisLab\ExpressionEngine\Service\Formatter;
use EllisLab\ExpressionEngine\Service\IpAddress;
use EllisLab\ExpressionEngine\Service\License;
use EllisLab\ExpressionEngine\Service\LivePreview;
use EllisLab\ExpressionEngine\Service\Logger;
use EllisLab\ExpressionEngine\Service\Member;
use EllisLab\ExpressionEngine\Service\Memory;
use EllisLab\ExpressionEngine\Service\Modal;
use EllisLab\ExpressionEngine\Service\Model;
use EllisLab\ExpressionEngine\Service\Permission;
use EllisLab\ExpressionEngine\Service\Profiler;
use EllisLab\ExpressionEngine\Service\Sidebar;
use EllisLab\ExpressionEngine\Service\Theme;
use EllisLab\ExpressionEngine\Service\Thumbnail;
use EllisLab\ExpressionEngine\Service\URL;
use EllisLab\ExpressionEngine\Service\Updater;
use EllisLab\ExpressionEngine\Service\Validation;
use EllisLab\ExpressionEngine\Service\Template;
use EllisLab\ExpressionEngine\Service\View;
use EllisLab\Addons\Spam\Service\Spam;
use EllisLab\Addons\FilePicker\Service\FilePicker;

// TODO should put the version in here at some point ...
return [

	'author' => 'EllisLab',
	'name' => 'ExpressionEngine',
	'description' => "The world's most flexible content management system.",

	'namespace' => 'EllisLab\ExpressionEngine',

	'services' => array(

		'Category' => function($ee)
		{
			return new Category\Factory;
		},

		'CP/CustomMenu' => function($ee)
		{
			return new CustomMenu\Menu;
		},

		'CP/EntryListing' => function($ee, $search_value, $search_in = NULL, $include_author_filter = FALSE)
		{
			 return new EntryListing\EntryListing(
				ee()->config->item('site_id'),
				(ee()->session->userdata['group_id'] == 1),
				array_keys(ee()->session->userdata['assigned_channels']),
				ee()->localize->now,
				$search_value,
				$search_in,
				$include_author_filter
			);
		},

		'CP/Filter' => function($ee)
		{
			$filters = new Filter\FilterFactory($ee->make('View', '_shared/filters'));
			$filters->setDIContainer($ee);
			return $filters;
		},

		'CP/GridInput' => function($ee, $config = array())
		{
			ee()->lang->load('content');
			$grid = new Library\CP\GridInput(
				$config,
				ee()->cp,
				ee()->config,
				ee()->javascript
			);

			return $grid;
		},

		'CP/MiniGridInput' => function($ee, $config = array())
		{
			ee()->lang->load('content');
			$grid = new Library\CP\MiniGridInput(
				$config,
				ee()->cp,
				ee()->config,
				ee()->javascript
			);

			return $grid;
		},

		'CP/Table' => function($ee, $config = array())
		{
			$table = Library\CP\Table::fromGlobals($config);
			$table->setLocalize(ee()->localize);
			return $table;
		},

		'CP/URL' => function($ee, $path = NULL)
		{
			if ( ! isset(ee()->session))
			{
				ee()->load->library('session');
			}

			$cp_url = ee()->config->item('cp_url');
			$site_index = ee()->functions->fetch_site_index(0,0);
			$uri_string = ee()->uri->uri_string();
			$session_id = ee()->session->session_id();
			$default_cp_url = SELF;

			$factory = new URL\URLFactory($cp_url, $site_index, $uri_string, $session_id, $default_cp_url, $ee->make('Encrypt'));

			return (is_null($path)) ? $factory : $factory->make($path);
		},

		'CP/Pagination' => function($ee, $total_count)
		{
			$view = $ee->make('View')->make('_shared/pagination');
			return new Library\CP\Pagination($total_count, $view);
		},

		'CSV' => function ($ee)
		{
			return new Library\Data\CSV();
		},

		'db' => function($ee)
		{
			return $ee->make('Database')->newQuery();
		},

		'Database/Backup' => function($ee, $file_path)
		{
			$filesystem = $ee->make('Filesystem');
			$backup_query = $ee->make('Database/Backup/Query');
			$row_limit = ee()->config->item('db_backup_row_limit');

			return new Database\Backup\Backup($filesystem, $backup_query, $file_path, $row_limit);
		},

		'Database/Backup/Query' => function($ee)
		{
			return new Database\Backup\Query($ee->make('db'));
		},

		'Database/Restore' => function($ee)
		{
			$filesystem = $ee->make('Filesystem');

			return new Database\Backup\Restore($ee->make('db'), $filesystem);
		},

		'Event' => function($ee)
		{
			return new Event\Emitter();
		},

		'Filesystem' => function($ee)
		{
			return new Filesystem\Filesystem();
		},

		'Format' => function($ee)
		{
			static $format_opts;
			if ($format_opts === NULL)
			{
				$format_opts += (extension_loaded('intl')) ? 0b00000001 : 0;
			}

			$config_items = [
				'censor_replacement' => ee()->config->item('censor_replacement'),
				'censored_words' => ee()->config->item('censored_words'),
				'foreign_chars' => ee()->config->loadFile('foreign_chars'),
				'stopwords' => ee()->config->loadFile('stopwords'),
				'word_separator' => ee()->config->item('word_separator'),
				'emoji_regex' => EMOJI_REGEX,
				'emoji_map' => ee()->config->loadFile('emoji'),
			];

			return new Formatter\FormatterFactory(ee()->lang, ee()->session, $config_items, $format_opts);
		},

		'Curl' => function($ee)
		{
			return new Curl\RequestFactory();
		},

		'View' => function($ee)
		{
			return new View\ViewFactory($ee);
		},

		'Memory' => function($ee)
		{
			return new Memory\Memory();
		},

		'Model' => function($ee)
		{
			$facade = new Model\Facade($ee->make('Model/Datastore'));
			$facade->setValidationFactory($ee->make('Validation'));

			return $facade;
		},

		'Spam' => function($ee)
		{
			return new Spam();
		},

		'Theme' => function($ee)
		{
			return new Theme\Theme(PATH_THEME_TEMPLATES, URL_THEMES, PATH_THIRD_THEME_TEMPLATES, URL_THIRD_THEMES, PATH_THEMES, PATH_THIRD_THEMES);
		},

		'ThemeInstaller' => function($ee)
		{
			return new Theme\ThemeInstaller();
		},

		'Thumbnail' => function($ee)
		{
			return new Thumbnail\ThumbnailFactory();
		},

		'Profiler' => function($ee)
		{
			return new Profiler\Profiler(ee()->lang, ee('View'), ee()->uri, ee('Format'));
		},

		'Permission' => function($ee)
		{
			$userdata = ee()->session->userdata;
			return new Permission\Permission($userdata);
		},

		'Updater/Runner' => function($ee)
		{
			return new Updater\Runner();
		},

		'Updater/Downloader' => function($ee)
		{
			$config = $ee->make('Config')->getFile();

			if ( ! $config->has('site_url'))
			{
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

		'Updater/Preflight' => function($ee)
		{
			return new Updater\Downloader\Preflight(
				$ee->make('Filesystem'),
				$ee->make('Updater/Logger'),
				$ee->make('Config')->getFile(),
				$ee->make('Model')->get('Site')->all()
			);
		},

		'Updater/Unpacker' => function($ee)
		{
			$filesystem = $ee->make('Filesystem');

			return new Updater\Downloader\Unpacker(
				$filesystem,
				new \ZipArchive(),
				new Updater\Verifier($filesystem),
				$ee->make('Updater/Logger'),
				new Updater\RequirementsCheckerLoader($filesystem)
			);
		},

		'Updater/Logger' => function($ee)
		{
			return new Updater\Logger(
				PATH_CACHE.'ee_update/update.log',
				$ee->make('Filesystem'),
				php_sapi_name() === 'cli'
			);
		},

		'Encrypt' => function($ee, $key = NULL)
		{
			if (empty($key))
			{
				$key = (ee()->config->item('encryption_key')) ?: ee()->db->username.ee()->db->password;
			}

			return new Encrypt\Encrypt($key);
		},

		'LivePreview' => function($ee)
		{
			return new LivePreview\LivePreview(ee()->session);
		},

		'Variables/Parser' => function ($ee)
		{
			return new Template\Variables\LegacyParser();
		},

		'Consent' => function($ee, $member_id = NULL)
		{
			$actor_userdata = ee()->session->userdata;
			if ( ! ee()->session->userdata('member_id'))
			{
				$actor_userdata['screen_name'] = lang('anonymous');
				$actor_userdata['username'] = lang('anonymous');
			}

			if ( ! $member_id)
			{
				$member_id = $actor_userdata['member_id'];
			}

			return new Consent\Consent(
				$ee->make('Model'),
				ee()->input,
				ee()->session,
				$member_id,
				$actor_userdata,
				ee()->localize->now);
		},

	),

	'services.singletons' => array(

		'Addon' => function($ee)
		{
			return new Addon\Factory($ee->make('App'));
		},

		'Captcha' => function($ee)
		{
			return new Library\Captcha();
		},

		'ChannelSet' => function($ee)
		{
			return new ChannelSet\Factory(
				ee()->config->item('site_id')
			);
		},

		'Cookie' => function($ee)
		{
			return new Cookie\Cookie();
		},

		'CookieRegistry' => function($ee)
		{
			return new Consent\CookieRegistry();
		},

		'CP/Alert' => function($ee)
		{
			$view = $ee->make('View')->make('_shared/alert');
			return new Alert\AlertCollection(ee()->session, $view, ee()->lang);
		},

		'CP/FilePicker' => function($ee)
		{
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


		'CP/Modal' => function($ee)
		{
			return new Modal\ModalCollection;
		},

		'CP/Sidebar' => function($ee)
		{
			$view = $ee->make('View');
			return new Sidebar\Sidebar($view);
		},

		'Config' => function($ee)
		{
			return new Config\Factory($ee);
		},

		'Database' => function($ee)
		{
			$config = $ee->make('Config')->getFile();

			$db_config = new Database\DBConfig($config);
			$db = new Database\Database($db_config);

			// we'll go by what's in the config file first - site prefs
			// may end up turning this off, but we load those so late that
			// it has led to some early query loops being missed. Better to
			// be more aggressive on this front.
			$save_queries = ($config->get('show_profiler', 'n') == 'y' OR DEBUG == 1);
			$db->getLog()->saveQueries($save_queries);

			return $db;
		},

		'Encrypt/Cookie' => function($ee)
		{
			return new Encrypt\Cookie();
		},

		'File' => function($ee)
		{
			return new File\Factory();
		},

		'IpAddress' => function($ee)
		{
			return new IpAddress\Factory();
		},

		'License' => function($ee)
		{
			$default_key_path = SYSPATH.'ee/EllisLab/ExpressionEngine/EllisLab.pub';
			$default_key = (is_readable($default_key_path)) ? file_get_contents($default_key_path) : '';

			return new License\LicenseFactory($default_key);
		},

		'Member' => function($ee)
		{
			return new Member\Member();
		},

		'Model/Datastore' => function($ee)
		{
			$app = $ee->make('App');
			$addons = $ee->make('Addon')->installed();

			$installed_prefixes = array('ee');

			foreach ($addons as $addon)
			{
				$installed_prefixes[] = $addon->getProvider()->getPrefix();
			}

			$config = new Model\Configuration();
			$config->setDefaultPrefix($ee->getPrefix());
			$config->setModelAliases($app->getModels());
			$config->setEnabledPrefixes($installed_prefixes);
			$config->setModelDependencies($app->forward('getModelDependencies'));

			return new Model\DataStore($ee->make('Database'), $config);
		},

		'Request' => function($ee)
		{
			return $ee->make('App')->getRequest();
		},

		'Response' => function($ee)
		{
			return $ee->make('App')->getResponse();
		},

		'Security/XSS' => function($ee)
		{
			return new Library\Security\XSS();
		},

		'Validation' => function($ee)
		{
			return new Validation\Factory();
		},

		'View/Helpers' => function($ee)
		{
			return new View\ViewHelpers();
		}
	),

	// models exposed on the model service
	'models' => array(

		# EllisLab\ExpressionEngine\Model..

			// ..\Addon
			'Action' => 'Model\Addon\Action',
			'Extension' => 'Model\Addon\Extension',
			'Module' => 'Model\Addon\Module',
			'Plugin' => 'Model\Addon\Plugin',
			'Fieldtype' => 'Model\Addon\Fieldtype',

			// ..\Category
			'Category' => 'Model\Category\Category',
			'CategoryGroup' => 'Model\Category\CategoryGroup',
			'CategoryField' => 'Model\Category\CategoryField',

			// ..\File
			'UploadDestination' => 'Model\File\UploadDestination',
			'FileDimension' => 'Model\File\FileDimension',
			'File' => 'Model\File\File',
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
			'TemplateGroup'  => 'Model\Template\TemplateGroup',
			'TemplateRoute'  => 'Model\Template\TemplateRoute',
			'GlobalVariable'  => 'Model\Template\GlobalVariable',
			'Snippet' => 'Model\Template\Snippet',
			'SpecialtyTemplate' => 'Model\Template\SpecialtyTemplate',

			// ..\Channel
			'Channel' => 'Model\Channel\Channel',
			'ChannelFieldGroup'=> 'Model\Channel\ChannelFieldGroup',
			'ChannelField' => 'Model\Channel\ChannelField',
			'ChannelEntry' => 'Model\Channel\ChannelEntry',
			'ChannelEntryAutosave' => 'Model\Channel\ChannelEntryAutosave',
			'ChannelEntryVersion' => 'Model\Channel\ChannelEntryVersion',
			'ChannelFormSettings' => 'Model\Channel\ChannelFormSettings',
			'ChannelLayout' => 'Model\Channel\ChannelLayout',
			'FieldData' => 'Model\Content\FieldData',

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
			'MemberGroup' => 'Model\Member\MemberGroup',
			'MemberNewsView' => 'Model\Member\NewsView',
			'OnlineMember' => 'Model\Member\Online',

			// ..\Menu
			'MenuSet' => 'Model\Menu\MenuSet',
			'MenuItem' => 'Model\Menu\MenuItem',

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
			'ConsentRequestVersion' => 'Model\Consent\ConsentRequestVersion'
	),
	'cookies.necessary' => [
		'cp_last_site_id',
		'csrf_token',
		'flash',
		'last_activity',
		'last_visit',
		'remember',
		'sessionid',
		'visitor_consents',
	],
	'cookies.functionality' => [
		'anon',
		'expiration',
		'forum_theme',
		'forum_topics',
		'my_email',
		'my_location',
		'my_name',
		'my_url',
		'notify_me',
		'save_info',
		'tracker',
	],
];

// EOF
