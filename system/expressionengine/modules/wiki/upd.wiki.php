<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Wiki Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Update File
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Wiki_upd {

	var $version = '2.3';
	
	function Wiki_upd()
	{
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */	
	function install()
	{
		$sql[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Wiki', '$this->version', 'y')";
		
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_wiki_page (
				page_id int(10) unsigned NOT NULL auto_increment,
				wiki_id INT(3) UNSIGNED NOT NULL,
				page_name VARCHAR(100) NOT NULL,
				page_namespace VARCHAR(125) NOT NULL DEFAULT '',
				page_redirect VARCHAR(100) NULL DEFAULT NULL,
				page_locked	CHAR(1) NOT NULL DEFAULT 'n',
				page_moderated CHAR(1) NOT NULL DEFAULT 'n',
				last_updated INT(10) UNSIGNED NOT NULL DEFAULT '0',
				last_revision_id INT(10) NULL DEFAULT NULL,
				has_categories CHAR(1) NOT NULL DEFAULT 'n',
				PRIMARY KEY `page_id` (`page_id`),
				KEY `wiki_id` (`wiki_id`),
				KEY `page_locked` (`page_locked`),
				KEY `page_moderated` (`page_moderated`),
				KEY `has_categories` (`has_categories`)
				) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_wiki_revisions` (
				`revision_id` int(12) unsigned NOT NULL auto_increment,
				`page_id` int(10) unsigned NOT NULL,
				`wiki_id` INT(3) UNSIGNED NOT NULL,
				`revision_date` int(10) unsigned NOT NULL,
				`revision_author` int(8) NOT NULL,
				`revision_notes` text NOT NULL,
				`revision_status` varchar(10) NOT NULL DEFAULT 'open',
				`page_content` mediumtext NOT NULL,
				PRIMARY KEY `revision_id` (`revision_id`),
				KEY `page_id` (`page_id`),
				KEY `wiki_id` (`wiki_id`),
				KEY `revision_author` (`revision_author`)
				) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_wiki_uploads(
				wiki_upload_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				wiki_id INT(3) UNSIGNED NOT NULL,
				file_name VARCHAR(60) NOT NULL,
				file_hash VARCHAR(32) NOT NULL,
				upload_summary TEXT,
				upload_author INT(8) NOT NULL,
				image_width INT(5) UNSIGNED NOT NULL,
				image_height INT(5) UNSIGNED NOT NULL,
				file_type VARCHAR(50) NOT NULL,
				file_size INT(10) UNSIGNED NOT NULL DEFAULT '0',
				upload_date INT(10) UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY `wiki_upload_id` (`wiki_upload_id`),
				KEY `wiki_id` (`wiki_id`)
				) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_wiki_search (
				wiki_search_id VARCHAR(32) NOT NULL,
				search_date int(10) NOT NULL,
				wiki_search_query TEXT,
				wiki_search_keywords VARCHAR(150) NOT NULL,
				PRIMARY KEY `wiki_search_id` (`wiki_search_id`)
				) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
		$sql[] = "CREATE TABLE IF NOT EXISTS exp_wikis(
				wiki_id INT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
				wiki_label_name VARCHAR(100) NOT NULL,
				wiki_short_name VARCHAR(50) NOT NULL,
				wiki_text_format VARCHAR(50) NOT NULL,
				wiki_html_format VARCHAR(10) NOT NULL,
				wiki_upload_dir INT(3) UNSIGNED NOT NULL DEFAULT '0',
				wiki_admins TEXT,
				wiki_users TEXT,
				wiki_revision_limit INT(8) UNSIGNED NOT NULL,
				wiki_author_limit INT(5) UNSIGNED NOT NULL ,
				wiki_moderation_emails TEXT,
				PRIMARY KEY `wiki_id` (`wiki_id`)
				) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_wiki_categories` (
				`cat_id` int(10) unsigned NOT NULL auto_increment,
				`wiki_id` INT(8) UNSIGNED NOT NULL,
				`cat_name` varchar(70) NOT NULL,
				`parent_id` int(10) unsigned NOT NULL,
				`cat_namespace` varchar(125) NOT NULL,
				PRIMARY KEY `cat_id` (`cat_id`),
				KEY `wiki_id` (`wiki_id`)
				) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
  		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_wiki_category_articles` (
				`page_id` INT(10) UNSIGNED NOT NULL,
				`cat_id` INT(10) UNSIGNED NOT NULL,
				PRIMARY KEY `page_id_cat_id` (`page_id`, `cat_id`)
				) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_wiki_namespaces` (
				`namespace_id` int(6) NOT NULL auto_increment,
				`wiki_id` int(10) UNSIGNED NOT NULL,
				`namespace_name` varchar(100) NOT NULL,
				`namespace_label` varchar(150) NOT NULL,
				`namespace_users` TEXT,
				`namespace_admins` TEXT,
				PRIMARY KEY `namespace_id` (`namespace_id`),
				KEY `wiki_id` (`wiki_id`))
				DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
					
		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}
		
		// Add Extension Hook
		$this->EE->db->insert('extensions', array(
			'class'    => 'Wiki_ext',
			'hook'     => 'files_after_delete',
			'method'   => 'files_after_delete',
			'settings' => '',
			'priority' => 5,
			'version'  => $this->version,
			'enabled'  => 'y'
		));
				
		return TRUE;
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$query = $this->EE->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Wiki'"); 
				
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row('module_id') ."'";
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Wiki'";
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Wiki'";
		$sql[] = "DROP TABLE IF EXISTS exp_wiki_page";
		$sql[] = "DROP TABLE IF EXISTS exp_wiki_revisions";
		$sql[] = "DROP TABLE IF EXISTS exp_wikis";
		$sql[] = "DROP TABLE IF EXISTS exp_wiki_uploads";
		$sql[] = "DROP TABLE IF EXISTS exp_wiki_search";
		$sql[] = "DROP TABLE IF EXISTS exp_wiki_categories";
		$sql[] = "DROP TABLE IF EXISTS exp_wiki_category_articles";
		$sql[] = "DROP TABLE IF EXISTS exp_wiki_namespaces";
	
		foreach ($sql as $query)
		{
			$this->EE->db->query($query);
		}
		
		// Disable extension
		$this->EE->db->delete('extensions', array('class' => 'Wiki_ext'));

		return TRUE;
	}



	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */	
	
	function update($current='')
	{
		if (version_compare($current, '1.1', '<'))
    	{
			$this->EE->load->dbforge();
			$this->EE->dbforge->drop_column('wikis', 'wiki_namespaces_list');
			$this->EE->dbforge->add_field(array(
				'namespace_id' => array(
					'type'				=> 'int',
					'constraint'		=> 6,
					'auto_increment'	=> TRUE
				),
				'wiki_id' => array(
					'type'				=> 'int',
					'constraint'		=> 10,
					'unsigned'			=> TRUE
				),
				'namespace_name' => array(
					'type'				=> 'varchar',
					'constraint'		=> 100
				),
				'namespace_label' => array(
					'type'				=> 'varchar',
					'constraint'		=> 150
				),
				'namespace_users' => array(
					'type'				=> 'text',
					'null'				=> TRUE
				),
				'namespace_admins' => array(
					'type'				=> 'text',
					'null'				=> TRUE
				),
			));
			$this->EE->dbforge->add_key('namespace_id', TRUE);
			$this->EE->dbforge->add_key('wiki_id');
			$this->EE->dbforge->create_table('wiki_namespaces');
    	
    		/* -------------------------------
    		/*  The Category NS needs a non-changing short name, so we use 
    		/*  'category'.  Prior to this it was using the Label, so we need
    		/*  to do a conversion for any category articles already in the 
    		/*  exp_wiki_page database table.
    		/* -------------------------------*/
    		
			$this->EE->db->where('page_namespace', lang('category_ns'));
			$this->EE->db->update('wiki_page', array(
				'page_namespace' => 'category'
			));
    	}

		if (version_compare($current, '1.2', '<'))
		{
			$this->EE->load->dbforge();
			
			$this->EE->dbforge->add_column(
				'wiki_page', 
				array(
					'last_revision_id' => array(
						'type'			=> 'int',
						'constraint'	=> 10
					)
				), 
				'last_updated'
			);
			
			$this->EE->db->query("
				UPDATE exp_wiki_page, exp_wiki_revisions
				SET exp_wiki_page.last_revision_id = 
					(
						SELECT MAX(exp_wiki_revisions.revision_id)
						FROM exp_wiki_revisions
						WHERE exp_wiki_revisions.page_id = exp_wiki_page.page_id
					)
				WHERE exp_wiki_page.page_id = exp_wiki_revisions.page_id"
			);
		}
		
		if (version_compare($current, '2.0', '<'))
		{
			$this->EE->db->query("ALTER TABLE `exp_wiki_category_articles` DROP KEY `page_id`");
			$this->EE->db->query("ALTER TABLE `exp_wiki_category_articles` DROP KEY `cat_id`");
			$this->EE->db->query("ALTER TABLE `exp_wiki_category_articles` ADD PRIMARY KEY `page_id_cat_id` (`page_id`, `cat_id`)");

			$this->EE->db->query("ALTER TABLE `exp_wiki_page` CHANGE `page_namespace` `page_namespace` VARCHAR(125) NULL DEFAULT NULL");
			$this->EE->db->query("ALTER TABLE `exp_wiki_page` CHANGE `page_redirect` `page_redirect` VARCHAR(125) NULL DEFAULT NULL");
			$this->EE->db->query("ALTER TABLE `exp_wiki_page` CHANGE `last_revision_id` `last_revision_id` INT(10) NULL DEFAULT NULL");
		}
		
		if (version_compare($current, '2.1', '<'))
		{
			$this->EE->db->query("ALTER TABLE `exp_wiki_page` CHANGE `page_namespace` `page_namespace` VARCHAR(125) NOT NULL DEFAULT ''");
		}
		
		if (version_compare($current, '2.2', '<'))
		{
			$this->EE->db->query("ALTER TABLE `exp_wiki_search` ADD COLUMN search_date int(10) NOT NULL AFTER wiki_search_id");
		}
		
		if (version_compare($current, '2.3', '<'))
		{
			// Add Extension Hook
			$this->EE->db->insert('extensions', array(
				'class'    => 'Wiki_ext',
				'hook'     => 'files_after_delete',
				'method'   => 'files_after_delete',
				'settings' => '',
				'priority' => 5,
				'version'  => $this->version,
				'enabled'  => 'y'
			));
		}
				
		return TRUE;
	}
	
}
/* END Class */

/* End of file upd.wiki.php */
/* Location: ./system/expressionengine/modules/wiki/upd.wiki.php */