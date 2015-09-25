<?php

return array(
	'author'      => 'EllisLab',
	'author_url'  => 'http://ellislab.com/',
	'name'        => 'Wiki',
	'description' => '',
	'version'     => '2.3',
	'namespace'   => 'User\Addons\Wiki',
	'settings_exist' => TRUE,
	'docs_url'    => 'http://github/wiki/index.html',
	'models' => array(
		'WikiNamespace' => 'Model\WikiNamespace',
		'Wiki' => 'Model\Wiki',
		'Page' => 'Model\Page',
		'Revision' => 'Model\Revision',
		'Category' => 'Model\Category',
		'CategoryArticle' => 'Model\CategoryArticle',
		'Search' => 'Model\Search',
		'Upload' => 'Model\Upload'		
		
	),
	'models.dependencies' => array(
		'Revision'   => array(
			'ee:Member'
		),
		'Upload'   => array(
			'ee:Member',
			'ee:File'
		)
	)

);