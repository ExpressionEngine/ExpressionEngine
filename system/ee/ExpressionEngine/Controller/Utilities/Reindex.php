<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Controller\Utilities;

use ExpressionEngine\Service\Model\Collection;

/**
 * Reindex Controller
 */
class Reindex extends Utilities
{
    const CACHE_KEY = '/search/reindex';

    protected $field_ids = [];
    protected $entry_ids = [];
    protected $reindexing = false;

    public function __construct()
    {
        parent::__construct();

        $data = ee()->cache->get(self::CACHE_KEY);
        if ($data === false || $data['reindexing'] === false) {
            $site_id = ee()->config->item('site_id');

            $fields = $this->getFields();
            $this->field_ids = $this->getFieldIdNames($fields);
            $this->entry_ids = [
                'all' => $this->getEntryIds($fields),
                $site_id => $this->getEntryIds($fields, $site_id)
            ];
            $this->cache();
        } else {
            $this->field_ids = $data['field_ids'];
            $this->entry_ids = $data['entry_ids'];
            $this->reindexing = $data['reindexing'];
        }
    }

    /**
     * Gets a Collection of ChannelField entities whose fieldtypes implement
     * the `reindex` function. These are the fields that need reindexing, the
     * rest can be ignored.
     *
     * @return obj Collection of ChannelField entities
     */
    protected function getFields()
    {
        $fieldtypes = [];

        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');
        foreach (ee()->api_channel_fields->fetch_installed_fieldtypes() as $type => $data) {
            $ft = ee()->api_channel_fields->setup_handler($type, true);
            if (method_exists($ft, 'reindex')) {
                $fieldtypes[] = $type;
            }
        }

        return ee('Model')->get('ChannelField')
            ->filter('field_type', 'IN', $fieldtypes)
            ->all();
    }

    /**
     * Given a Collection of ChannelField entities extract an array of the
     * field id "names", i.e. ['field_id_1', 'field_id_13']
     *
     * @param obj $fields A Collection of ChannelField entities
     * @return array An array of field id names i.e. ['field_id_1', 'field_id_13']
     */
    protected function getFieldIdNames(Collection $fields)
    {
        $field_ids = [];

        foreach ($fields as $field) {
            $field_ids[] = 'field_id_' . $field->getId();
        }

        return $field_ids;
    }

    /**
     * Given a Collection of ChannelField entities fetch a list of all the
     * Channel entries that use at least one of these fields
     *
     * @param obj $fields A Collection of ChannelField entities
     * @return array An array of Channel entry IDs.
     */
    protected function getEntryIds(Collection $fields, $site_id = null)
    {
        $channel_ids = [];
        $entry_ids = ee('Model')->get('ChannelEntry')
            ->fields('entry_id');

        foreach ($fields as $field) {
            $channel_ids = array_merge($channel_ids, $field->getAllChannels()->pluck('channel_id'));
        }

        $channel_ids = array_unique($channel_ids);

        if (! empty($channel_ids)) {
            $entry_ids->filter('channel_id', 'IN', $channel_ids);
        }

        if ($site_id) {
            $entry_ids->filter('site_id', $site_id);
        }

        return $entry_ids->all()->pluck('entry_id');
    }

    /**
     * Save the field_ids, entry_ids, and reindexing status to cache
     *
     * @return bool TRUE if it saved; FALSE if not
     */
    protected function cache()
    {
        $data = [
            'field_ids' => $this->field_ids,
            'entry_ids' => $this->entry_ids,
            'reindexing' => $this->reindexing
        ];

        return ee()->cache->save(self::CACHE_KEY, $data);
    }

    /**
     * Reindex utility
     *
     * @access	public
     * @return	void
     */
    public function index()
    {
        if (! ee('Permission')->has('can_access_data')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $site_id = ee()->config->item('site_id');

        ee()->cp->add_js_script('file', 'cp/utilities/reindex');

        ee()->javascript->set_global([
            'reindex' => [
                'endpoint' => ee('CP/URL')->make('utilities/reindex/process')->compile(),
                'entries' => [
                    'all' => count($this->entry_ids['all']),
                    'one' => count($this->entry_ids[$site_id])
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
                        'desc' => sprintf(lang('search_reindex_desc'), number_format(count($this->entry_ids['all']))),
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
     * Process the reindexing
     *
     * @access	public
     * @return	void
     */
    public function process()
    {
        // Only accept POST requests
        if (is_null(ee('Request')->post('progress'))) {
            show_404();
        }

        $site = (ee('Request')->post('all_sites', 'y') == 'y') ? 'all' : ee()->config->item('site_id');

        if (! $this->reindexing) {
            ee()->logger->log_action(lang('search_reindexed_started'));
            $this->reindexing = true;
            $this->cache();
        }

        $progress = (int) ee('Request')->post('progress');

        if (isset($this->entry_ids[$site][$progress])) {
            $entry = ee('Model')->get('ChannelEntry', $this->entry_ids[$site][$progress])->first();

            foreach ($entry->getCustomFields() as $field) {
                $name = $field->getName();

                if (in_array($name, $this->field_ids)) {
                    $search_data = $field->reindex($entry);
                    $entry->setRawProperty($name, $search_data);
                }
            }

            $dirty = $entry->getDirty();

            if (! empty($dirty)) {
                $entry->saveFieldData($dirty);
            }

            $progress++;
        }

        if ($progress >= count($this->entry_ids[$site])) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('reindex_success'))
                ->addToBody(lang('reindex_success_desc'))
                ->defer();

            ee()->logger->log_action(sprintf(lang('search_reindexed_completed'), number_format(count($this->entry_ids[$site]))));

            ee()->config->update_site_prefs(['search_reindex_needed' => null], 0);

            $this->reindexing = false; // For symmetry and "futureproofing"
            ee()->cache->delete(self::CACHE_KEY); // All done!
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
