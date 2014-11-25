<?php
namespace EllisLab\ExpressionEngine\Model\Template\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Template Table
 *
 * @package		ExpressionEngine
 * @subpackage	Template\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class TemplateGateway extends Gateway {

	protected static $_table_name  = 'templates';
	protected static $_primary_key = 'template_id';

	protected $template_id;
	protected $site_id;
	protected $group_id;
	protected $template_name;
	protected $save_template_file;
	protected $template_type;
	protected $template_data;
	protected $template_notes;
	protected $edit_date;
	protected $last_author_id;
	protected $cache;
	protected $refresh;
	protected $no_auth_bounce;
	protected $enable_http_auth;
	protected $allow_php;
	protected $php_parse_location;
	protected $hits;

}
