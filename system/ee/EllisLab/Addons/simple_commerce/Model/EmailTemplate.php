<?php

namespace EllisLab\Addons\SimpleCommerce\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

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
