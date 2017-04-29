<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * SpamKernel Model
 */
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
