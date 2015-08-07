<?php

use EllisLab\ExpressionEngine\Library;
use EllisLab\ExpressionEngine\Library\Event;
use EllisLab\ExpressionEngine\Library\Filesystem;
use EllisLab\ExpressionEngine\Service\Addon;
use EllisLab\ExpressionEngine\Service\Alert;
use EllisLab\ExpressionEngine\Service\Config;
use EllisLab\ExpressionEngine\Service\Database;
use EllisLab\ExpressionEngine\Service\Filter;
use EllisLab\ExpressionEngine\Service\Grid;
use EllisLab\ExpressionEngine\Service\Model;
use EllisLab\ExpressionEngine\Service\Validation;
use EllisLab\ExpressionEngine\Service\View;
use EllisLab\ExpressionEngine\Service\Sidebar;
use EllisLab\ExpressionEngine\Service\Thumbnail;

// TODO should put the version in here at some point ...
return array(

	'author' => 'EllisLab',
	'name' => 'ExpressionEngine',
	'description' => 'The worlds most flexible content management system.',

	'namespace' => 'EllisLab\ExpressionEngine',

	'views' => '../../views',

	'services' => array(

		'CP/GridInput' => function($ee, $config = array())
		{
			$grid = new Library\CP\GridInput(
				$config,
				ee()->view,
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

			return new Library\CP\URL($path, $session_id, $qs, $cp_url);
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

		'View' => function($ee)
		{
			return new View\ViewFactory($ee);
		},

		'Filter' => function($ee)
		{
			$filters = new Filter\FilterFactory($ee->make('View', '_shared/filters'));
			$filters->setDIContainer($ee);
			return $filters;
		},

		'Model' => function($ee)
		{
			$frontend = new Model\Frontend($ee->make('Model/Datastore'));
			$frontend->setValidationFactory($ee->make('Validation'));

			return $frontend;
		},

		'Sidebar' => function($ee)
		{
			$view = $ee->make('View');
			return new Sidebar\Sidebar($view);
		},

		'Thumbnail' => function($ee)
		{
			return new Thumbnail\ThumbnailFactory();
		}

	),

	'services.singletons' => array(

		'Addon' => function($ee)
		{
			return new Addon\Factory($ee->make('App'));
		},

		'Alert' => function($ee)
		{
			$view = $ee->make('View')->make('_shared/alert');
			return new Alert\AlertCollection(ee()->session, $view);
		},

		'Captcha' => function($ee)
		{
			return new Library\Captcha();
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

		'Model/Datastore' => function($ee)
		{
			$app = $ee->make('App');

			return new Model\DataStore(
				$ee->make('Database'),
				$app->getModels(),
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

		# EllisLab\ExpressionEngine\Module..

			// ..\Channel
			'Channel' => 'Module\Channel\Model\Channel',
			'ChannelFieldGroup'=> 'Module\Channel\Model\ChannelFieldGroup',
			'ChannelField' => 'Module\Channel\Model\ChannelField',
			'ChannelEntry' => 'Module\Channel\Model\ChannelEntry',
			'ChannelEntryAutosave' => 'Module\Channel\Model\ChannelEntryAutosave',
			'ChannelFormSettings' => 'Module\Channel\Model\ChannelFormSettings',
			'ChannelLayout' => 'Module\Channel\Model\ChannelLayout',

			// ..\Comment
			'Comment' => 'Module\Comment\Model\Comment',
			'CommentSubscription' => 'Module\Comment\Model\CommentSubscription',

			// ..\Member
			'HTMLButton' => 'Module\Member\Model\HTMLButton',
			'Member' => 'Module\Member\Model\Member',
			'MemberField' => 'Module\Member\Model\MemberField',
			'MemberGroup' => 'Module\Member\Model\MemberGroup',

			// ..\Search
			'SearchLog' => 'Module\Search\Model\SearchLog',

			// TODO: FIND A NEW HOME FOR THESE
			'EmailCache' => 'Model\EmailCache',
	)
);
