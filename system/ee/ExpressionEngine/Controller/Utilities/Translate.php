<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

use ZipArchive;
use ExpressionEngine\Library\CP\Table;

/**
 * Translate Manager Controller
 */
class Translate extends Utilities
{
    protected $languages_dir;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('access_translate')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $this->languages_dir = SYSPATH . 'user/language/';

        if (! is_really_writable($this->languages_dir)) {
            $not_writeable = lang('translation_dir_unwritable');
        }
    }

    /**
     * Magic method that sets the language and routes the action
     */
    public function __call($name, $arguments)
    {
        $name = strtolower($name);

        if (! array_key_exists($name, ee()->lang->language_pack_names())) {
            show_404();
        }

        if (empty($arguments)) {
            $this->listFiles($name);
        } elseif (strtolower($arguments[0]) == 'edit' && isset($arguments[1])) {
            $this->edit($name, $arguments[1]);
        } elseif (strtolower($arguments[0]) == 'save' && isset($arguments[1])) {
            $this->save($name, $arguments[1]);
        } else {
            show_404();
        }
    }

    public function index()
    {
        ee()->lang->load('settings');
        $default_language = ee()->config->item('deft_lang') ?: 'english';

        $vars = [];
        $data = [];

        foreach (ee()->lang->language_pack_names() as $key => $value) {
            $language_title = $value;

            if ($key == $default_language) {
                $language_title .= ' (' . lang('default') . ')';
            }

            $edit_url = ee('CP/URL')->make('utilities/translate/' . $key);

            $data[] = [
                'attrs' => [],
                'columns' => array(
                    'filename' => array(
                        'content' => $language_title,
                        'href' => $edit_url
                    )
                )
            ];
        }

        $base_url = ee('CP/URL')->make('utilities/translate/');

        $table = ee('CP/Table', ['autosort' => true, 'autosearch' => true]);
        $table->setColumns(['language']);

        $table->setNoResultsText('no_search_results');
        $table->setData($data);
        $vars['table'] = $table->viewData($base_url);

        if (!empty($vars['table']['data'])) {
            // Paginate!
            $vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
                ->perPage($vars['table']['limit'])
                ->currentPage($vars['table']['page'])
                ->render($base_url);
        }

        // Set search results heading
        if (!empty($vars['table']['search'])) {
            ee()->view->cp_heading = sprintf(
                lang('search_results_heading'),
                $vars['table']['total_rows'],
                htmlspecialchars($vars['table']['search'], ENT_QUOTES, 'UTF-8')
            );
        }

        ee()->view->cp_page_title = lang('cp_translations');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('cp_translations')
        );

        ee()->cp->render('utilities/translate/languages', $vars);
    }

    /**
     * List the "*_lang.php" files in a $language directory
     *
     * @param string $language	The language directory (i.e. 'english')
     */
    private function listFiles($language)
    {
        if (ee()->input->get_post('bulk_action') == 'export') {
            $this->export($language, ee()->input->get_post('selection'));
        }

        ee()->view->cp_page_title = ucfirst($language) . ' ' . lang('language_files');

        $vars = array(
            'language' => $language,
            'pagination' => ''
        );

        $base_url = ee('CP/URL')->make('utilities/translate/' . $language);

        $data = array();

        ee()->load->helper('file');

        $path = $this->getLanguageDirectory($language);

        $filename_end = '_lang.php';
        $filename_end_len = strlen($filename_end);

        $language_files = get_filenames($path) ?: [];
        $english_files = get_filenames(SYSPATH . 'ee/language/english/');

        foreach ($english_files as $file) {
            if ($file == 'email_data.php' or $file == 'stopwords.php') {
                continue;
            }

            if (substr($file, -$filename_end_len) && substr($file, -4) == '.php') {
                $name = str_replace('_lang.php', '', $file);
                $edit_url = ee('CP/URL')->make('utilities/translate/' . $language . '/edit/' . $name);
                $data[] = [
                    'attrs' => [
                        'class' => ! in_array($file, $language_files) ? 'missing' : ''
                    ],
                    'columns' => array(
                        'filename' => array(
                            'content' => $file,
                            'href' => $edit_url
                        ),
                        array('toolbar_items' => array(
                            'edit' => array(
                                'href' => $edit_url,
                                'title' => strtolower(lang('edit'))
                            )
                        )),
                        array(
                            'name' => 'selection[]',
                            'value' => $name
                        )
                    )
                ];
            }
        }

        $table = ee('CP/Table', array('autosort' => true, 'autosearch' => true));
        $table->setColumns(
            array(
                'file_name',
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                ),
                array(
                    'type' => Table::COL_CHECKBOX
                )
            )
        );
        $table->setNoResultsText('no_search_results');
        $table->setData($data);
        $vars['table'] = $table->viewData($base_url);

        $base_url = $vars['table']['base_url'];

        if (! empty($vars['table']['data'])) {
            // Paginate!
            $vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
                ->perPage($vars['table']['limit'])
                ->currentPage($vars['table']['page'])
                ->render($base_url);
        }

        // Set search results heading
        if (! empty($vars['table']['search'])) {
            ee()->view->cp_heading = sprintf(
                lang('search_results_heading'),
                $vars['table']['total_rows'],
                htmlspecialchars($vars['table']['search'], ENT_QUOTES, 'UTF-8')
            );
        }

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('utilities/translate')->compile() => lang('cp_translations'),
            '' => ucfirst($language)
        );

        ee()->cp->render('utilities/translate/list', $vars);
    }

    /**
     * Find the language in the potential language directories
     *
     * @param string $language	The language name (i.e. 'english')
     * @return string The full path to the language directory
     */
    private function getLanguageDirectory($language)
    {
        foreach (array(SYSPATH . 'user/', APPPATH) as $parent_directory) {
            if (is_dir($parent_directory . 'language/' . $language)) {
                return $parent_directory . 'language/' . $language . '/';
            }
        }

        ee('CP/Alert')->makeInline('shared-form')
            ->asIssue()
            ->withTitle(lang('cannot_access'))
            ->addToBody(sprintf(lang('cannot_access_translation_desc'), $language))
            ->now();

        return '';
    }

    /**
     * Zip and send the selected language files
     *
     * @param string $language	The language directory (i.e. 'english')
     * @param array  $files		The list of files to export
     */
    private function export($language, $files)
    {
        if (empty($files)) {
            ee()->view->set_message('issue', lang('no_files_selected'));

            return;
        }

        $path = $this->getLanguageDirectory($language);

        // Confirm the files exist
        foreach ($files as $file) {
            if (! is_readable($path . $file . '_lang.php')) {
                $message = $path . $file . '_lang.php ' . lang('cannot_access') . '.';
                ee()->view->set_message('issue', $message);

                return;
            }
        }

        $tmpfilename = tempnam(sys_get_temp_dir(), '');
        $zip = new ZipArchive();
        if ($zip->open($tmpfilename, ZipArchive::CREATE) !== true) {
            ee()->view->set_message('issue', lang('cannot_create_zip'));

            return;
        }

        foreach ($files as $file) {
            $zip->addFile($path . $file . '_lang.php', $file . '_lang.php');
        }
        $zip->close();

        $data = file_get_contents($tmpfilename);
        unlink($tmpfilename);

        ee()->load->helper('download');
        force_download('ExpressionEngine-language-export-' . $language . '.zip', $data);
        exit;
    }

    private function edit($language, $file)
    {
        $file = ee()->security->sanitize_filename($file);

        $path = $this->getLanguageDirectory($language);
        $filename = $file . '_lang.php';

        if (file_exists($path . $filename) && ! is_readable($path . $filename)) {
            $message = $path . $file . '_lang.php ' . lang('cannot_access') . '.';
            ee()->view->set_message('issue', $message, '', true);
            ee()->functions->redirect(ee('CP/URL')->make('utilities/translate/' . $language));
        }

        ee()->view->cp_page_title = ucfirst($language) . ' ' . $filename . ' ' . ucfirst(lang('translation'));

        $vars['language'] = $language;
        $vars['filename'] = $filename;

        $dest_dir = $this->languages_dir . $language . '/';

        $M = [];
        if (file_exists($path . $filename) && is_readable($path . $filename)) {
            require($path . $filename);

            $M = $lang;

            unset($lang);
        }

        if (file_exists($dest_dir . $filename)) {
            require($dest_dir . $filename);
        } else {
            $lang = $M;
        }

        $english = ee()->lang->load($file, 'english', true);

        ee()->lang->load($file);
        $vars['sections'] = [[]];
        foreach ($english as $key => $val) {
            if ($key != '') {
                $vars['sections'][0][] = [
                    'title' => ee('Format')->make('Text', $val . ' ')->convertToEntities()->compile(),
                    'fields' => [
                        $key => [
                            'type' => (strlen($val) > 100) ? 'textarea' : 'text',
                            'value' => isset($M[$key]) ? stripslashes($M[$key]) : ''
                        ]
                    ]
                ];
            }
        }

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('utilities/translate')->compile() => lang('cp_translations'),
            ee('CP/URL')->make('utilities/translate/' . $language)->compile() => ucfirst($language),
            '' => lang('edit')
        );

        $vars['base_url'] = ee('CP/URL')->make('utilities/translate/' . $language . '/save/' . $file);
        $vars['buttons'] = array(
            array(
                'name' => '',
                'type' => 'submit',
                'value' => 'save',
                'shortcut' => 's',
                'text' => trim(sprintf(lang('translate_btn'), '')),
                'working' => 'btn_saving'
            )
        );

        return ee()->cp->render('settings/form', $vars);
    }

    private function save($language, $file)
    {
        $file = ee()->security->sanitize_filename($file);

        $dest_dir = $this->languages_dir . $language . '/';
        $filename = $file . '_lang.php';
        $dest_loc = $dest_dir . $filename;

        $str = '<?php' . "\n" . '$lang = array(' . "\n\n\n";

        ee()->lang->loadfile($file);

        foreach ($_POST as $key => $val) {
            $val = str_replace('<script', '', $val);
            $val = str_replace('<iframe', '', $val);
            $val = str_replace(array("\\", "'"), array("\\\\", "\'"), $val);

            $key = ee('Security/XSS')->clean($key);
            $val = ee('Security/XSS')->clean($val);

            $str .= '\'' . $key . '\' => ' . "\n" . '\'' . $val . '\'' . ",\n\n";
        }

        $str .= "''=>''\n);\n\n";
        $str .= "// End of File";

        // Make sure any existing file is writeable
        if (file_exists($dest_loc)) {
            @chmod($dest_loc, FILE_WRITE_MODE);

            if (! is_really_writable($dest_loc)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('trans_file_not_writable'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL')->make('utilities/translate/' . $language . '/edit/' . $file));
            }
        }

        $this->load->helper('file');

        if (write_file($dest_loc, $str)) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('translations_saved'))
                ->addToBody(sprintf(lang('file_saved'), $dest_loc))
                ->defer();
        } else {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('invalid_path'))
                ->addToBody($dest_loc)
                ->defer();
        }
        ee()->functions->redirect(ee('CP/URL')->make('utilities/translate/' . $language . '/edit/' . $file));
    }
}
// END CLASS

// EOF
