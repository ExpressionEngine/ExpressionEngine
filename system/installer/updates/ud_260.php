<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.6
 * @filesource
 */

define('LD', '{');
define('RD', '}');
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://expressionengine.com
 */
class Updater {
	
	public $version_suffix = '';

	private $related_data = array();
	private $reverse_related_data = array();
	private $related_id;
	private $related_markers = array();
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'_add_template_name_to_dev_log',
				'_drop_dst',
				'_update_timezone_column_lengths',
				'_update_session_table',
				'_update_actions_table',
				'_update_specialty_templates',
				'_update_relationship_fieldtype',
				'_update_relationship_table',
				'_update_relationship_tags'
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * update Session table
	 *
	 * @return TRUE
	 */
	private function _add_template_name_to_dev_log()
	{
		ee()->smartforge->add_column(
			'developer_log',
			array(
				'template_id' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				),
				'template_name' => array(
					'type'			=> 'varchar',
					'constraint'	=> 100
				),
				'template_group' => array(
					'type'			=> 'varchar',
					'constraint'	=> 100
				),
				'addon_module' => array(
					'type'			=> 'varchar',
					'constraint'	=> 100
				),
				'addon_method' => array(
					'type'			=> 'varchar',
					'constraint'	=> 100
				),
				'snippets' => array(
					'type'			=> 'text'
				)
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Drop DST columns!
	 */
	private function _drop_dst()
	{
			ee()->smartforge->drop_column('members', 'daylight_savings');

			ee()->smartforge->drop_column('channel_titles', 'dst_enabled');

			ee()->smartforge->drop_column('channel_entries_autosave', 'dst_enabled');
	}

	// --------------------------------------------------------------------

	/**
	 * We need to store PHP timezone identifiers in the database instead of
	 * our shorthand names so that PHP can properly localize dates, so we
	 * need to increase the column lengths anywhere timezones are stored
	 */
	private function _update_timezone_column_lengths()
	{
		ee()->smartforge->modify_column(
			'members',
			array(
				'timezone' => array(
					'name' 			=> 'timezone',
					'type' 			=> 'varchar',
					'constraint' 	=> 50
				)
			)
		);

		// Get all date fields, we'll need to update their timezone column
		// lengths in the channel_data table
		$date_fields = ee()->db
			->select('field_id')
			->get_where(
				'channel_fields',
				array('field_type' => 'date')
			)->result_array();

		foreach ($date_fields as $field)
		{
			$field_name = 'field_dt_'.$field['field_id'];

			ee()->smartforge->modify_column(
				'channel_data',
				array(
					$field_name => array(
						'name' 			=> $field_name,
						'type' 			=> 'varchar',
						'constraint' 	=> 50
					)
				)
			);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update Session table
	 * 
	 * We duplicate this from the 2.5.4 update because the changes weren't
	 * made to the schema file and therefore aren't present for new installs
	 * of 2.5.4 or 2.5.5
	 */
	private function _update_session_table()
	{
		ee()->smartforge->add_column(
			'sessions',
			array(
				'fingerprint' => array(
					'type'			=> 'varchar',
					'constraint'	=> 40
				),
				'sess_start' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				)
			),
			'user_agent'
		);
		
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update the Actions Table
	 *
	 * Required for the changes to the reset password flow.  Removed
	 * one old action and added two new ones.
	 */
	private function _update_actions_table()
	{
		// Update two old actions that we no longer need to be actions
		// with the names of the new methods.

		// For this one, the method was renamed.  It still mostly does
		// the same thing and needs to be an action.
		ee()->db->where('method', 'retrieve_password')
			->update('actions', array('method'=>'send_reset_token'));
		// For this one the method still exists, but is now a form.  It needs
		// to be renamed to the new processing method.
		ee()->db->where('method', 'reset_password')
			->update('actions', array('method'=>'process_reset_password'));

	} 

	// -------------------------------------------------------------------

	/**
	 * Update Specialty Templates
	 *
	 * Required for the changes to the reset password flow.  We needed to 
	 * slightly change the language of the related e-mail template to fit
	 * the new flow.
	 */
	private function _update_specialty_templates()
	{
		$data = array(
			'template_data'=>'{name},

To reset your password, please go to the following page:

{reset_url}

If you do not wish to reset your password, ignore this message. It will expire in 24 hours.

{site_name}
{site_url}');	

		ee()->db->where('template_name', 'forgot_password_instructions')
			->update('specialty_templates', $data);
	}

	// -------------------------------------------------------------------

	private function _update_relationship_fieldtype()
	{
		// UPDATE TABLE `exp_fieldtypes` SET name='relationships' WHERE name='rel';
		ee()->db->where('name', 'rel');
		ee()->db->update('fieldtypes', array('name'=>'relationship'));

		// UPDATE TABLE `exp_channel_fields` set field_type='relationships' where field_type='rel';
		ee()->db->where('field_type', 'rel');
		ee()->db->update('channel_fields', array('field_type'=>'relationship'));
	
		ee()->db->where('field_type', 'relationship');	
		$channel_fields = ee()->db->get('channel_fields');
		foreach ($channel_fields->result_array() as $channel_field)
		{
			$settings = array(
				'channels'		=> array($channel_field['field_related_id']),
				'expired'		=> 0,
				'future'		=> 0,
				'categories'	=> array(),
				'authors'		=> array(),
				'statuses'		=> array(),
				'limit'			=> $channel_field['field_related_max'],
				'order_field'	=> $channel_field['field_related_orderby'],
				'order_dir'		=> $channel_field['field_related_sort'],
				'allow_multiple'	=> 0
			);
			
			ee()->db->where('field_id', $channel_field['field_id']);
			ee()->db->update(
				'channel_fields', 
				array('field_settings'=>base64_encode(serialize($settings)))); 
					
		} 

	}

	/**
 	 *
	 */
	private function _update_relationship_table()
	{
 
		// ALTER TABLE `exp_relationships` CHANGE COLUMN `rel_id` `relationship_id` int(10) unsigned NOT NULL DEFAULT 0;
		ee()->smartforge->modify_column(
			'relationships',
			array(
				'rel_id' => array(
					'name'			=> 'relationship_id',
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'auto_increment'=> TRUE
				)
			)
		);

		// ALTER TABLE `exp_relationships` CHANGE COLUMN `rel_parent_id` `parent_id` int(10) unsigned NOT NULL DEFAULT 0;
		ee()->smartforge->modify_column(
			'relationships',
			array(
				'rel_parent_id' => array(
					'name'			=> 'parent_id',
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
				)
			)
		);

		// ALTER TABLE `exp_relationships` CHANGE COLUMN `rel_child_id` `child_id` int(10) unsigned NOT NULL DEFAULT 0; 	
		ee()->smartforge->modify_column(
			'relationships',
			array(
				'rel_child_id' => array(
					'name'			=> 'child_id',
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
				)
			)
		);

		// ALTER TABLE `exp_relationships` DROP COLUMN `rel_type`;
		ee()->smartforge->drop_column('relationships', 'rel_type');		

		// ALTER TABLE `exp_relationships` DROP COLUMN `rel_data`;
		ee()->smartforge->drop_column('relationships', 'rel_data');
		
		// ALTER TABLE `exp_relationships` DROP COLUMN `reverse_rel_data`;
		ee()->smartforge->drop_column('relationships', 'reverse_rel_data');

		// FIXME No way to do this with DB_forge at the moment it seems.
		// alter table exp_relationships ADD KEY `field_id` (`field_id`);
		// ee()->smartforge->add_key('field_id', FALSE);

		// ALTER TABLE `exp_relationships` ADD COLUMN field_id int unsigned;
		// ALTER TABLE exp_relationships ADD COLUMN `order` int unsigned;
		ee()->smartforge->add_column(
			'relationships',
			array(
				'field_id' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE
				),
				'order' => array(
					'type' => 'int',
					'constraint' => 10,
					'unsigned' => TRUE
				)
			),
			'child_id'
		);

		ee()->db->where('field_type', 'relationship');
		$fields = ee()->db->get('channel_fields');
		$data = ee()->db->get('channel_data');

		foreach ($data->result_array() as $entry)
		{
			foreach ($fields->result_array() as $field)
			{
				if (empty($entry['field_id_' . $field['field_id']]))
				{
					continue;
				}
				ee()->db->where('relationship_id', $entry['field_id_' . $field['field_id']]);
				ee()->db->update('relationships', array('field_id' => $field['field_id']));	
			}
		}
	
		// Wipe out the old, unsed relationship data.
		ee()->smartforge->drop_column(
			array(
				'field_related_to', 'field_related_id', 'field_related_max',
				'field_related_orderby', 'field_related_sort'));
	}

	// -------------------------------------------------------------------

	/**
	 *
	 */
	private function _update_relationship_tags()
	{
		// We're gonna need this to be already loaded.
		require_once(APPPATH . 'libraries/Functions.php');	
		ee()->functions = new Installer_Functions();

		require_once(APPPATH . 'libraries/Extensions.php');
		ee()->extensions = new Installer_Extensions();

		$installer_config = ee()->config;
		ee()->config = new MSM_Config();

		// We need to figure out which template to load.
		// Need to check the edit date.
		ee()->load->model('template_model');
		$templates = ee()->template_model->fetch_last_edit(array(), TRUE); 
	
		// related_entries
		// Foreach template
		foreach($templates as $template)
		{
			// Find the {related_entries} and {reverse_related_entries} tags 
			// (match pairs and wrapped tags)
			$this->_replace_related_entries_tags($template);

			// save the template
			// if saving to file, save the file
			if ($template->loaded_from_file)
			{
				ee()->template_model->save_to_file($template);
			}
			else
			{
				ee()->template_model->save_to_database($template);
			}
		}

		ee()->config = $installer_config;
		
		return true;
	}

	/**
	 * Find all {related_entries} tags in the passed Template
	 *
	 * Takes a passed Template_Entity and searches the template for
	 * instances of {related_entries} and {reverse_related_entries}.
	 * It then replaces them with the proper child tag or parents tag
	 * respectively.  It does the replace in the entity object, allowing
	 * the template to be saved by simply saving the entity.
	 *
	 * @param Template_Entity	The template you wish to find tags in.
	 *
	 * @return void
	 */
	private function _replace_related_entries_tags(Template_Entity $template)
	{
		$template->template_data = $this->_assign_relationship_data($template->template_data);

		// First deal with {related_entries} tags.  Since these are
		// just a single entry relationship, we can replace the child
		// variables with the single entry short-cut
		//
		// NOTE If we don't use a tag pair, we have no where for parameters
		// to go.  Maybe check for parameters and make the decision to 
		// use tag pair vs single entry then?
		foreach ($this->related_data as $marker=>$relationship_tag)
		{
			$tagdata = $relationship_tag['tagdata'];
			foreach ($relationship_tag['var_single'] as $variable)
			{
				$new_var = '{' . $relationship_tag['field_name'] . ':' . $variable . '}';
				$tagdata = str_replace('{' . $variable . '}', $new_var, $tagdata);
			}
		
			$tagdata = '{' . $relationship_tag['field_name'] . '}' . $tagdata . '{/' . $relationship_tag['field_name'] . '}';
			$target = '{REL[' . $relationship_tag['field_name'] . ']' . $marker . 'REL}';
			$template->template_data = str_replace($target, $tagdata, $template->template_data);
		}	

		// Now deal with {reverse_related_entries}, just replace each
		// tag pair with a {parents} tag pair and put the parameters from
		// the original tag onto the {parents} tag.
		foreach ($this->reverse_related_data as $marker=>$relationship_tag)
		{
			$tagdata = $relationship_tag['tagdata'];
			foreach($relationship_tag['var_single'] as $variable)
			{
				$new_var = '{parents:' . $variable . '}';
				$tagdata = str_replace('{' . $variable . '}', $new_var, $tagdata);
			}

			$parentTag = 'parents ';
			foreach ($relationship_tag['params'] as $param=>$value)
			{
				$parentTag .= $param . '="' . $value .'" ';
			}

			$tagdata = '{' . $parentTag . '}' . $tagdata . '{/parents}';

			$target = '{REV_REL[' . $marker . ']' . 'REV_REL}';
			$template->template_data = str_replace($target, $tagdata, $template->template_data);
		}
	}

	/**
	 * Process Tags
	 *
	 * Channel entries can have related entries embedded within them.
	 * We'll extract the related tag data, stash it away in an array, and
	 * replace it with a marker string so that the template parser
	 * doesn't see it.  In the channel class we'll check to see if the 
	 * ee()->TMPL->related_data array contains anything.  If so, we'll celebrate
	 * wildly.
	 *
	 * @param	string
	 * @return	string
	 */	
	private function _assign_relationship_data($chunk)
	{
		$this->related_markers = array();
		
		if (preg_match_all("/".LD."related_entries\s+id\s*=\s*[\"\'](.+?)[\"\']".RD."(.+?)".LD.'\/'."related_entries".RD."/is", $chunk, $matches))
		{  		
			$no_rel_content = '';
			
			for ($j = 0; $j < count($matches[0]); $j++)
			{
				$rand = ee()->functions->random('alnum', 8);
				$marker = LD.'REL['.$matches[1][$j].']'.$rand.'REL'.RD;
				
				if (preg_match("/".LD."if no_related_entries".RD."(.*?)".LD.'\/'."if".RD."/s", $matches[2][$j], $no_rel_match)) 
				{
					// Match the entirety of the conditional
					
					if (stristr($no_rel_match[1], LD.'if'))
					{
						$match[0] = ee()->functions->full_tag($no_rel_match[0], $matches[2][$j], LD.'if', LD.'\/'."if".RD);
					}
					
					$no_rel_content = substr($no_rel_match[0], strlen(LD."if no_related_entries".RD), -strlen(LD.'/'."if".RD));
				}
				
				$this->related_markers[] = $matches[1][$j];
				$vars = ee()->functions->assign_variables($matches[2][$j]);
				$this->related_id = $matches[1][$j];
				$this->related_data[$rand] = array(
											'marker'			=> $rand,
											'field_name'		=> $matches[1][$j],
											'tagdata'			=> $matches[2][$j],
											'var_single'		=> $vars['var_single'],
											'var_pair' 			=> $vars['var_pair'],
											'var_cond'			=> ee()->functions->assign_conditional_variables($matches[2][$j], '\/', LD, RD),
											'no_rel_content'	=> $no_rel_content
										);
										
				$chunk = str_replace($matches[0][$j], $marker, $chunk);					
			}
		}

		if (preg_match_all("/".LD."reverse_related_entries\s*(.*?)".RD."(.+?)".LD.'\/'."reverse_related_entries".RD."/is", $chunk, $matches))
		{  		
			for ($j = 0; $j < count($matches[0]); $j++)
			{
				$rand = ee()->functions->random('alnum', 8);
				$marker = LD.'REV_REL['.$rand.']REV_REL'.RD;
				$vars = ee()->functions->assign_variables($matches[2][$j]);
				
				$no_rev_content = '';

				if (preg_match("/".LD."if no_reverse_related_entries".RD."(.*?)".LD.'\/'."if".RD."/s", $matches[2][$j], $no_rev_match)) 
				{
					// Match the entirety of the conditional
					
					if (stristr($no_rev_match[1], LD.'if'))
					{
						$match[0] = ee()->functions->full_tag($no_rev_match[0], $matches[2][$j], LD.'if', LD.'\/'."if".RD);
					}
					
					$no_rev_content = substr($no_rev_match[0], strlen(LD."if no_reverse_related_entries".RD), -strlen(LD.'/'."if".RD));
				}
				
				$this->reverse_related_data[$rand] = array(
															'marker'			=> $rand,
															'tagdata'			=> $matches[2][$j],
															'var_single'		=> $vars['var_single'],
															'var_pair' 			=> $vars['var_pair'],
															'var_cond'			=> ee()->functions->assign_conditional_variables($matches[2][$j], '\/', LD, RD),
															'params'			=> ee()->functions->assign_parameters($matches[1][$j]),
															'no_rev_content'	=> $no_rev_content
														);
										
				$chunk = str_replace($matches[0][$j], $marker, $chunk);					
			}
		}
	
		return $chunk;
	}

	// --------------------------------------------------------------------------

}	
/* END CLASS */

/* End of file ud_260.php */
/* Location: ./system/expressionengine/installer/updates/ud_260.php */
