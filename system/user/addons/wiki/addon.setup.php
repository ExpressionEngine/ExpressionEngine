<?php

return array(
	'author'      => 'EllisLab',
	'author_url'  => 'http://ellislab.com/',
	'name'        => 'Wiki',
	'description' => '',
	'version'     => '3.0',
	'namespace'   => 'User\Addons\Wiki',
	'settings_exist' => TRUE,
	'docs_url'    => 'https://github.com/EllisLab/Wiki/wiki',
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
//			'ee:Member',
			'ee:File'
		)
	)
);