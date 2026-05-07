<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
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
     * Create a new translate utility controller instance.
     *
     * @return void
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
     * Route language-specific utility actions.
     *
     * @param string $name
     * @param array<int, mixed> $arguments
     * @return void
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

    /**
     * Display the available Control Panel language packs.
     *
     * @return void
     */
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
     * List translatable language files for the selected language.
     *
     * @param string $language
     * @return void
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
     * Resolve the language directory from supported parent paths.
     *
     * @param string $language
     * @return string
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
     * Export selected language files as a zip download.
     *
     * @param string $language
     * @param array<int, string> $files
     * @return void
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

    /**
     * Render the translation editor for a language file.
     *
     * @param string $language
     * @param string $file
     * @return mixed
     */
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

            if (isset($lang) && is_array($lang)) {
                $M = $lang;
            } else {
                $this->addInvalidTranslationFileAlert($path . $filename);
            }

            unset($lang);
        }

        if (file_exists($dest_dir . $filename)) {
            require($dest_dir . $filename);

            if (! isset($lang) || ! is_array($lang)) {
                $this->addInvalidTranslationFileAlert($dest_dir . $filename);
                $lang = $M;
            }
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

    /**
     * Persist submitted translations for a language file.
     *
     * @param string $language
     * @param string $file
     * @return void
     */
    private function save($language, $file)
    {
        $file = ee()->security->sanitize_filename($file);

        $dest_dir = $this->languages_dir . $language . '/';
        $filename = $file . '_lang.php';
        $dest_loc = $dest_dir . $filename;

        ee()->lang->loadfile($file);
        $allowed_keys = $this->getAllowedTranslationKeys($file);
        $normalized = $this->normalizeSubmittedTranslations($_POST, $allowed_keys);

        if (! $normalized['is_valid']) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('translate_error'))
                ->addToBody(lang('translate_error_desc'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('utilities/translate/' . $language . '/edit/' . $file));
            return;
        }

        $str = $this->renderLanguagePhp($normalized['translations']);

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

    /**
     * Queue an inline alert when a translation file cannot be safely parsed.
     *
     * @param string $path
     * @return void
     */
    private function addInvalidTranslationFileAlert($path)
    {
        ee('CP/Alert')->makeInline('shared-form')
            ->asIssue()
            ->withTitle(lang('cannot_access'))
            ->addToBody($path)
            ->defer();
    }

    /**
     * Return valid translatable keys from the canonical English language file.
     *
     * @param string $file
     * @return array<int, string>
     */
    private function getAllowedTranslationKeys($file)
    {
        $english = ee()->lang->load($file, 'english', true);

        if (! is_array($english)) {
            return [];
        }

        $allowed_keys = [];
        foreach (array_keys($english) as $key) {
            if (is_string($key) && $key !== '') {
                $allowed_keys[] = $key;
            }
        }

        return $allowed_keys;
    }

    /**
     * Return non-translation form keys expected on CP form posts.
     *
     * @return array<int, string>
     */
    private function getKnownTranslationFormKeys()
    {
        return [
            'csrf_token',
            'XID',
            'site_id',
        ];
    }

    /**
     * Validate posted translations and normalize them for safe persistence.
     *
     * @param mixed $post
     * @param array<int, string> $allowed_keys
     * @param callable|null $cleaner
     * @return array{
     *     is_valid: bool,
     *     translations: array<string, string>,
     *     unexpected_keys: array<int, string>,
     *     invalid_value_keys: array<int, string>
     * }
     */
    private function normalizeSubmittedTranslations($post, $allowed_keys, $cleaner = null)
    {
        if (! is_array($post)) {
            $post = [];
        }

        if ($cleaner === null) {
            $cleaner = function ($value) {
                return ee('Security/XSS')->clean($value);
            };
        }

        $translations = [];
        $unexpected_keys = [];
        $invalid_value_keys = [];

        $allowed_lookup = array_fill_keys($allowed_keys, true);
        $known_form_keys = array_fill_keys($this->getKnownTranslationFormKeys(), true);

        // Any unknown field names indicate client-side form tampering.
        foreach (array_keys($post) as $posted_key) {
            if (! isset($allowed_lookup[$posted_key]) && ! isset($known_form_keys[$posted_key])) {
                $unexpected_keys[] = $posted_key;
            }
        }

        // Persist only allowed keys and keep deterministic output order.
        foreach ($allowed_keys as $allowed_key) {
            $raw_value = array_key_exists($allowed_key, $post) ? $post[$allowed_key] : '';

            if (! is_scalar($raw_value) && $raw_value !== null) {
                $invalid_value_keys[] = $allowed_key;
                continue;
            }

            $translations[$allowed_key] = $this->sanitizeTranslationValue((string) $raw_value, $cleaner);
        }

        return [
            'is_valid' => empty($unexpected_keys) && empty($invalid_value_keys),
            'translations' => $translations,
            'unexpected_keys' => $unexpected_keys,
            'invalid_value_keys' => $invalid_value_keys,
        ];
    }

    /**
     * Apply legacy value sanitization before writing translation values.
     *
     * @param string $value
     * @param callable $cleaner
     * @return string
     */
    private function sanitizeTranslationValue($value, $cleaner)
    {
        $value = str_replace('<script', '', $value);
        $value = str_replace('<iframe', '', $value);

        return $cleaner($value);
    }

    /**
     * Render the translation array as a PHP language file.
     *
     * @param array<string, string> $translations
     * @return string
     */
    private function renderLanguagePhp($translations)
    {
        // Preserve legacy sentinel empty key in generated language files.
        $translations[''] = '';

        return "<?php\n" . '$lang = ' . var_export($translations, true) . ";\n\n// End of File\n";
    }
}
// END CLASS

// EOF
