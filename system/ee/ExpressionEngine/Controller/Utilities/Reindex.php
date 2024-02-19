<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Controller\Utilities;

/**
 * Reindex Controller
 */
class Reindex extends Utilities
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = ee('Channel/Reindex');
        $this->service->site_id = ee()->config->item('site_id');
        $this->service->initialize();
    }


    /**
     * Reindex utility
     *
     * @access  public
     * @return  void
     */
    public function index()
    {
        if (! ee('Permission')->has('can_access_data')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->cp->add_js_script('file', 'cp/utilities/reindex');

        ee()->javascript->set_global([
            'reindex' => [
                'endpoint' => ee('CP/URL')->make('utilities/reindex/process')->compile(),
                'entries' => [
                    'all' => count($this->service->entry_ids['all']),
                    'one' => count($this->service->entry_ids[$this->service->site_id])
                ],
                'search_desc' => lang('search_reindex_desc'),
                'base_url' => ee('CP/URL')->make('utilities/reindex')->compile(),
                'ajax_fail_banner' => ee('CP/Alert')->makeInline('search-reindex-fail')
                    ->asIssue()
                    ->withTitle(lang('search_reindex_fail'))
                    ->addToBody('%body%')
                    ->render()
            ]
        ]);

        $vars = [
            'base_url' => ee('CP/URL')->make('utilities/reindex/process')->compile(),
            'hide_top_buttons' => true,
            'save_btn_text' => 'btn_reindex',
            'save_btn_text_working' => 'btn_reindex_working',
            'sections' => [
                [
                    [
                        'title' => 'search_reindex',
                        'desc' => sprintf(lang('search_reindex_desc'), number_format(count($this->service->entry_ids['all']))),
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

        if (ee('Model')->get('Site')->count() > 1) {
            $vars['sections'][0][] = [
                'title' => 'all_sites',
                'desc' => 'all_sites_desc',
                'fields' => [
                    'all_sites' => [
                        'type' => 'toggle',
                        'value' => 1
                    ]
                ]
            ];
        }

        ee('CP/Alert')->makeInline('reindex-explained')
            ->asTip()
            ->cannotClose()
            ->addToBody(lang('reindex_explained_desc'))
            ->now();

        ee()->view->extra_alerts = ['reindex-explained'];

        if (! ee()->config->item('search_reindex_needed')) {
            ee('CP/Alert')->makeInline('reindex-not-needed')
                ->asImportant()
                ->withTitle(lang('reindex_not_needed'))
                ->addToBody(lang('reindex_not_needed_desc'))
                ->now();
            ee()->view->extra_alerts = ['reindex-explained', 'reindex-not-needed'];
        }

        ee()->view->cp_page_title = lang('search_reindex');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('search_reindex')
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
        $site = (ee('Request')->post('all_sites', 'y') == 'y') ? 'all' : ee()->config->item('site_id');

        $progress = $this->service->process($progress, $site);

        // If completed
        if (! $this->service->inProgress()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('reindex_success'))
                ->addToBody(lang('reindex_success_desc'))
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
