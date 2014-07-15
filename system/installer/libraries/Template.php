<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

if ( ! defined('LD')) define('LD', '{');
if ( ! defined('RD')) define('RD', '}');

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Installer Template Handling Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Installer_Template {

	private $related_data = array();
	private $reverse_related_data = array();
	private $related_id;
	private $related_markers = array();

	/**
	 * Build the installer's Template library and load all the
	 * libraries it will need.
	 */
	public function __construct()
	{
		// We're gonna need this to be already loaded.
		require_once(APPPATH . 'libraries/Functions.php');
		ee()->functions = new Installer_Functions();

		require_once(APPPATH . 'libraries/Extensions.php');
		ee()->extensions = new Installer_Extensions();

		require_once(APPPATH . 'libraries/Addons.php');
		ee()->addons = new Installer_Addons();
	}

	/**
	 * Find all {related_entries} tags in the passed Template
	 *
	 * Takes a passed Template_Entity and searches the template for instances
	 * of {related_entries} and {reverse_related_entries}.  It then replaces
	 * them with the proper child tag or parents tag respectively.  It does the
	 * replace in the entity object, allowing the template to be saved by
	 * simply saving the entity.
	 *
	 * This is a helper method called by _update_relationship_tags(), not to be
	 * called in do_update().
	 *
	 * @param Template_Entity	The template you wish to find tags in.
	 *
	 * @return void
	 */
	public function replace_related_entries_tags($template_data)
	{

		ee()->db->select('field_id, field_name');
		$query = ee()->db->get('channel_fields');

		$channel_custom_fields = array();
		foreach ($query->result_array() as $field)
		{
			$channel_custom_fields[] = $field['field_name'];
		}


		$channel_single_variables = array(
    		'absolute_count', 'absolute_results', 'aol_im', 'author',
			'author_id', 'avatar_image_height', 'avatar_image_width', 'avatar_url', 'bio',
			'channel', 'channel_id', 'channel_short_name', 'yahoo_im', 'comment_auto_path',
			'comment_entry_id_auto_path', 'comment_total', 'comment_url_title_auto_path',
			'count', 'edit_date', 'email', 'entry_date', 'entry_id', 'entry_id_path',
			'entry_site_id', 'expiration_date', 'forum_topic_id', 'gmt_entry_date',
			'gmt_edit_date', 'icq', 'interests', 'ip_address', 'location',
			'member_search_path', 'msn_im', 'occupation', 'page_uri', 'page_url',
			'permalink', 'photo_url', 'photo_image_height', 'photo_image_width',
			'profile_path', 'recent_comment_date', 'relative_url', 'relative_date',
			'screen_name', 'signature', 'signature_image_height', 'signature_image_url',
			'signature_image_width', 'status', 'switch', 'title', 'title_permalink',
			'total_results', 'trimmed_url', 'url', 'url_or_email',
			'url_or_email_as_author', 'url_or_email_as_link', 'url_title',
			'url_title_path', 'username', 'week_date'
		);

		$channel_pair_variables = array(
			'date_header', 'date_footer', 'categories'
		);

		$template_data = $this->_assign_relationship_data($template_data);

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
			if (isset($relationship_tag['var_single']))
			{
				foreach ($relationship_tag['var_single'] as $variable)
				{
					// Make sure this is a channel variable, or a custom field variable.  We
					// don't want to replace globals.  That would be silly.
					if( ! in_array($variable, $channel_single_variables) && ! in_array($variable, $channel_custom_fields))
					{
						continue;
					}
					// Just replace the front of the tag.  This way any paramters are left where they are.
					$new_var = '{' . $relationship_tag['field_name'] . ':' . $variable;
					$tagdata = str_replace('{' . $variable, $new_var, $tagdata);
				}
			}

			if (isset($relationship_tag['var_pair']))
			{
				foreach($relationship_tag['var_pair'] as $variable=>$params)
				{
					if( ! in_array($variable, $channel_pair_variables) && ! in_array($variable, $channel_custom_fields))
					{
						continue;
					}
					// Just the front of the tag, leave parameters in place.
					$new_var = $relationship_tag['field_name'] . ':' . $variable;
					$tagdata = str_replace('{' . $variable, '{' . $new_var, $tagdata);
					// For pairs, we have to replace the closing tag as well.
					$tagdata = str_replace('{/' . $variable, '{/' . $new_var, $tagdata);
				}
			}

			// If no_related_entries no longer works.  It's been replaced by prefix:no_results
			$tagdata = str_replace('{if no_related_entries}', '{if ' . $relationship_tag['field_name'] . ':no_results}', $tagdata);

			$tagdata = '{' . $relationship_tag['field_name'] . '}' . $tagdata . '{/' . $relationship_tag['field_name'] . '}';
			$target = '{REL[' . $relationship_tag['field_name'] . ']' . $marker . 'REL}';
			$template_data = str_replace($target, $tagdata, $template_data);
		}

		// Now deal with {reverse_related_entries}, just replace each
		// tag pair with a {parents} tag pair and put the parameters from
		// the original tag onto the {parents} tag.
		foreach ($this->reverse_related_data as $marker=>$relationship_tag)
		{
			$tagdata = $relationship_tag['tagdata'];

			if (isset($relationship_tag['var_single']))
			{
				foreach($relationship_tag['var_single'] as $variable)
				{
					if( ! in_array($variable, $channel_single_variables) && ! in_array($variable, $channel_custom_fields))
					{
						continue;
					}
					$new_var = '{parents:' . $variable;
					$tagdata = str_replace('{' . $variable, $new_var, $tagdata);
				}
			}

			if (isset($relationship_tag['var_pair']))
			{
				foreach($relationship_tag['var_pair'] as $variable=>$params)
				{
					if( ! in_array($variable, $channel_pair_variables) && ! in_array($variable, $channel_custom_fields))
					{
						continue;
					}
					$new_var = 'parents:' . $variable;
					$tagdata = str_replace('{' . $variable, '{' . $new_var, $tagdata);
					$tagdata = str_replace('{/' . $variable, '{/' . $new_var, $tagdata);
				}
			}

			// If no_reverse_related_entries doesn't work anymore.  Replace with no_results.
			$tagdata = str_replace('{if no_reverse_related_entries}', '{if parents:no_results}', $tagdata);

			$parentTag = 'parents ';
			foreach ($relationship_tag['params'] as $param=>$value)
			{
				$parentTag .= $param . '="' . $value .'" ';
			}

			$tagdata = '{' . $parentTag . '}' . $tagdata . '{/parents}';

			$target = '{REV_REL[' . $marker . ']' . 'REV_REL}';
			$template_data = str_replace($target, $tagdata, $template_data);
		}
		return $template_data;
	}

	/**
	 * Process Tags
	 *
	 * Channel entries can have related entries embedded within them.  We'll
	 * extract the related tag data, stash it away in an array, and replace it
	 * with a marker string so that the template parser doesn't see it.
	 *
	 * This is a helper method called by _replace_related_entries_tags(), not
	 * to be called by do_update().
	 *
	 * This method has multiple side effects and makes use of the following
	 * class variables:
	 * 		$related_data, $reverse_related_data,
	 *		$related_id, $related_markers
	 *
	 * @param	string The template chunk to be chekd for relationship tags.
	 *
	 * @return	string The parsed template chunk, with relationship tags removed.
	 */
	private function _assign_relationship_data($chunk)
	{
		$this->related_markers = array();
		$this->related_data = array();
		$this->reverse_related_data = array();
		$this->related_id = NULL;

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


}
// END CLASS

/* End of file Template.php */
/* Location: ./system/expressionengine/installer/libraries/Template.php */
