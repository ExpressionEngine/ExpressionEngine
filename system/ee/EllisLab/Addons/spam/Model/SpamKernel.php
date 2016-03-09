<?php

namespace EllisLab\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamKernel extends Model {

	protected static $_table_name = 'spam_kernels';
	protected static $_primary_key = 'kernel_id';

	protected static $_relationships = array(
		'Vocabulary' => array(
			'type' => 'hasMany',
			'model' => 'SpamVocabulary',
			'to_key' => 'kernel_id'
		),
		'Parameters' => array(
			'type' => 'hasMany',
			'model' => 'SpamParameter',
			'to_key' => 'kernel_id'
		),
		'Training' => array(
			'type' => 'hasMany',
			'model' => 'SpamTraining',
			'to_key' => 'kernel_id'
		),
	);

	protected $kernel_id;
	protected $name;
	protected $count;

}

// EOF
