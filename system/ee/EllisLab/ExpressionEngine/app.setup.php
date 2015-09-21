<?php

use EllisLab\ExpressionEngine\Library;
use EllisLab\ExpressionEngine\Library\Event;
use EllisLab\ExpressionEngine\Library\Filesystem;
use EllisLab\ExpressionEngine\Library\Request;
use EllisLab\ExpressionEngine\Service\Addon;
use EllisLab\ExpressionEngine\Service\Alert;
use EllisLab\ExpressionEngine\Service\Config;
use EllisLab\ExpressionEngine\Service\Database;
use EllisLab\ExpressionEngine\Service\EntryListing;
use EllisLab\ExpressionEngine\Service\Filter;
use EllisLab\ExpressionEngine\Service\Grid;
use EllisLab\ExpressionEngine\Service\License;
use EllisLab\ExpressionEngine\Service\Modal;
use EllisLab\ExpressionEngine\Service\Model;
use EllisLab\ExpressionEngine\Service\Validation;
use EllisLab\ExpressionEngine\Service\View;
use EllisLab\ExpressionEngine\Service\Sidebar;
use EllisLab\ExpressionEngine\Service\Thumbnail;
use EllisLab\Addons\Spam\Service\Spam;
use EllisLab\ExpressionEngine\Service\Profiler;

// TODO should put the version in here at some point ...
return array(

	'author' => 'EllisLab',
	'name' => 'ExpressionEngine',
	'description' => 'The worlds most flexible content management system.',

	'namespace' => 'EllisLab\ExpressionEngine',

	'services' => array(

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
			return Library\CP\Table::fromGlobals($config);
		},

		'CP/URL' => function($ee, $path, $qs = array(), $cp_url = '', $session_id = NULL)
		{
			$session_id = $session_id ?: ee()->session->session_id();
			$cp_url = (empty($cp_url)) ? SELF : (string) $cp_url;

			return new Library\CP\URL($path, $session_id, $qs, $cp_url, ee()->uri->uri_string);
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

		'Request' => function($ee)
		{
			return new Request\RequestFactory();
		},

		'View' => function($ee)
		{
			return new View\ViewFactory($ee);
		},

		'Model' => function($ee)
		{
			$frontend = new Model\Frontend($ee->make('Model/Datastore'));
			$frontend->setValidationFactory($ee->make('Validation'));

			return $frontend;
		},

		'Spam' => function($ee)
		{
			return new Spam();
		},

		'Thumbnail' => function($ee)
		{
			return new Thumbnail\ThumbnailFactory();
		},

		'Profiler' => function($ee)
		{
			return new Profiler\Profiler(ee()->lang, ee('View'));
		}

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

		'CP/Alert' => function($ee)
		{
			$view = $ee->make('View')->make('_shared/alert');
			return new Alert\AlertCollection(ee()->session, $view);
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

			return new Database\Database($db_config);
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

			return new Model\DataStore(
				$ee->make('Database'),
				$app->getModels(),
				$app->forward('getModelDependencies'),
				$ee->getPrefix()
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

			// ..\Search
			'SearchLog' => 'Model\Search\SearchLog',

			// ..\Email
			'EmailCache' => 'Model\Email\EmailCache',
			'EmailTracker' => 'Model\Email\EmailTracker',

			// ..\Revision
			'RevisionTracker' => 'Model\Revision\RevisionTracker'
	)
);
