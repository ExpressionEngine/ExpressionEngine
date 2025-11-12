<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Controller\Utilities;

use ExpressionEngine\Service\Model\Collection;

/**
 * File Usage Update Controller
 */
class FileUsage extends Utilities
{
    protected $fileUsage;

    public function __construct()
    {
        parent::__construct();

        $this->fileUsage = ee('FileUsage');
    }

    /**
     * Update File Usage utility
     *
     * @access  public
     * @return  void
     */
    public function index()
    {
        if (! ee('Permission')->has('can_access_data')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->cp->add_js_script('file', 'cp/utilities/file-usage');

        ee()->javascript->set_global([
            'update_file_usage' => [
                'endpoint' => ee('CP/URL')->make('utilities/file-usage/process')->compile(),
                'fieldsAndTables' => $this->fileUsage->getFieldsAndTables(),
                'desc' => lang('update_file_usage_desc'),
                'base_url' => ee('CP/URL')->make('utilities/file-usage')->compile(),
                'ajax_fail_banner' => ee('CP/Alert')->makeInline('update_file_usage-fail')
                    ->asIssue()
                    ->withTitle(lang('update_file_usage_fail'))
                    ->addToBody('%body%')
                    ->render()
            ]
        ]);

        $vars = [
            'base_url' => ee('CP/URL')->make('utilities/file-usage/process')->compile(),
            'hide_top_buttons' => true,
            'save_btn_text' => 'update',
            'save_btn_text_working' => 'updating',
            'sections' => [
                [
                    [
                        'title' => 'update_file_usage',
                        'desc' => sprintf(lang('update_file_usage_desc'), number_format(count($this->fileUsage->getFieldsAndTables()))),
                        'fields' => [
                            'progress' => [
                                'type' => 'html',
                                'content' => ee()->load->view('_shared/progress_bar', array('percent' => 0), true)
                            ]
                        ]
                    ]
                ]
            ],
        ];

        ee('CP/Alert')->makeInline('update_file_usage_explained')
            ->asTip()
            ->cannotClose()
            ->addToBody(
                sprintf(
                    lang('update_file_usage_explained_desc'),
                    DOC_URL . 'control-panel/file-manager/file-manager.html#compatibility-mode',
                    ee('CP/URL')->make('utilities/db-backup')->compile(),
                    ee('CP/URL')->make('settings/content-design')->compile() . '#fieldset-file_manager_compatibility_mode'
                )
            )
            ->now();

        ee()->view->extra_alerts = ['update_file_usage_explained'];

        ee()->view->cp_page_title = lang('update_file_usage');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('update_file_usage')
        );

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Process the updating
     *
     * @access  public
     * @return  void
     */
    public function process()
    {
        // Only accept POST requests
        if (is_null(ee('Request')->post('progress'))) {
            show_404();
        }

        $progress = (int) ee('Request')->post('progress');

        $progress = $this->fileUsage->process($progress);

        // If completed
        if (! $this->fileUsage->inProgress()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('update_file_usage_success'))
                ->addToBody(lang('update_file_usage_success_desc'))
                ->defer();

            ee()->output->send_ajax_response(['status' => 'finished']);
        }

        ee()->output->send_ajax_response([
            'status' => 'in_progress',
            'progress' => $progress
        ]);
    }
}
// END CLASS

// EOF
