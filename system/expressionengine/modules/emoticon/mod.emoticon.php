<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Emotion Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Emoticon {

	var $smileys	 = FALSE;
	var $return_data = '';

	/**
	  *  Constructor
	  */
	function Emoticon()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		if (is_file(PATH_MOD.'emoticon/emoticons.php'))
		{
			require PATH_MOD.'emoticon/emoticons.php';

			if (is_array($smileys))
			{
				$this->smileys = $smileys;
			}
		}

		$this->table_layout();
	}

	// --------------------------------------------------------------------

	/**
	  *  Table-based emoticon layout
	  */
	function table_layout()
	{
		if ($this->smileys == FALSE)
		{
			return FALSE;
		}

		if ($this->EE->config->item('enable_emoticons') == 'n')
		{
			return FALSE;
		}


		$path = $this->EE->config->slash_item('emoticon_url');

		$columns  = ( ! $this->EE->TMPL->fetch_param('columns'))  ? '4' : $this->EE->TMPL->fetch_param('columns');

		$tagdata = $this->EE->TMPL->tagdata;

		//  Extract the relevant stuff from the tag

		if ( ! preg_match("/<tr(.*?)<td/si", $tagdata, $match))
		{
			$tr = "<tr>\n";
		}
		else
		{
			$tr = '<tr'.$match['1'];
		}

		if ( ! preg_match("/<td(.*?)<".'\/'."tr>/si", $tagdata, $match))
		{
			$td = "<td>";
		}
		else
		{
			$td = '<td'.$match['1'];
		}


		$i = 1;

		$dups = array();

		foreach ($this->smileys as $key => $val)
		{
			if ($i == 1)
			{
				$this->return_data .= $tr;
			}

			if (in_array($this->smileys[$key]['0'], $dups))
				continue;

			$link = "<a href=\"javascript:void(0);\" onclick=\"add_smiley('".$key."')\"><img src=\"".$path.$this->smileys[$key]['0']."\" width=\"".$this->smileys[$key]['1']."\" height=\"".$this->smileys[$key]['2']."\" alt=\"".$this->smileys[$key]['3']."\" style=\"border:0;\" /></a>";

			$dups[] = $this->smileys[$key]['0'];


			$cell = $td;

			$this->return_data .= str_replace("{smiley}", $link, $cell);

			if ($i == $columns)
			{
				$this->return_data .= "</tr>\n";

				$i = 1;
			}
			else
			{
				$i++;
			}
		}

		$this->return_data = rtrim($this->return_data);

		if (substr($this->return_data, -5) != "</tr>")
		{
			$this->return_data .= "</tr>";
		}
	}
}
// END CLASS

/* End of file mod.emoticon.php */
/* Location: ./system/expressionengine/modules/emoticon/mod.emoticon.php */