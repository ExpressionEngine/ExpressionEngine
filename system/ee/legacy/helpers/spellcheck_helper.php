<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Spellcheck Helper
 */

/**
 * Build Spellcheck
 *
 * Creates the proper html output for spellcheck results
 *
 * @access	public
 * @param	string	custom field id
 * @return	string
 */
function build_spellcheck($field_id)
{
	$r = '<div class="spellcheck_content clear_left js_hide" id="spellcheck_holder_'.$field_id.'">'."\n";
	$r .= "\t".'<p><a href="#" class="save_spellcheck">'.ee()->lang->line('save_spellcheck').'</a> | <a href="#" class="revert_spellcheck">'.ee()->lang->line('revert_spellcheck')."</a></p>\n";
	$r .= "\t".'<span id="spellcheck_hidden_'.$field_id.'"></span>'."\n";
	$r .= "\t".'<iframe class="spellcheck_frame" name="spellcheck_frame_'.$field_id.'" id="spellcheck_frame_'.$field_id.'" src="'.BASE.AMP.'C=content_publish'.AMP.'M=spellcheck_actions'.AMP.'action=iframe"></iframe>'."\n";
	$r .= "</div>";

	return $r;
}

// EOF
