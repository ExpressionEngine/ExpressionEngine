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
 * ExpressionEngine Wiki Model
 *
 * A model representing a Wiki in the Wiki module.
 *
 * @package		ExpressionEngine
 * @subpackage	Wiki Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Wiki extends Model {

	protected static $_primary_key = 'wiki_id';
	protected static $_table_name = 'wikis';

	protected static $_typed_columns = array(
		'wiki_moderation_emails'     => 'commaDelimited',
		'wiki_admins'     => 'pipeDelimited',
		'wiki_users'     => 'pipeDelimited'
	);

	protected static $_validation_rules = array(
		'wiki_label_name'	     => 'required|unique',
		'wiki_short_name'       => 'required|validateShortName|unique',
		'wiki_moderation_emails'   => 'validateEmails',
		'wiki_html_format' => 'required',
		'wiki_text_format' => 'required',
		'wiki_revision_limit' => 'is_natural_no_zero|required',
		'wiki_author_limit' => 'is_natural_no_zero|required'
	);



	protected static $_relationships = array(
		'WikiNamespaces' => array(
			'type' => 'hasMany',
			'model' => 'WikiNamespace'
		),
		'Categories' => array(
			'type'  => 'hasMany',
			'model' => 'Category'
		),
		'Pages' => array(
			'type'  => 'hasMany',
			'model' => 'Page'
		),
		'Uploads' => array(
			'type'  => 'hasMany',
			'model' => 'Upload'
		)
	);



	
	protected static $_events = array(
		'afterInsert'
	);	

	protected $wiki_id;
	protected $wiki_label_name;
	protected $wiki_short_name;
	protected $wiki_text_format;
	protected $wiki_html_format;
	protected $wiki_upload_dir;
	protected $wiki_admins;
	protected $wiki_users;
	protected $wiki_revision_limit;
	protected $wiki_author_limit;
	protected $wiki_moderation_emails;



	/**
	 * Ensures fields with multiple emails contain valid emails
	 */
	public function validateEmails($key, $value, $params, $rule)
	{
		foreach($value as $email)
		{
			if (trim($email) != '' && (bool) filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE)
			{
				return 'valid_emails';
			}
		}

		return TRUE;
	}
	
	public function validateShortName($key, $value, $params, $rule)
	{
		if (preg_match('/[^a-z0-9\-\_]/i', $value))
		{
			return 'invalid_short_name';
		}

		return TRUE;
	}
	
	public function onAfterInsert()
	{
			$data = array(
				'wiki_id'        => $this->wiki_id,
				'page_name'      => 'index',
				'page_namespace' => '',
				'last_updated'   => ee()->localize->now
			);

        $this->getFrontend()->make('wiki:Page', $data)->save();
	}


/*	
		//  Default Index Page
		$this->lang->loadfile('wiki');

		$data = array(	'wiki_id'		=> $wiki_id,
						'page_name'		=> 'index',
						'page_namespace'	=> '',
						'last_updated'	=> $this->localize->now);

		$this->db->insert('wiki_page', $data);
		$page_id = $this->db->insert_id();

		$data = array(	'page_id'			=> $page_id,
						'wiki_id'			=> $wiki_id,
						'revision_date'		=> $this->localize->now,
						'revision_author'	=> $this->session->userdata('member_id'),
						'revision_notes'	=> $this->lang->line('default_index_note'),
						'page_content'		=> $this->lang->line('default_index_content')
					 );

		$this->db->insert('wiki_revisions', $data);
		$last_revision_id = $this->db->insert_id();

		$this->db->where('page_id', $page_id);
		$this->db->update('wiki_page', array('last_revision_id' => $last_revision_id));
	
*/	

}
