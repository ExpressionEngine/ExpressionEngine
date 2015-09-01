<?php

namespace User\addons\Wiki\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Wiki Revisions Model
 *
 * A model representing a Revision in the Wiki module.
 *
 * @package		ExpressionEngine
 * @subpackage	Wiki Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Revision extends Model {

	protected static $_primary_key = 'page_id';
	protected static $_table_name = 'wiki_page';

	protected static $_relationships = array(
		'Page' => array(
			'type' => 'belongsTo'
		),
       'RevisionAuthor' => array(
            'type'     => 'belongsTo',
            'from_key' => 'revision_author',
            'to_key'   => 'member_id',
            'model'    => 'ee:Member',
            'weak'     => TRUE,
            'inverse' => array(
                'name' => 'Revision',
                'type' => 'hasMany'
            )
        )	
	);
	

	protected $revision_id;
	protected $page_id;
	protected $wiki_id;
	protected $revision_date;
	protected $revision_author;
	protected $revision_notes;
	protected $revision_status;
	protected $page_content;
}
