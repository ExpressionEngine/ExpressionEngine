<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine EE_Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Fieldtype {

	var $EE;
	var $field_id;
	var $field_name;
	var $settings = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @deprecated This is only here to maintain backwards compatibility
	 * for people using parent::EE_Fieldtype() and will be removed in a
	 * later version.  Deprecated as of version 2.6
	 */
	function EE_Fieldtype()
	{
		$this->EE =& get_instance();

		// Log the deprecation.
		ee()->load->library('logger');
		ee()->logger->deprecated('2.6', 'EE_Fieldtype::__construct()');
	}

	// --------------------------------------------------------------------

	function _init($config = array())
	{
		foreach($config as $key => $val)
		{
			$this->$key = $val;
		}
	}

	// --------------------------------------------------------------------

	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		if ($tagdata)
		{
			return $tagdata;
		}

		return $data;
	}

	// --------------------------------------------------------------------

	function pre_process($data)
	{
		return $data;
	}

	// --------------------------------------------------------------------

	function validate($data)
	{
		return TRUE;
	}

	// --------------------------------------------------------------------

	function save_global_settings()
	{
		return array();
	}

	// --------------------------------------------------------------------

	/**
	 * Display Field Settings
	 *
	 * @access	public
	 * @param	array
	 * @return	string
	 */
	function display_settings($data)
	{
		return '';
	}

	// --------------------------------------------------------------------

	/**
	 * Save Field
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function save($data)
	{
		return $data;
	}



	// --------------------------------------------------------------------

	/**
	 * Called after field is saved
	 *
	 * @access	public
	 * @param	string
	 */
	function post_save($data)
	{
		// $this->settings['entry_id'];
		return array();
	}

	// --------------------------------------------------------------------

	/**
	 * Called when entries are deleted
	 *
	 * @access	public
	 * @param	mixed
	 */
	function delete($ids)
	{
		return array();
	}

	// --------------------------------------------------------------------

	/**
	 * Save Settings
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function save_settings($data)
	{
		return array();
	}

	// --------------------------------------------------------------------

	/**
	 * Settings Modify Column
	 *
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	function settings_modify_column($data)
	{
		// Default custom field additions to channel_data
		$fields['field_id_'.$data['field_id']] = array(
			'type' 			=> 'text',
			'null'			=> TRUE
			);

		$fields['field_ft_'.$data['field_id']] = array(
			'type' 			=> 'tinytext',
			'null'			=> TRUE,
			);

		return $fields;
	}

	// --------------------------------------------------------------------

	/**
	 * Grid Settings Modify Column
	 *
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	function grid_settings_modify_column($data)
	{
		$fields['col_id_'.$data['col_id']] = array(
			'type' => 'text',
			'null' => TRUE
		);

		return $fields;
	}

	// --------------------------------------------------------------------

	/**
	 * Save Settings
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function post_save_settings($data)
	{
		return;
	}

	// --------------------------------------------------------------------

	/**
	 * Install
	 *
	 * @access	public
	 * @return	array	global settings
	 */
	function install()
	{
		return array();
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstall
	 *
	 * @access	public
	 * @return	void
	 */
	function uninstall()
	{
		return;
	}

	// --------------------------------------------------------------------

	function display_publish_field($data)
	{
		$vars['glossary_items'] = ee()->load->ee_view('content/_assets/glossary_items', '', TRUE);

		ee()->load->vars($vars);
		return $this->display_field($data);
	}

	// --------------------------------------------------------------------

	function field_formatting_row($data, $prefix = FALSE)
	{
		$edit_format_link = $data['edit_format_link'];
		$prefix = ($prefix) ? $prefix.'_' : '';

		$extra = '';

		if ($data['field_id'] != '')
		{
			$extra .= '<div class="notice update_formatting js_hide">';
			$extra .= '<p>'.lang('fmt_has_changed').'</p><p>';
			$extra .= form_checkbox($prefix.'update_formatting', 'y', FALSE, 'id="'.$prefix.'update_formatting"');
			$extra .= NBS.lang('update_existing_fields', $prefix.'update_formatting');
			$extra .= '</p></div>';
		}

		// Data from Form Validation
		$show_fmt = set_value($prefix.'field_show_fmt', $data['field_show_fmt_y']);
		$show_fmt = ($show_fmt == 'y' OR $show_fmt === TRUE);

		ee()->table->add_row(
			lang('deft_field_formatting', $prefix.'field_fmt'),
			form_dropdown($prefix.'field_fmt', $data['field_fmt_options'], set_value($prefix.'field_fmt', $data['field_fmt']), 'id="'.$prefix.'field_fmt"').
				NBS.$data['edit_format_link'].BR.BR.
				'<strong>'.lang('show_formatting_buttons').'</strong>'.BR.
				form_radio($prefix.'field_show_fmt', 'y', $show_fmt, 'id="'.$prefix.'field_show_fmt_y"').NBS.
				lang('yes', $prefix.'field_show_fmt_y').NBS.NBS.NBS.NBS.NBS.
				form_radio($prefix.'field_show_fmt', 'n', ! $show_fmt, 'id="'.$prefix.'field_show_fmt_n"').NBS.
				lang('no', $prefix.'field_show_fmt_n').
				$extra
		);

		ee()->javascript->output('
		$("#'.$prefix.'field_fmt").change(function() {
			$(this).nextAll(".update_formatting").show();
		});
		');
	}

	// --------------------------------------------------------------------

	function text_direction_row($data, $prefix = FALSE)
	{
		$prefix = ($prefix) ? $prefix.'_' : '';

		// Data from Form Validation
		$ltr_checked = set_value($prefix.'field_text_direction', $data['field_text_direction_ltr']);
		$ltr_checked = ($ltr_checked == 'ltr' OR $ltr_checked === TRUE OR $ltr_checked === '1');

		ee()->table->add_row(
			'<strong>'.lang('text_direction').'</strong>',
			form_radio($prefix.'field_text_direction', 'ltr', $ltr_checked, 'id="'.$prefix.'field_text_direction_ltr"').NBS.
				lang('ltr', $prefix.'field_text_direction_ltr').NBS.NBS.NBS.NBS.NBS.
				form_radio($prefix.'field_text_direction', 'rtl', ! $ltr_checked, 'id="'.$prefix.'field_text_direction_rtl"').NBS.
				lang('rtl', $prefix.'field_text_direction_rtl')
		);
	}

	// --------------------------------------------------------------------

	function field_content_type_row($data, $prefix = FALSE)
	{
		$suf = $prefix;
		$prefix = ($prefix) ? $prefix.'_' : '';

		$extra = '';

		if ($data['field_id'] != '')
		{
			$extra .= '<div class="notice update_content_type js_hide">';
			$extra .= '<p>'.sprintf(
								lang('content_type_changed'),
								$data['field_content_'.$suf]).'</p></div>';
		}

		ee()->table->add_row(
			lang('field_content_'.$suf, 'field_content_'.$suf),
			form_dropdown($prefix.'field_content_type', $data['field_content_options_'.$suf], set_value($prefix.'field_content_type', $data['field_content_'.$suf]), 'id="'.$prefix.'field_content_type"').$extra
		);

		ee()->javascript->output('
		$("#'.$prefix.'field_content_type").change(function() {
			$(this).nextAll(".update_content_type").show();
		});
		');

	}

	// --------------------------------------------------------------------

	function multi_item_row($data, $prefix = FALSE)
	{
		$prefix = ($prefix) ? $prefix.'_' : '';

		$pre_populate = set_value($prefix.'field_pre_populate', $data['field_pre_populate']);

		ee()->table->add_row(
			'<p class="field_format_option select_format">'.
				form_radio($prefix.'field_pre_populate', 'n', ($pre_populate == 'n'), 'id="'.$prefix.'field_pre_populate_n"').NBS.
				lang('field_populate_manually', $prefix.'field_pre_populate_n').BR.
				form_radio($prefix.'field_pre_populate', 'y', ($pre_populate == 'y'), 'id="'.$prefix.'field_pre_populate_y"').NBS.
				lang('field_populate_from_channel', $prefix.'field_pre_populate_y').
			'</p>',
			'<p class="field_format_option select_format_n">'.
				lang('multi_list_items', $prefix.'field_list_items').BR.
				lang('field_list_instructions').BR.
				form_textarea(array('id'=>$prefix.'field_list_items','name'=>$prefix.'field_list_items', 'rows'=>10, 'cols'=>50, 'value'=>set_value($prefix.'field_list_items', $data['field_list_items']))).
			'</p>
			<p class="field_format_option select_format_y">'.
				lang('select_channel_for_field', $prefix.'field_pre_populate_id').
				form_dropdown($prefix.'field_pre_populate_id', $data['field_pre_populate_id_options'], set_value($prefix.'field_pre_populate_id', $data['field_pre_populate_id_select']), 'id="'.$prefix.'field_pre_populate_id"').
			'</p>'
		);

		ee()->javascript->click('#'.$prefix.'field_pre_populate_n', '$(".select_format_n").show();$(".select_format_y").hide();', FALSE);
		ee()->javascript->click('#'.$prefix.'field_pre_populate_y', '$(".select_format_y").show();$(".select_format_n").hide();', FALSE);

		// When this field becomes active for the first time - hit the option we need
		ee()->javascript->output('
			$("#ft_'.rtrim($prefix, '_').'").one("activate", function() {
				$("#'.$prefix.'field_pre_populate_'.$pre_populate.'").trigger("click");
			});
		');
	}

	// --------------------------------------------------------------------

	function field_show_smileys_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_smileys', 'field_show_smileys', $prefix);
	}

	function field_show_spellcheck_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_spellcheck', 'field_show_spellcheck', $prefix);
	}

	function field_show_glossary_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_glossary', 'field_show_glossary', $prefix);
	}

	function field_show_file_selector_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_file_selector', 'field_show_file_selector', $prefix);
	}

	function field_show_formatting_btns_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_formatting_btns', 'field_show_formatting_btns', $prefix);
	}

	function field_show_writemode_row($data, $prefix = FALSE)
	{
		$this->_yes_no_row($data, 'show_writemode', 'field_show_writemode', $prefix);
	}

	// --------------------------------------------------------------------

	function _yes_no_row($data, $lang, $data_key, $prefix = FALSE, $grid = FALSE)
	{
		$prefix = ($prefix) ? $prefix.'_' : '';

		$data = (isset($data[$data_key])) ? $data[$data_key] : '';

		$val_is_y = set_value($prefix.$data_key, $data);
		$val_is_y = ($val_is_y == 'y' OR $val_is_y === TRUE);

		$yes_no_string = form_radio($prefix.$data_key, 'y', $val_is_y, 'id="'.$prefix.$data_key.'_y"').NBS.
			lang('yes', $prefix.$data_key.'_y').NBS.NBS.NBS.NBS.NBS.
			form_radio($prefix.$data_key, 'n', ( ! $val_is_y), 'id="'.$prefix.$data_key.'_n"').NBS.
			lang('no', $prefix.$data_key.'_n');

		if ($grid)
		{
			return $this->grid_settings_row(lang($lang), $yes_no_string);
		}

		ee()->table->add_row('<strong>'.lang($lang).'</strong>', $yes_no_string);
	}

	// --------------------------------------------------------------------

	/**
	 * Creates a generic settings row in Grid
	 *
	 * @return string
	 */
	public function grid_yes_no_row($label, $name, $data)
	{
		return $this->_yes_no_row($data, $label, $name, FALSE, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Creates a generic settings row in Grid
	 *
	 * @return string
	 */
	public function grid_settings_row($label, $content, $wide = FALSE)
	{
		$label_class = ($wide)
			? 'grid_col_setting_label_small_width' : 'grid_col_setting_label_fixed_width';

		return form_label($label, NULL,
				array('class' => $label_class)
			).$content;
	}

	// --------------------------------------------------------------------

	/**
	 * Creates a dropdown formatted for a Grid columns settings field
	 *
	 * @return string
	 */
	public function grid_dropdown_row($label, $name, $data, $selected = NULL, $multiple = FALSE, $wide = FALSE, $attributes = NULL)
	{
		$classes = '';
		$classes .= ($multiple) ? 'grid_settings_multiselect' : 'select';
		$classes .= ($wide) ? ' grid_select_wide' : '';

		$attributes .= 'class="'.$classes.'"';
		$attributes .= ($multiple) ? ' multiple' : '';

		return $this->grid_settings_row(
			$label,
			form_dropdown(
				$name,
				$data,
				$selected,
				$attributes
			),
			$wide
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Creates a checkbox row in a Grid column settings field
	 *
	 * @return string
	 */
	public function grid_checkbox_row($label, $name, $value, $checked)
	{
		return form_label(
			form_checkbox(
				$name,
				$value,
				$checked
			).$label
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Field formatting row for Grid column settings
	 *
	 * @return string
	 */
	public function grid_field_formatting_row($data)
	{
		return $this->grid_dropdown_row(
			lang('grid_output_format'),
			'field_fmt',
			// TODO: Revisit list of plugin formatting, abstract out
			// existing logic in channel fields API and confirm it's
			// correct, there's a bug report or two about it
			ee()->addons_model->get_plugin_formatting(TRUE),
			(isset($data['field_fmt'])) ? $data['field_fmt'] : 'none'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Text direction row for Grid column settings
	 *
	 * @return string
	 */
	public function grid_text_direction_row($data)
	{
		return $this->grid_dropdown_row(
			lang('grid_text_direction'),
			'field_text_direction',
			array(
				'ltr' => lang('ltr'),
				'rtl' => lang('rtl')
			),
			(isset($data['field_text_direction'])) ? $data['field_text_direction'] : NULL
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Field max length row for Grid column settings
	 *
	 * @return string
	 */
	public function grid_max_length_row($data)
	{
		return form_label(lang('grid_limit_input')).NBS.NBS.NBS.
			form_input(array(
				'name'	=> 'field_maxl',
				'value'	=> (isset($data['field_maxl'])) ? $data['field_maxl'] : 256,
				'class'	=> 'grid_input_text_small'
			)).NBS.NBS.NBS.
			'<i class="instruction_text">'.lang('grid_chars_allowed').'</i>';
	}

	// --------------------------------------------------------------------

	/**
	 * Multiitem row for Grid column settings
	 *
	 * @return string
	 */
	public function grid_multi_item_row($data)
	{
		return form_textarea(array(
				'name'	=> 'field_list_items',
				'rows'	=> 10,
				'cols'	=> 24,
				'value'	=> isset($data['field_list_items']) ? $data['field_list_items'] : '',
				'class'	=> 'right'
			)).
			form_label(lang('multi_list_items')).'<br>'.
			'<i class="instruction_text">'.lang('field_list_instructions').'</i>';
	}

	// --------------------------------------------------------------------

	/**
	 * Max textarea rows for Grid column settings
	 *
	 * @return string
	 */
	public function grid_textarea_max_rows_row($data, $default = 6)
	{
		return form_label(lang('textarea_rows'), NULL,
				array('class' => 'grid_col_setting_label_fixed_width')
			).
			form_input(array(
				'name'	=> 'field_ta_rows',
				'size'	=> 4,
				'value'	=> isset($data['field_ta_rows']) ? $data['field_ta_rows'] : $default,
				'class'	=> 'grid_input_text_small'
			));
	}

	// --------------------------------------------------------------------

	/**
	 * Wraps a field in a DIV with a little extra padding rather than a
	 * Grid cell's default 5px
	 *
	 * @return string
	 */
	public function grid_padding_container($string)
	{
		return '<div class="grid_padding">'.$string.'</div>';
	}

	// --------------------------------------------------------------------

	/**
	 * Wraps a field in a DIV that will ignore default Grid cell padding
	 * settings
	 *
	 * @return string
	 */
	public function grid_full_cell_container($string)
	{
		return '<div class="grid_full_cell_container">'.$string.'</div>';
	}
}
// END EE_Fieldtype class


/* End of file EE_Fieldtype.php */
/* Location: ./system/expressionengine/fieldtypes/EE_Fieldtype.php */
