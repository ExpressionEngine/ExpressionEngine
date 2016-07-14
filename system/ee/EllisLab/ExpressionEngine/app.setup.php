<?php

use EllisLab\ExpressionEngine\Library;
use EllisLab\ExpressionEngine\Library\Filesystem;
use EllisLab\ExpressionEngine\Library\Curl;
use EllisLab\ExpressionEngine\Service\Addon;
use EllisLab\ExpressionEngine\Service\Alert;
use EllisLab\ExpressionEngine\Service\Category;
use EllisLab\ExpressionEngine\Service\ChannelSet;
use EllisLab\ExpressionEngine\Service\Config;
use EllisLab\ExpressionEngine\Service\CustomMenu;
use EllisLab\ExpressionEngine\Service\Database;
use EllisLab\ExpressionEngine\Service\EntryListing;
use EllisLab\ExpressionEngine\Service\Event;
use EllisLab\ExpressionEngine\Service\File;
use EllisLab\ExpressionEngine\Service\Filter;
use EllisLab\ExpressionEngine\Service\Formatter;
use EllisLab\ExpressionEngine\Service\License;
use EllisLab\ExpressionEngine\Service\Modal;
use EllisLab\ExpressionEngine\Service\Model;
use EllisLab\ExpressionEngine\Service\Permission;
use EllisLab\ExpressionEngine\Service\Profiler;
use EllisLab\ExpressionEngine\Service\Sidebar;
use EllisLab\ExpressionEngine\Service\Theme;
use EllisLab\ExpressionEngine\Service\Thumbnail;
use EllisLab\ExpressionEngine\Service\URL;
use EllisLab\ExpressionEngine\Service\Validation;
use EllisLab\ExpressionEngine\Service\View;
use EllisLab\Addons\Spam\Service\Spam;
use EllisLab\Addons\FilePicker\Service\FilePicker;

// TODO should put the version in here at some point ...
return array(

	'author' => 'EllisLab',
	'name' => 'ExpressionEngine',
	'description' => 'The worlds most flexible content management system.',

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

		'CP/EntryListing' => function($ee, $search_value)
		{
			 return new EntryListing\EntryListing(
				ee()->config->item('site_id'),
				(ee()->session->userdata['group_id'] == 1),
				array_keys(ee()->session->userdata['assigned_channels']),
				ee()->localize->now,
				$search_value
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
			$grid = new Library\CP\GridInput(
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
			$cp_url = ee()->config->item('cp_url');
			$site_index = ee()->functions->fetch_site_index(0,0);
			$uri_string = ee()->uri->uri_string();
			$session_id = ee()->session->session_id();
			$default_cp_url = SELF;

			$factory = new URL\URLFactory($cp_url, $site_index, $uri_string, $session_id, $default_cp_url);

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
			return new Formatter\FormatterFactory(ee()->lang);
		},

		'Curl' => function($ee)
		{
			return new Curl\RequestFactory();
		},

		'View' => function($ee)
		{
			return new View\ViewFactory($ee);
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
			return new Theme\Theme(PATH_THEMES, URL_THEMES, PATH_THIRD_THEMES, URL_THIRD_THEMES);
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

		'File' => function($ee)
		{
			return new File\Factory();
		},

		'License' => function($ee)
		{
			$default_key_path = SYSPATH.'ee/EllisLab/ExpressionEngine/EllisLab.pub';
			$default_key = (is_readable($default_key_path)) ? file_get_contents($default_key_path) : '';

			return new License\LicenseFactory($default_key);
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

			return new Model\DataStore(
				$ee->make('Database'),
				$app->getModels(),
				$app->forward('getModelDependencies'),
				$ee->getPrefix(),
				$installed_prefixes
			);
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
			// empty

			// ..\Site
			'Site' => 'Model\Site\Site',
			'Stats' => 'Model\Site\Stats',

			// ..\Status
			'Status' => 'Model\Status\Status',
			'StatusGroup' => 'Model\Status\StatusGroup',

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

			// ..\Comment
			'Comment' => 'Model\Comment\Comment',
			'CommentSubscription' => 'Model\Comment\CommentSubscription',

			// ..\Member
			'HTMLButton' => 'Model\Member\HTMLButton',
			'Member' => 'Model\Member\Member',
			'MemberField' => 'Model\Member\MemberField',
			'MemberGroup' => 'Model\Member\MemberGroup',

			// ..\Menu
			'MenuSet' => 'Model\Menu\MenuSet',
			'MenuItem' => 'Model\Menu\MenuItem',

			// ..\Search
			'SearchLog' => 'Model\Search\SearchLog',

			// ..\Email
			'EmailCache' => 'Model\Email\EmailCache',
			'EmailTracker' => 'Model\Email\EmailTracker',

			// ..\Revision
			'RevisionTracker' => 'Model\Revision\RevisionTracker'
	)
);

// EOF
