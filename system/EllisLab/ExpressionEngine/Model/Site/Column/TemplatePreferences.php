<?php

namespace EllisLab\ExpressionEngine\Model\Site\Column;

use EllisLab\ExpressionEngine\Service\Model\Column\Base64SerializedComposite;

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
 * ExpressionEngine Template Preferences
 *
 * @package		ExpressionEngine
 * @subpackage	Site\Preferences
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class TemplatePreferences extends Base64SerializedComposite {

	protected $enable_template_routes;
	protected $strict_urls;
	protected $site_404;
	protected $save_tmpl_revisions;
	protected $max_tmpl_revisions;
	protected $save_tmpl_files;
	protected $tmpl_file_basepath;

}
