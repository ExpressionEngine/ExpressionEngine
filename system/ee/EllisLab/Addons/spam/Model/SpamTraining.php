<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * SpamTraining Model
 */
class SpamTraining extends Model {

	protected static $_table_name = 'spam_training';
	protected static $_primary_key = 'training_id';

	protected static $_relationships = array(
		'Kernel' => array(
			'type' => 'belongsTo',
			'model' => 'SpamKernel',
			'to_key' => 'kernel_id'
		),
		'Author' => array(
			'type'     => 'belongsTo',
			'model'    => 'ee:Member',
			'from_key' => 'author',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'training_id',
				'type' => 'hasMany'
			)
		)
	);

	protected $training_id;
	protected $kernel_id;
	protected $author;
	protected $source;
	protected $type;
	protected $class;

}

// EOF
