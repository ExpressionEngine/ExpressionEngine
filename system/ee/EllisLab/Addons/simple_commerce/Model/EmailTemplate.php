<?php

namespace EllisLab\Addons\SimpleCommerce\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * ExpressionEngine Simple Commerce Email Template Model
 *
 * @package		ExpressionEngine
 * @subpackage	Moblog Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EmailTemplate extends Model {

	protected static $_primary_key = 'email_id';
	protected static $_table_name = 'simple_commerce_emails';

	protected static $_validation_rules = array(
		'email_name'    => 'required',
		'email_subject' => 'required',
		'email_body'    => 'required',
	);

	protected $email_id;
	protected $email_name;
	protected $email_subject;
	protected $email_body;
}

// EOF
