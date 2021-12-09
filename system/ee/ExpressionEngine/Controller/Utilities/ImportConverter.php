<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

/**
 * Import Converter Controller
 */
class ImportConverter extends Utilities
{
    private $member_file_name = '';
    private $cache = '';

    public function __construct()
    {
        parent::__construct();
        $this->cache = PATH_CACHE . 'import_convert/';
    }

    /**
     * Member import file converter
     */
    public function index()
    {
        if (! ee('Permission')->can('access_import')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! AJAX_REQUEST) {
            if (! ee('Filesystem')->exists($this->cache)) {
                ee('Filesystem')->mkDir($this->cache);
            } else {
                ee('Filesystem')->deleteDir($this->cache, true);
            }
        }

        ee()->lang->loadfile('member_import');

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'member_file',
                    'desc' => sprintf(lang('member_file_desc')),
                    'fields' => array(
                        'member_file' => [
                            'type' => 'file',
                            'required' => true
                        ],
                    )
                ),
                array(
                    'title' => 'delimiting_char',
                    'desc' => 'delimiting_char_desc',
                    'fields' => array(
                        'delimiter' => array(
                            'type' => 'radio',
                            'choices' => array(
                                'comma' => lang('comma_delimit') . ' <i>,</i>',
                                'tab' => lang('tab_delimit'),
                                'pipe' => lang('pipe_delimit') . ' <i>|</i>',
                                'other' => lang('other_delimit')
                            ),
                            'group_toggle' => ['other' => 'other'],
                            'encode' => false,
                            'value' => 'comma'
                        ),
                        'delimiter_special' => [
                            'type' => 'text',
                            'group' => 'other'
                        ]
                    )
                ),
                array(
                    'title' => 'enclosing_char',
                    'desc' => 'enclosing_char_desc',
                    'fields' => array(
                        'enclosure' => array('type' => 'text')
                    )
                )
            )
        );

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules(array(
            array(
                'field' => 'member_file',
                'label' => 'lang:member_file',
                'rules' => 'callback__file_handler'
            ),
            array(
                'field' => 'delimiter',
                'label' => 'lang:delimiting_char',
                'rules' => 'required|enum[tab,other,comma,pipe]'
            ),
            array(
                'field' => 'delimiter_special',
                'label' => 'lang:delimiting_char',
                'rules' => 'trim|callback__not_alphanu'
            ),
            array(
                'field' => 'enclosure',
                'label' => 'lang:enclosing_char',
                'rules' => 'callback__prep_enclosure'
            ),
        ));

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            return $this->import_fieldmap();
        } elseif (ee()->form_validation->errors_exist()) {
            ee()->view->set_message('issue', lang('file_not_converted'), lang('file_not_converted_desc'));
        }

        // Check cache folder is writable, no point in filling the form if not
        if (! ee('Filesystem')->isWritable($this->cache)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->cannotClose()
                ->withTitle(lang('import_cache_file_not_writable'))
                ->addToBody(lang('import_cache_file_instructions'))
                ->now();
        }

        $vars['has_file_input'] = true;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('import_converter');
        ee()->view->base_url = ee('CP/URL')->make('utilities/import-converter');
        ee()->view->save_btn_text = 'import_convert_btn';
        ee()->view->save_btn_text_working = 'import_convert_btn_saving';

        ee()->view->cp_breadcrumbs = array(
            '' => lang('import_converter')
        );

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Not Alpha or Numeric
     *
     * Validation callback that makes sure that no alphanumeric chars are submitted
     *
     * @param	string
     * @return	boolean
     */
    public function _not_alphanu($str = '')
    {
        if (ee()->input->post('delimiter') == 'other') {
            if ($str == '') {
                ee()->form_validation->set_message('_not_alphanu', str_replace('%x', lang('other'), lang('no_delimiter')));

                return false;
            }

            preg_match("/[\w\d]*/", $str, $matches);

            if ($matches[0] != '') {
                ee()->form_validation->set_message('_not_alphanu', lang('alphanumeric_not_allowed'));

                return false;
            }
        }

        return true;
    }

    /**
     * Prep Enclosure
     *
     * Undo changes made by form prep
     *
     * @return	string
     */
    public function _prep_enclosure($enclosure)
    {
        // undo changes made by form prep as we need the literal characters
        // and htmlspecialchars_decode() doesn't exist until PHP 5, so...
        $enclosure = str_replace('&#39;', "'", $enclosure);
        $enclosure = str_replace('&amp;', "&", $enclosure);
        $enclosure = str_replace('&lt;', "<", $enclosure);
        $enclosure = str_replace('&gt;', ">", $enclosure);
        $enclosure = str_replace('&quot;', '"', $enclosure);
        $enclosure = stripslashes($enclosure);

        return $enclosure;
    }

    /**
     * Callback that handles file upload
     *
     *
     * @return	bool
     */
    public function _file_handler()
    {
        if (! ee('Filesystem')->isWritable($this->cache)) {
            ee()->form_validation->set_message('_file_handler', lang('import_cache_file_not_writable'));

            return false;
        }

        // Required field
        if (! isset($_FILES['member_file']['name']) or empty($_FILES['member_file']['name'])) {
            ee()->form_validation->set_message('_file_handler', lang('required'));

            return false;
        }

        // need to error check

        ee()->load->library('upload');
        ee()->upload->initialize(array(
            'allowed_types' => '*',
            'upload_path' => $this->cache,
            'overwrite' => true
        ));

        if (! ee()->upload->do_upload('member_file')) {
            //print_r(ee()->upload->display_errors());
            ee()->form_validation->set_message('_file_handler', lang('upload_problem'));

            return false;
        }

        $data = ee()->upload->data();
        $this->member_file_name = $data['file_name'];

        return true;
    }

    /**
     * For mapping to existing member fields
     */
    public function import_fieldmap()
    {
        if (! ee('Permission')->can('access_utilities')) {
            show_error(lang('unauthorized_access'), 403);
        }

        //  Snag form POST data
        switch (ee()->input->post('delimiter')) {
            case 'tab':	$delimiter = "\t";

                break;
            case 'pipe':	$delimiter = "|";

                break;
            case 'other':	$delimiter = ee()->input->post('delimiter_special');

                break;
            case 'comma':
            default:		$delimiter = ",";
        }

        $enclosure = ee()->input->post('enclosure') ?: '';
        $this->member_file_name = (! empty($this->member_file_name)) ? $this->member_file_name : ee('Encrypt')->decode(ee()->input->post('member_file'));

        //  Read data file into an array
        $fields = $this->_datafile_to_array($this->cache . '/' . $this->member_file_name, $delimiter, $enclosure);

        if (! isset($fields[0]) or count($fields[0]) < 3) {
            // No point going further if there aren't even the minimum required
            ee()->view->set_message('issue', lang('not_enough_fields'), lang('not_enough_fields_desc'), true);
            ee()->functions->redirect(ee('CP/URL')->make('utilities/import_converter'));
        }

        // Get member table fields
        $this->default_fields = ee('Model')->make('Member')->getFields();

        ksort($this->default_fields);
        $vars['select_options'][''] = lang('select');

        foreach ($this->default_fields as $key => $val) {
            $vars['select_options'][$val] = $val;
        }

        // we do not allow <unique_id> or <member_id> in our XML format
        unset($vars['select_options']['unique_id']);
        unset($vars['select_options']['member_id']);

        // When MemberField model is ready
        //$m_fields = ee('Model')->get('MemberField')->order('m_field_name', 'asc')->all();
        $m_fields = ee()->db->order_by('m_field_name', 'asc')->get('member_fields');

        if ($m_fields->num_rows() > 0) {
            foreach ($m_fields->result() as $field) {
                $vars['select_options'][$field->m_field_name] = $field->m_field_name;
            }
        }

        $vars['fields'] = $fields;

        $vars['form_hidden'] = array(
            'member_file' => ee('Encrypt')->encode($this->member_file_name),
            'delimiter' => ee()->input->post('delimiter'),
            'enclosure' => $enclosure,
            'delimiter_special' => $delimiter
        );

        $vars['encrypt'] = '';

        ee()->view->cp_page_title = lang('import_converter') . ' - ' . lang('assign_fields');
        ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/import_converter'), lang('import_converter'));
        ee()->cp->render('utilities/import/fieldmap', $vars);
    }

    /**
     * Datafile to Array
     *
     * Read delimited data file into an array
     *
     * @return	array
     */
    private function _datafile_to_array($file, $delimiter, $enclosure)
    {
        if (! ee('Permission')->can('access_utilities')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $contents = file($file);
        $fields = array();

        //  Parse file into array
        if ($enclosure == '') {
            foreach ($contents as $line) {
                $fields[] = explode($delimiter, $line);
            }
        } else {
            foreach ($contents as $line) {
                preg_match_all("/" . preg_quote($enclosure) . "(.*?)" . preg_quote($enclosure) . "/si", $line, $matches);
                $fields[] = $matches[1];
            }
        }
        return $fields;
    }

    /**
     * Pair Fields Form
     *
     * For mapping to existing custom fields
     *
     * @return	void
     */
    public function importFieldmapConfirm()
    {
        if (! ee('Permission')->can('access_utilities')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $paired = array();

        // Validate selected fields
        foreach ($_POST as $key => $val) {
            if (substr($key, 0, 5) == 'field') {
                $_POST['unique_check'][$key] = $val;
                $paired[$key] = $val;
            }
        }

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules(array(
            array(
                'field' => 'unique_check',
                'label' => 'lang:other',
                'rules' => 'callback__unique_required'
            ),
            array(
                'field' => 'encrypt',
                'label' => 'lang:plain_text_passwords',
                'rules' => 'required'
            )
        ));

        if (ee()->form_validation->run() === false) {
            return $this->import_fieldmap();
        }

        //  Snag form POST data
        switch (ee()->input->post('delimiter')) {
            case 'tab':	$delimiter = "\t";

                break;
            case 'pipe':	$delimiter = "|";

                break;
            case 'other':	$delimiter = ee()->input->post('delimiter_special');

                break;
            case 'comma':
            default:		$delimiter = ",";
        }

        $this->member_file_name = ee('Encrypt')->decode(ee()->input->post('member_file'));
        $enclosure = ee()->input->post('enclosure') ?: '';

        //  Read data file into an array
        $fields = $this->_datafile_to_array($this->cache . '/' . $this->member_file_name, $delimiter, $enclosure);

        $vars['fields'] = $fields;
        $vars['paired'] = $paired;

        $vars['form_hidden'] = array(
            'member_file' => ee()->input->post('member_file'),
            'delimiter' => ee()->input->post('delimiter'),
            'enclosure' => $enclosure,
            'delimiter_special' => $delimiter,
            'encrypt' => ee()->input->post('encrypt')
        );

        $vars['form_hidden'] = array_merge($vars['form_hidden'], $paired);

        ee()->view->cp_page_title = lang('confirm_assignments');
        ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/import_converter'), lang('import_converter'));
        ee()->cp->render('utilities/import/fieldmap-confirm', $vars);
    }

    /**
     * Create XML File
     *
     * Creates and XML file from delimited data
     *
     * @return	mixed
     */
    public function importCodeOutput()
    {
        if (! ee('Permission')->can('access_utilities')) {
            show_error(lang('unauthorized_access'), 403);
        }

        //  Snag form POST data
        switch (ee()->input->post('delimiter')) {
            case 'tab':	$delimiter = "\t";

                break;
            case 'pipe':	$delimiter = "|";

                break;
            case 'other':	$delimiter = ee()->input->post('delimiter_special');

                break;
            case 'comma':
            default:		$delimiter = ",";
        }

        $this->member_file_name = ee('Encrypt')->decode(ee()->input->post('member_file'));
        $enclosure = ee()->input->post('enclosure') ?: '';
        $encrypt = ($this->input->post('encrypt') == 'y');

        ee()->load->helper(array('file', 'xml'));

        //  Read file contents
        $contents = read_file($this->cache . '/' . $this->member_file_name);

        //  Get structure
        $structure = array();

        foreach ($_POST as $key => $val) {
            if (substr($key, 0, 5) == 'field') {
                $structure[] = $val;
            }
        }

        ee()->load->library('xmlparser');

        // parse XML data
        $xml = ee()->xmlparser->parse_xml($contents);

        $params = array(
            'data' => $contents,
            'structure' => $structure,
            'root' => 'members',
            'element' => 'member',
            'delimiter' => $delimiter,
            'enclosure' => $enclosure
        );

        $xml = ee()->xmlparser->delimited_to_xml($params, 1);

        //  Add type="text" parameter for plaintext passwords
        if ($encrypt === true) {
            $xml = str_replace('<password>', '<password type="text">', $xml);
        }

        if (! empty(ee()->xmlparser->errors)) {
            return show_error($this->xmlparser->errors);
        }

        $vars['code'] = $xml;
        $vars['generated'] = ee()->localize->human_time();
        $vars['username'] = ee()->session->userdata('username');

        ee()->view->cp_page_title = lang('xml_code');
        ee()->cp->set_breadcrumb(ee('CP/URL')->make('utilities/import_converter'), lang('import_converter'));
        ee()->cp->render('utilities/import/code-output', $vars);

        //Clear out the cache so that unauthorized cannot access
        ee('Filesystem')->deleteDir($this->cache, true);
    }

    /**
     * Downloads generated XML from import converter
     *
     * @return	void
     */
    public function downloadXml()
    {
        ee()->load->helper('download');
        force_download(
            'member_' . ee()->localize->format_date('%y%m%d') . '.xml',
            ee()->input->post('xml')
        );
        exit;
    }

    /**
     * Unique Required
     *
     * Check for uniqueness and required values
     *
     * @return	void
     */
    public function _unique_required($selected_fields)
    {
        //  Get field pairings
        $paired = array();
        $mssg = array();

        if (is_array($selected_fields)) {
            foreach ($selected_fields as $val) {
                if ($val != '' && in_array($val, $paired)) {
                    $mssg[] = str_replace("%x", $val, lang('duplicate_field_assignment'));
                }

                $paired[] = $val;
            }
        }

        if (! in_array('username', $paired)) {
            $mssg[] = lang('missing_username_field');
        }

        if (! in_array('screen_name', $paired)) {
            $mssg[] = lang('missing_screen_name_field');
        }

        if (! in_array('email', $paired)) {
            $mssg[] = lang('missing_email_field');
        }

        if (count($mssg) > 0) {
            $out = implode('<br>', $mssg);
            $this->form_validation->set_message('_unique_required', $out);

            return false;
        }

        return true;
    }
}
// END CLASS

// EOF
