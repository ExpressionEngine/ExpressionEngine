<?php

namespace EllisLab\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamTraining extends Model {

	protected static $_table_name = 'spam_training';
	protected static $_primary_key = 'training_id';

	protected static $_typed_columns = array(
		'class' => 'boolString'
	);

	protected static $_relationships = array(
		'Kernel' => array(
			'type' => 'belongsTo',
			'model' => 'SpamKernel',
			'to_key' => 'kernel_id'
		),
		'Author' => array(
			'type'     => 'BelongsTo',
			'model'    => 'ee:Member',
			'from_key' => 'author'
		)
	);


	protected $training_id;
	protected $kernel_id;
	protected $author;
	protected $source;
	protected $type;
	protected $class;

}
