<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Channels;

use ExpressionEngine\Library\CP;
use ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;
use ExpressionEngine\Model\Channel\Channel;
use ExpressionEngine\Service\Model\Collection;

/**
 * Channels Controller
 */
class Channels extends AbstractChannelsController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if (ee()->input->post('bulk_action') == 'remove') {
            $this->remove(ee()->input->post('selection'));
            ee()->functions->redirect(ee('CP/URL')->make('channels'));
        }

        $vars['base_url'] = ee('CP/URL', 'channels');

        $channels = ee('Model')->get('Channel')
            ->filter('site_id', ee()->config->item('site_id'));

        if ($search = ee()->input->get_post('filter_by_keyword')) {
            $channels = $channels->search('channel_title', $search);
        }

        $total_channels = $channels->count();

        $filters = ee('CP/Filter')
            ->add('Keyword')
            ->add('Perpage', $total_channels, 'all_channels', true);
        $filter_values = $filters->values();

        $page = ee('Request')->get('page') ?: 1;
        $per_page = $filter_values['perpage'];

        $channels = $channels->offset(($page - 1) * $per_page)
            ->limit($per_page)
            ->order('channel_title')
            ->all();

        // Only show filters if there is data to filter or we are currently filtered
        if ($search or $channels->count() > 0) {
            $vars['filters'] = $filters->render($vars['base_url']);
        }

        $highlight_id = ee()->session->flashdata('highlight_id');
        $imported_channels = ee()->session->flashdata('imported_channels') ?: [];
        $data = [];
        foreach ($channels as $channel) {
            $edit_url = ee('CP/URL')->make('channels/edit/' . $channel->getId());

            $data[] = [
                'id' => $channel->getId(),
                'label' => $channel->channel_title,
                'href' => $edit_url,
                'extra' => LD . $channel->channel_name . RD,
                'selected' => ($highlight_id && $channel->getId() == $highlight_id) or in_array($channel->getId(), $imported_channels),
                'toolbar_items' => [
                    'download' => [
                        'href' => ee('CP/URL', 'channels/sets/export/' . $channel->getId()),
                        'title' => lang('export'),
                        'content' => ' ' . lang('export')
                    ],
                    'layout-set' => [
                        'href' => ee('CP/URL', 'channels/layouts/' . $channel->getId()),
                        'title' => $channel->channel_title . ' ' . lang('layouts'),
                        'content' => ' ' . lang('layouts')
                    ]
                ],
                'selection' => [
                    'name' => 'selection[]',
                    'value' => $channel->getId(),
                    'data' => [
                        'confirm' => lang('channel') . ': <b>' . ee('Format')->make('Text', $channel->channel_title)->convertToEntities() . '</b>'
                    ]
                ]
            ];
        }

        ee()->javascript->set_global('lang.remove_confirm', lang('layout') . ': <b>### ' . lang('channels') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/confirm_remove',
            ),
        ));

        $vars['pagination'] = ee('CP/Pagination', $total_channels)
            ->perPage($per_page)
            ->currentPage($page)
            ->render(ee('CP/URL')->make('channels', $filter_values));

        $vars['cp_page_title'] = lang('all_channels');
        $vars['channels'] = $data;
        $vars['create_url'] = ee('CP/URL', 'channels/create');
        $vars['no_results'] = ['text' =>
            sprintf(lang('no_found'), lang('channels'))
            . (ee('Permission')->has('can_create_channels') ? ' <a href="' . $vars['create_url'] . '">' . lang('add_new') . '</a> '
            . lang('or') . ' <a href="#" rel="import-channel">' . lang('import') . '</a>' : '')];

        ee()->view->cp_breadcrumbs = array(
            '' => lang('channels')
        );

        ee()->cp->render('channels/index', $vars);
    }

    /**
     * Remove channels handler
     */
    public function remove()
    {
        if (! ee('Permission')->can('delete_channels')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $channel_ids = ee()->input->post('selection');
        $channels = ee('Model')->get('Channel', $channel_ids)->all();

        if ($channels->count() > 0 && ee()->input->post('bulk_action') == 'remove') {
            $channels->delete();

            ee('CP/Alert')->makeInline('channels')
                ->asSuccess()
                ->withTitle(lang('channels_deleted'))
                ->addToBody(sprintf(lang('channels_deleted_desc'), count($channel_ids)))
                ->defer();
        } else {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->functions->redirect(ee('CP/URL')->make('channels', ee()->cp->get_url_state()));
    }

    /**
     * New channel form
     */
    public function create()
    {
        if (! ee('Permission')->can('create_channels')) {
            show_error(lang('unauthorized_access'), 403);
        }

        return $this->form();
    }

    /**
     * Edit channel form
     */
    public function edit($channel_id)
    {
        if (! ee('Permission')->can('edit_channels')) {
            show_error(lang('unauthorized_access'), 403);
        }

        return $this->form($channel_id);
    }

    /**
     * Channel creation/edit form
     *
     * @param   int $channel_id ID of Channel to edit
     */
    private function form($channel_id = null)
    {
        if (is_null($channel_id)) {
            // Only auto-complete channel short name for new channels
            ee()->cp->add_js_script('plugin', 'ee_url_title');

            ee()->javascript->set_global([
                'publish.foreignChars' => ee()->config->loadFile('foreign_chars')
            ]);

            ee()->javascript->output('
                $("input[name=channel_title]").bind("keyup keydown", function() {
                    $(this).ee_url_title("input[name=channel_name]");
                });
            ');

            $alert_key = 'created';
            ee()->view->cp_page_title = lang('create_new_channel');
            ee()->view->base_url = ee('CP/URL')->make('channels/create');
            $channel = ee('Model')->make('Channel');
            $channel->title_field_label = lang('title');

            // For some reason, not setting these to NULL can result in in pre-populated
            // selections. @TODO find and fix the bug
            $channel->FieldGroups = null;
            $channel->CustomFields = null;
            $channel->Statuses = null;
            $channel->CategoryGroups = null;
        } else {
            $channel = ee('Model')->get('Channel', (int) $channel_id)->first();

            if (! $channel) {
                show_error(lang('unauthorized_access'), 403);
            }

            $alert_key = 'updated';
            ee()->view->cp_page_title = lang('edit_channel');
            ee()->view->base_url = ee('CP/URL')->make('channels/edit/' . $channel_id);
        }

        $vars['errors'] = null;

        if (! empty($_POST)) {
            // List of all the fields && groups before saving
            $beforeFields = $channel->CustomFields->pluck('field_id');
            $beforeFieldGroups = $channel->FieldGroups->pluck('group_id');

            // Set up the channel after changes
            $channel = $this->setWithPost($channel);

            // List of all the fields && groups after saving
            $afterFields = $channel->CustomFields->pluck('field_id');
            $afterFieldGroups = $channel->FieldGroups->pluck('group_id');

            // Get the fields that are different
            $removedFields = array_diff($beforeFields, $afterFields);
            $removedFieldGroups = array_diff($beforeFieldGroups, $afterFieldGroups);
            $addedFields = array_diff($afterFields, $beforeFields);
            $addedFieldGroups = array_diff($afterFieldGroups, $beforeFieldGroups);

            $channel->site_id = ee()->config->item('site_id');
            $result = $channel->validate();

            if ($response = $this->ajaxValidation($result)) {
                return $response;
            }

            if ($result->isValid()) {
                $channel = $this->saveChannel($channel);

                if (is_null($channel_id)) {
                    ee()->session->set_flashdata('highlight_id', $channel->getId());
                }

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('channel_' . $alert_key))
                    ->addToBody(sprintf(lang('channel_' . $alert_key . '_desc'), $channel->channel_title))
                    ->defer();

                if (ee('Request')->post('submit') == 'save_and_new') {
                    $redirectUrl = ee('CP/URL')->make('channels/create');
                } elseif (ee()->input->post('submit') == 'save_and_close') {
                    $redirectUrl = ee('CP/URL')->make('channels');
                } else {
                    $redirectUrl = ee('CP/URL')->make('channels/edit/' . $channel->getId());
                }
                // If we added a field that is conditional, we need to sync channel entries
                $syncNeeded = false;
                if (!empty($addedFields)) {
                    $fields = $channel->CustomFields->filter('field_id', 'IN', $addedFields);
                    foreach ($fields as $field) {
                        if ($field->field_is_conditional) {
                            $syncNeeded = true;

                            break;
                        }
                    }
                }

                // If we added a field group that has a field that is conditional, we need to sync channel entries
                if (!empty($addedFieldGroups) && !$syncNeeded) {
                    $fieldGroups = $channel->FieldGroups->filter('group_id', 'IN', $addedFieldGroups);

                    foreach ($fieldGroups as $fieldGroup) {
                        foreach ($fieldGroup->ChannelFields as $field) {
                            if ($field->field_is_conditional) {
                                $syncNeeded = true;

                                break;
                            }
                        }

                        if ($syncNeeded) {
                            break;
                        }
                    }
                }

                // Redirect to sync page if we need to
                if ($syncNeeded) {
                    $channel->conditional_sync_required = 'y';
                    $channel->save();

                    ee()->functions->redirect(
                        ee('CP/URL')->make('utilities/sync-conditional-fields/sync')
                            ->setQueryStringVariable('channel_id', $channel->getId())
                            ->setQueryStringVariable('return', base64_encode($redirectUrl))
                            ->compile()
                    );
                }

                ee()->functions->redirect($redirectUrl);
            } else {
                // Put cat_group back as an array
                $_POST['cat_group'] = explode('|', $_POST['cat_group']);

                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('channel_not_' . $alert_key))
                    ->addToBody(lang('channel_not_' . $alert_key . '_desc'))
                    ->now();
            }
        }

        $vars['sections'] = [];
        $vars['tabs'] = [
            'channel' => $this->renderChannelTab($channel, $vars['errors']),
            'fields' => $this->renderFieldsTab($channel, $vars['errors']),
            'categories' => $this->renderCategoriesTab($channel, $vars['errors']),
            'statuses' => $this->renderStatusesTab($channel, $vars['errors']),
            'settings' => $this->renderSettingsTab($channel, $vars['errors']),
            'permissions' => $this->renderPermissionsTab($channel, $vars['errors']),
        ];

        ee()->javascript->set_global([
            'channelManager.fieldGroup.createUrl' => ee('CP/URL')->make('fields/groups/create')->compile(),
            'channelManager.fieldGroup.fieldUrl' => ee('CP/URL')->make('channels/render-field-groups-field')->compile(),

            'channelManager.fields.createUrl' => ee('CP/URL')->make('fields/create')->compile(),
            'channelManager.fields.fieldUrl' => ee('CP/URL')->make('channels/render-fields-field')->compile(),

            'channelManager.catGroup.createUrl' => ee('CP/URL')->make('categories/groups/create')->compile(),
            'channelManager.catGroup.editUrl' => ee('CP/URL')->make('categories/groups/edit/###')->compile(),
            'channelManager.catGroup.toggleUrl' => ee('CP/URL')->make('channels/groups/edit/###')->compile(),
            'channelManager.catGroup.fieldUrl' => ee('CP/URL')->make('channels/render-category-groups-field')->compile(),

            'channelManager.statuses.createUrl' => ee('CP/URL')->make('channels/status/create')->compile(),
            'channelManager.statuses.editUrl' => ee('CP/URL')->make('channels/status/edit/###')->compile(),
            'channelManager.statuses.removeUrl' => ee('CP/URL')->make('channels/status/remove')->compile(),
            'channelManager.statuses.fieldUrl' => ee('CP/URL')->make('channels/render-statuses-field')->compile()
        ]);

        $fieldtypes = ee('Model')->get('Fieldtype')
            ->fields('name')
            ->all();

        // Call fieldtypes' display_settings methods to load any needed JS
        foreach ($fieldtypes as $fieldtype) {
            $dummy_field = ee('Model')->make('ChannelField');
            $dummy_field->field_type = $fieldtype->name;
            $dummy_field->getSettingsForm();
        }

        ee()->cp->add_js_script('plugin', 'ee_url_title');
        ee()->cp->add_js_script('file', 'cp/channel/channel_manager');

        ee()->javascript->set_global('status.default_name', lang('status'));
        ee()->cp->add_js_script('file', array('library/simplecolor', 'components/colorpicker'));

        ee()->view->header = array(
            'title' => is_null($channel_id) ? lang('channels') : ee('Security/XSS')->clean(htmlspecialchars($channel->channel_title)),
            'toolbar_items' => array(
                'settings' => array(
                    'href' => ee('CP/URL')->make('settings/content-design'),
                    'title' => lang('settings')
                )
            )
        );

        ee()->view->cp_page_title = is_null($channel_id) ? lang('create_channel') : lang('edit_channel');
        ee()->view->ajax_validate = true;
        ee()->view->save_btn_text = lang('save');
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('channels')->compile() => lang('channels'),
            '' => is_null($channel_id) ? lang('create_channel') : lang('edit_channel')
        );

        $vars['buttons'] = [
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save',
                'text' => 'save',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_new',
                'text' => 'save_and_new',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_close',
                'text' => 'save_and_close',
                'working' => 'btn_saving'
            ]
        ];

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Save category group settings
     *
     * @param Channel $channel
     * @return void
     */
    private function setCategoryGroupSettings(Channel $channel)
    {
        $assignedGroupSettings = [];
        foreach ($channel->CategoryGroups as $group) {
            // can't grab CategoryGroupSettings at once, because they would not save properly
            // so taking one by one - there shouldn't be too many
            $setting = ee('Model')->get('CategoryGroupSettings')->filter('channel_id', $channel->channel_id)->filter('group_id', $group->group_id)->first();
            if (empty($setting)) {
                $setting = ee('Model')->make('CategoryGroupSettings', [
                    'group_id' => $group->group_id,
                    'channel_id' => $channel->channel_id,
                    'site_id' => $channel->site_id,
                ]);
            }
            $setting->cat_required = !empty(ee('Request')->post('cat_required')) && in_array($group->group_id, ee('Request')->post('cat_required')) ? 'y' : 'n';
            $setting->cat_allow_multiple = !empty(ee('Request')->post('cat_allow_multiple')) && in_array($group->group_id, ee('Request')->post('cat_allow_multiple')) ? 'y' : 'n';
            $assignedGroupSettings[] = $setting;
        }
        $channel->CategoryGroupSettings = new Collection($assignedGroupSettings);
    }

    /**
     * Renders the main Channel tab for the Channel create/edit form
     *
     * @param Channel $channel A Channel entity
     * @param null|ValidationResult $errors NULL (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderChannelTab($channel, $errors)
    {
        $section = array(
            array(
                'title' => 'channel_title',
                'fields' => array(
                    'channel_title' => array(
                        'type' => 'text',
                        'value' => $channel->channel_title,
                        'required' => true
                    )
                )
            ),
            array(
                'title' => 'short_name',
                'desc' => 'alphadash_desc',
                'fields' => array(
                    'channel_name' => array(
                        'type' => 'text',
                        'value' => $channel->channel_name,
                        'required' => true
                    )
                )
            ),
            array(
                'title' => 'channel_max_entries',
                'desc' => 'channel_max_entries_desc',
                'fields' => array(
                    'max_entries' => array(
                        'type' => 'text',
                        'value' => $channel->max_entries ?: ''
                    )
                )
            )
        );

        // Only show duplicate channel option for new channels and if channels exist
        if ($channel->isNew() && ee('Model')->get('Channel')->filter('site_id', ee()->config->item('site_id'))->count()) {
            // Channel duplicate preferences menu
            $channels = ee('Model')->get('Channel')
                ->filter('site_id', ee()->config->item('site_id'))
                ->order('channel_title')
                ->all();
            $duplicate_channel_prefs_options[''] = lang('channel_do_not_duplicate');
            foreach ($channels as $dupe_channel) {
                $duplicate_channel_prefs_options[$dupe_channel->channel_id] = $dupe_channel->channel_title;
            }

            $section[] = array(
                'title' => 'channel_duplicate',
                'desc' => 'channel_duplicate_desc',
                'fields' => array(
                    'duplicate_channel_prefs' => array(
                        'type' => 'radio',
                        'choices' => $duplicate_channel_prefs_options,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('channels'))
                        ]
                    )
                )
            );
        }

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    /**
     * Renders the Fields tab for the Channel create/edit form
     *
     * @param Channel $channel A Channel entity
     * @param null|ValidationResult $errors NULL (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderFieldsTab($channel, $errors)
    {
        $add_groups_button = null;
        $add_fields_button = null;

        if (ee('Permission')->can('create_channel_fields')) {
            $add_groups_button = [
                'text' => 'add_group',
                'rel' => 'add_new'
            ];
            $add_fields_button = [
                'text' => 'add_field',
                'rel' => 'add_new'
            ];
        }

        $section = array(
            array(
                'title' => 'title_field_label',
                'desc' => 'title_field_label_desc',
                'fields' => array(
                    'title_field_label' => array(
                        'type' => 'text',
                        'value' => $channel->title_field_label
                    )
                )
            ),
            array(
                'title' => 'title_field_instructions',
                'desc' => 'title_field_instructions_desc',
                'fields' => array(
                    'title_field_instructions' => array(
                        'type' => 'textarea',
                        'value' => $channel->title_field_instructions
                    )
                )
            ),
            array(
                'title' => 'field_groups',
                'desc' => 'field_groups_desc',
                'button' => $add_groups_button,
                'fields' => array(
                    'field_groups' => array(
                        'type' => 'html',
                        'content' => $this->renderFieldGroupsField($channel)
                    )
                )
            ),
            array(
                'title' => 'fields',
                'desc' => 'fields_desc',
                'button' => $add_fields_button,
                'fields' => array(
                    'custom_fields' => array(
                        'type' => 'html',
                        'content' => $this->renderFieldsField($channel)
                    )
                )
            ),
        );

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    /**
     * Renders the Field Groups selection form for the channel create/edit form
     *
     * @param Channel $channel A Channel entity, optional
     * @return string HTML
     */
    public function renderFieldGroupsField($channel = null)
    {
        $field_group_options = ee('Model')->get('ChannelFieldGroup')
            ->fields('group_name')
            ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
            ->order('group_name')
            ->all()
            ->getDictionary('group_id', 'group_name');

        $selected = ee('Request')->post('field_groups') ?: [];

        if ($channel) {
            $selected = $channel->FieldGroups->pluck('group_id');
        }

        $no_results = [
            'text' => sprintf(lang('no_found'), lang('field_groups'))
        ];

        if (ee('Permission')->can('create_channel_fields')) {
            $no_results['link_text'] = 'add_new';
            $no_results['link_href'] = ee('CP/URL')->make('fields/groups/create');
        }

        return ee('View')->make('ee:_shared/form/fields/select')->render([
            'field_name' => 'field_groups',
            'choices' => $field_group_options,
            'value' => $selected,
            'multi' => true,
            'no_results' => $no_results
        ]);
    }

    /**
     * Renders the Fields selection form for the channel create/edit form
     *
     * @param Channel $channel A Channel entity, optional
     * @return string HTML
     */
    public function renderFieldsField($channel = null)
    {
        $selected = ee('Request')->post('custom_fields') ?: [];
        if ($channel) {
            $selected = $channel->CustomFields->pluck('field_id');
        }

        $fields = ee('Model')->get('ChannelField')
            ->fields('field_id', 'field_label', 'field_name')
            ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
            ->order('field_label')
            ->all();

        $custom_field_options = [];
        foreach ($fields as $field) {
            if (in_array($field->getId(), $selected)) {
                $custom_field_options[] = [
                    'label' => $field->field_label,
                    'value' => $field->getId(),
                    'instructions' => LD . $field->field_name . RD
                ];
            }
        }
        foreach ($fields as $field) {
            if (! in_array($field->getId(), $selected)) {
                $custom_field_options[] = [
                    'label' => $field->field_label,
                    'value' => $field->getId(),
                    'instructions' => LD . $field->field_name . RD
                ];
            }
        }

        $no_results = [
            'text' => sprintf(lang('no_found'), lang('fields'))
        ];

        if (ee('Permission')->can('create_channel_fields')) {
            $no_results['link_text'] = 'add_new';
            $no_results['link_href'] = ee('CP/URL')->make('fields/create');
        }

        return ee('View')->make('ee:_shared/form/fields/select')->render([
            'field_name' => 'custom_fields',
            'choices' => $custom_field_options,
            'value' => $selected,
            'multi' => true,
            'no_results' => $no_results
        ]);
    }

    /**
     * Renders the Categories tab for the Channel create/edit form
     *
     * @param Channel $channel A Channel entity
     * @param null|ValidationResult $errors NULL (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderCategoriesTab($channel, $errors)
    {
        $add_groups_button = null;

        if (ee('Permission')->can('create_categories')) {
            $add_groups_button = [
                'text' => 'add_group',
                'rel' => 'add_new'
            ];
        }

        $section = array(
            array(
                'title' => 'category_groups',
                'desc' => 'category_groups_desc',
                'attrs' => array(
                    'class' => 'channel-statuses'
                ),
                'button' => $add_groups_button,
                'fields' => array(
                    'cat_group' => array(
                        'type' => 'html',
                        'content' => $this->renderCategoryGroupsField($channel)
                    )
                )
            )
        );

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    /**
     * Renders the Category Groups selection form for the channel create/edit form
     *
     * @param Channel $channel A Channel entity, optional
     * @return string HTML
     */
    public function renderCategoryGroupsField($channel = null)
    {
        $categoryGroups = ee('Model')->get('CategoryGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('exclude_group', '!=', 1)
            ->order('group_name')
            ->all();

        $cat_group_options = [];

        $selected = ee('Request')->post('cat_group') ?: [];

        $categoryGroupSettings = $channel ? $channel->CategoryGroupSettings->indexBy('group_id') : null;

        if (!ee('Request')->isPost() && !is_null($channel) && ! is_null($channel->CategoryGroups)) {
            $selected =  $channel->CategoryGroups->pluck('group_id');
        }

        foreach ($categoryGroups as $categoryGroup) {
            $cat_group_options[$categoryGroup->group_id] = [
                'label' => $categoryGroup->group_name,
                'toggles' => [
                    'cat_allow_multiple' => ($categoryGroupSettings && isset($categoryGroupSettings[$categoryGroup->group_id])) ? $categoryGroupSettings[$categoryGroup->group_id]->cat_allow_multiple : true,
                    'cat_required' => ($categoryGroupSettings && isset($categoryGroupSettings[$categoryGroup->group_id])) ? $categoryGroupSettings[$categoryGroup->group_id]->cat_required : false
                ]
            ];
        }

        ee()->javascript->set_global([
            'lang.cat_allow_multiple' => lang('cat_allow_multiple'),
            'lang.cat_required' => lang('cat_required')
        ]);

        return ee('View')->make('ee:_shared/form/fields/select')->render([
            'field_name' => 'cat_group',
            'choices' => $cat_group_options,
            'encode' => false,
            'value' => $selected,
            'multi' => true,
            'force_react' => true,
            'reorderable' => false,
            'removable' => false,
            'editable' => true,
            'toggles' => [
                'cat_allow_multiple',
                'cat_required'
            ]
        ]);
    }

    /**
     * Renders the Statuses tab for the Channel create/edit form
     *
     * @param Channel $channel A Channel entity
     * @param null|ValidationResult $errors NULL (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderStatusesTab($channel, $errors)
    {
        $add_status_button = null;

        if (ee('Permission')->can('create_statuses')) {
            $add_status_button = [
                'text' => 'add_status',
                'rel' => 'add_new'
            ];
        }

        $section = array(
            array(
                'title' => 'statuses',
                'desc' => 'statuses_desc',
                'attrs' => array(
                    'class' => 'channel-statuses'
                ),
                'button' => $add_status_button,
                'fields' => array(
                    'statuses' => array(
                        'type' => 'html',
                        'content' => $this->renderStatusesField($channel),
                    )
                )
            )
        );

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    /**
     * Renders the Category Groups selection form for the channel create/edit form
     *
     * @param Channel $channel A Channel entity, optional
     * @return string HTML
     */
    public function renderStatusesField($channel = null)
    {
        $statuses = ee('Model')->get('Status')
            ->order('status_order')
            ->all();

        foreach ($statuses as $status) {
            $status_options[] = $status->getSelectOptionComponent(true);
        }

        $selected = ee('Request')->post('statuses') ?: [];

        if ($channel) {
            $selected = $channel->Statuses->pluck('status_id');
        }

        $default = ee('Model')->get('Status')
            ->filter('status', 'IN', ['open', 'closed'])
            ->all()
            ->pluck('status_id');

        // Make sure open and closed are always selected
        // And they are still haven't included
        if (!empty(array_diff($default, $selected))) {
            $selected = array_merge($selected, $default);
        }

        return ee('View')->make('ee:_shared/form/fields/select')->render([
            'field_name' => 'statuses',
            'choices' => $status_options,
            'disabled_choices' => $default,
            'encode' => false,
            'unremovable_choices' => $default,
            'value' => $selected,
            'multi' => true,
            'force_react' => true,
            'reorderable' => ee('Permission')->can('edit_statuses'),
            'removable' => ee('Permission')->can('delete_statuses'),
            'editable' => ee('Permission')->can('edit_statuses'),
            'reorder_ajax_url' => ee('CP/URL', 'channels/status/reorder')->compile()
        ]);
    }

    /**
     * Renders the Settings tab for the Channel create/edit form
     *
     * @param Channel $channel A Channel entity
     * @param null|ValidationResult $errors NULL (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderSettingsTab($channel, $errors)
    {
        // Default status menu
        $deft_status_options = [
            'open' => lang('open'),
            'closed' => lang('closed')
        ];
        $deft_status_options += $channel->Statuses
            ->sortBy('status_order')
            ->getDictionary('status', 'status');

        $deft_category_options = ['' => lang('none')];

        $category_group_ids = $channel->CategoryGroups->pluck('group_id');

        if (count($category_group_ids)) {
            $categories = ee('Model')->get('Category')
                ->with('CategoryGroup')
                ->filter('CategoryGroup.group_id', 'IN', $category_group_ids)
                ->order('CategoryGroup.group_name')
                ->order('Category.cat_name')
                ->all();

            if (count($categories) > 0) {
                foreach ($categories as $category) {
                    $deft_category_options[$category->cat_id] = $category->getCategoryGroup()->group_name . ': ' . $category->cat_name;
                }
            }
        }

        $channel_fields = $channel->getAllCustomFields();

        $search_excerpt_options = array();

        if (count($channel_fields) > 0) {
            foreach ($channel_fields as $channel_field) {
                $search_excerpt_options[$channel_field->field_id] = $channel_field->field_label;
            }
        }

        // HTML formatting
        $channel_html_formatting_options = array(
            'none' => lang('convert_to_entities'),
            'safe' => lang('allow_safe_html'),
            'all' => lang('allow_all_html')
        );

        // Default comment text formatting
        $comment_text_formatting_options = ee()->addons_model->get_plugin_formatting(true);

        // Comment HTML formatting
        $comment_html_formatting_options = array(
            'none' => lang('convert_to_entities'),
            'safe' => lang('allow_safe_html'),
            'all' => lang('allow_all_html_not_recommended')
        );

        if (! $channel_form = $channel->getChannelFormSettings()) {
            $channel_form = ee('Model')->make('ChannelFormSettings');
        }

        // Add "Use Channel Default" option for channel form default status
        $channel_form_statuses = array('' => lang('channel_form_default_status_empty'));
        $channel_form_statuses = array_merge($channel_form_statuses, $deft_status_options);
        ee()->load->model('admin_model');

        $author_list = $this->authorList();
        if (!empty($channel_form->default_author) && !isset($author_list[$channel_form->default_author])) {
            $defaultAuthor = ee('Model')->get('Member', $channel_form->default_author)->first();
            if (!empty($defaultAuthor)) {
                $author_list[$channel_form->default_author] = $defaultAuthor->getMemberName();
            }
        }

        $sections = array(
            array(
                array(
                    'title' => 'channel_description',
                    'desc' => 'channel_description_desc',
                    'fields' => array(
                        'channel_description' => array(
                            'type' => 'textarea',
                            'value' => $channel->channel_description
                        )
                    )
                ),
                array(
                    'title' => 'xml_language',
                    'desc' => 'xml_language_desc',
                    'fields' => array(
                        'channel_lang' => array(
                            'type' => 'radio',
                            'choices' => ee()->admin_model->get_xml_encodings(),
                            'value' => $channel->channel_lang ?: 'en'
                        )
                    )
                ),
            ),
            'url_path_settings' => array(
                array(
                    'title' => 'channel',
                    'desc' => 'channel_url_desc',
                    'fields' => array(
                        'channel_url' => array(
                            'type' => 'text',
                            'value' => $channel->getRawProperty('channel_url')
                        )
                    )
                ),
                array(
                    'title' => 'comment_form',
                    'desc' => 'comment_form_desc',
                    'fields' => array(
                        'comment_url' => array(
                            'type' => 'text',
                            'value' => $channel->getRawProperty('comment_url')
                        )
                    )
                ),
                array(
                    'title' => 'search_results',
                    'desc' => 'search_results_desc',
                    'fields' => array(
                        'search_results_url' => array(
                            'type' => 'text',
                            'value' => $channel->getRawProperty('search_results_url')
                        )
                    )
                ),
                array(
                    'title' => 'rss_feed',
                    'desc' => 'rss_feed_desc',
                    'fields' => array(
                        'rss_url' => array(
                            'type' => 'text',
                            'value' => $channel->getRawProperty('rss_url')
                        )
                    )
                ),
                array(
                    'title' => 'allow_preview',
                    'desc' => 'allow_preview_desc',
                    'fields' => array(
                        'allow_preview' => array(
                            'type' => 'yes_no',
                            'group_toggle' => array(
                                'y' => 'allow_preview'
                            ),
                            'value' => $channel->allow_preview
                        )
                    )
                ),
                array(
                    'title' => 'preview_url',
                    'desc' => 'preview_url_desc',
                    'group' => 'allow_preview',
                    'fields' => array(
                        'preview_url' => array(
                            'type' => 'text',
                            'value' => $channel->getRawProperty('preview_url')
                        )
                    )
                )

            ),
            'channel_defaults' => array(
                array(
                    'title' => 'default_title',
                    'desc' => 'default_title_desc',
                    'fields' => array(
                        'default_entry_title' => array(
                            'type' => 'text',
                            'value' => $channel->default_entry_title
                        )
                    )
                ),
                array(
                    'title' => 'url_title_prefix',
                    'desc' => 'url_title_prefix_desc',
                    'fields' => array(
                        'url_title_prefix' => array(
                            'type' => 'text',
                            'value' => $channel->url_title_prefix
                        )
                    )
                ),
                array(
                    'title' => 'enforce_auto_url_title',
                    'desc' => 'enforce_auto_url_title_desc',
                    'fields' => array(
                        'enforce_auto_url_title' => array(
                            'type' => 'yes_no',
                            'value' => $channel->enforce_auto_url_title
                        )
                    )
                ),
                array(
                    'title' => 'default_status',
                    'desc' => 'default_status_desc',
                    'fields' => array(
                        'deft_status' => array(
                            'type' => 'radio',
                            'choices' => $deft_status_options,
                            'value' => $channel->deft_status,
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('statuses'))
                            ]
                        )
                    )
                ),
                array(
                    'title' => 'default_category',
                    'desc' => 'default_category_desc',
                    'fields' => array(
                        'deft_category' => array(
                            'type' => 'radio',
                            'choices' => $deft_category_options,
                            'value' => $channel->deft_category,
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('categories'))
                            ]
                        )
                    )
                ),
                array(
                    'title' => 'search_excerpt',
                    'desc' => 'search_excerpt_desc',
                    'fields' => array(
                        'search_excerpt' => array(
                            'type' => 'radio',
                            'choices' => $search_excerpt_options,
                            'value' => $channel->search_excerpt,
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('fields'))
                            ]
                        )
                    )
                )
            ),
            'publishing' => array(
                array(
                    'title' => 'html_formatting',
                    'fields' => array(
                        'channel_html_formatting' => array(
                            'type' => 'radio',
                            'choices' => $channel_html_formatting_options,
                            'value' => $channel->channel_html_formatting
                        )
                    )
                ),
                array(
                    'title' => 'convert_image_urls',
                    'desc' => 'convert_image_urls_desc',
                    'fields' => array(
                        'channel_allow_img_urls' => array(
                            'type' => 'yes_no',
                            'value' => $channel->channel_allow_img_urls
                        )
                    )
                ),
                array(
                    'title' => 'convert_urls_emails_to_links',
                    'desc' => 'convert_urls_emails_to_links_desc',
                    'fields' => array(
                        'channel_auto_link_urls' => array(
                            'type' => 'yes_no',
                            'value' => $channel->channel_auto_link_urls
                        )
                    )
                ),
                array(
                    'title' => 'enable_sticky',
                    'desc' => 'enable_sticky_desc',
                    'fields' => array(
                        'sticky_enabled' => array(
                            'type' => 'yes_no',
                            'value' => $channel->sticky_enabled
                        )
                    )
                )
            ),
            'channel_form' => array(
                array(
                    'title' => 'default_status',
                    'desc' => 'channel_form_status_desc',
                    'fields' => array(
                        'default_status' => array(
                            'type' => 'radio',
                            'choices' => $channel_form_statuses,
                            'value' => $channel_form->default_status,
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('statuses'))
                            ]
                        )
                    )
                ),
                array(
                    'title' => 'channel_form_default_author',
                    'desc' => 'channel_form_default_author_desc',
                    'fields' => array(
                        'default_author' => array(
                            'type' => 'radio',
                            'choices' => $author_list,
                            'filter_url' => ee('CP/URL')->make('channels/author-list')->compile(),
                            'value' => isset($author_list[$channel_form->default_author])
                                ? $channel_form->default_author
                                : null,
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('authors'))
                            ]
                        )
                    )
                ),
                array(
                    'title' => 'allow_guest_submission',
                    'desc' => 'allow_guest_submission_desc',
                    'fields' => array(
                        'allow_guest_posts' => array(
                            'type' => 'yes_no',
                            'value' => $channel_form->allow_guest_posts
                        )
                    )
                )
            ),
            'versioning' => array(
                array(
                    'title' => 'enable_versioning',
                    'desc' => 'enable_versioning_desc',
                    'fields' => array(
                        'enable_versioning' => array(
                            'type' => 'yes_no',
                            'value' => $channel->enable_versioning,
                            'note' => form_label(
                                form_checkbox('update_versioning', 'y')
                                . lang('update_versioning')
                            )
                        )
                    )
                ),
                array(
                    'title' => 'max_versions',
                    'desc' => 'max_versions_desc',
                    'fields' => array(
                        'max_revisions' => array(
                            'type' => 'text',
                            'value' => $channel->max_revisions,
                            'note' => form_label(
                                form_checkbox('clear_versioning_data', 'y')
                                . lang('clear_versioning_data')
                            )
                        )
                    )
                )
            ),
            'notifications' => array(
                array(
                    'title' => 'enable_author_notification',
                    'desc' => 'enable_author_notification_desc',
                    'fields' => array(
                        'comment_notify_authors' => array(
                            'type' => 'yes_no',
                            'value' => $channel->comment_notify_authors
                        )
                    )
                ),
                array(
                    'title' => 'enable_channel_entry_notification',
                    'desc' => 'enable_channel_entry_notification_desc',
                    'fields' => array(
                        'channel_notify' => array(
                            'type' => 'yes_no',
                            'value' => $channel->channel_notify
                        ),
                        'channel_notify_emails' => array(
                            'type' => 'text',
                            'value' => $channel->channel_notify_emails
                        )
                    )
                ),
                array(
                    'title' => 'enable_comment_notification',
                    'desc' => 'enable_comment_notification_desc',
                    'fields' => array(
                        'comment_notify' => array(
                            'type' => 'yes_no',
                            'value' => $channel->comment_notify
                        ),
                        'comment_notify_emails' => array(
                            'type' => 'text',
                            'value' => $channel->comment_notify_emails
                        )
                    )
                )
            ),
            'commenting' => array(
                array(
                    'title' => 'allow_comments',
                    'desc' => 'allow_comments_desc',
                    'fields' => array(
                        'comment_system_enabled' => array(
                            'type' => 'yes_no',
                            'value' => $channel->comment_system_enabled
                        )
                    )
                ),
                array(
                    'title' => 'allow_comments_checked',
                    'desc' => 'allow_comments_checked_desc',
                    'fields' => array(
                        'deft_comments' => array(
                            'type' => 'yes_no',
                            'value' => $channel->deft_comments
                        )
                    )
                ),
                array(
                    'title' => 'require_membership',
                    'desc' => 'require_membership_desc',
                    'fields' => array(
                        'comment_require_membership' => array(
                            'type' => 'yes_no',
                            'value' => $channel->comment_require_membership
                        )
                    )
                ),
                array(
                    'title' => 'require_email',
                    'desc' => 'require_email_desc',
                    'fields' => array(
                        'comment_require_email' => array(
                            'type' => 'yes_no',
                            'value' => $channel->comment_require_email
                        )
                    )
                ),
                array(
                    'title' => 'moderate_comments',
                    'desc' => 'moderate_comments_desc',
                    'fields' => array(
                        'comment_moderate' => array(
                            'type' => 'yes_no',
                            'value' => $channel->comment_moderate
                        )
                    )
                ),
                array(
                    'title' => 'max_characters',
                    'desc' => 'max_characters_desc',
                    'fields' => array(
                        'comment_max_chars' => array(
                            'type' => 'text',
                            'value' => $channel->comment_max_chars
                        )
                    )
                ),
                array(
                    'title' => 'comment_time_limit',
                    'desc' => 'comment_time_limit_desc',
                    'fields' => array(
                        'comment_timelock' => array(
                            'type' => 'text',
                            'value' => $channel->comment_timelock
                        )
                    )
                ),
                array(
                    'title' => 'comment_expiration',
                    'desc' => 'comment_expiration_desc',
                    'fields' => array(
                        'comment_expiration' => array(
                            'type' => 'text',
                            'value' => $channel->comment_expiration,
                            'note' => form_label(
                                form_checkbox('apply_expiration_to_existing', 'y')
                                . lang('apply_expiration_to_existing')
                            )
                        )
                    )
                ),
                array(
                    'title' => 'text_formatting',
                    'desc' => 'text_formatting_desc',
                    'fields' => array(
                        'comment_text_formatting' => array(
                            'type' => 'radio',
                            'choices' => $comment_text_formatting_options,
                            'value' => $channel->comment_text_formatting
                        )
                    )
                ),
                array(
                    'title' => 'html_formatting',
                    'fields' => array(
                        'comment_html_formatting' => array(
                            'type' => 'radio',
                            'choices' => $comment_html_formatting_options,
                            'value' => $channel->comment_html_formatting
                        )
                    )
                ),
                array(
                    'title' => 'convert_image_urls',
                    'desc' => 'comment_convert_image_urls_desc',
                    'fields' => array(
                        'comment_allow_img_urls' => array(
                            'type' => 'yes_no',
                            'value' => $channel->comment_allow_img_urls
                        )
                    )
                ),
                array(
                    'title' => 'convert_urls_emails_to_links',
                    'desc' => 'comment_convert_urls_emails_to_links_desc',
                    'fields' => array(
                        'comment_auto_link_urls' => array(
                            'type' => 'yes_no',
                            'value' => $channel->comment_auto_link_urls
                        )
                    )
                )
            )
        );

        if (ee('pro:Access')->hasRequiredLicense() && (ee()->config->item('enable_entry_cloning') === false || ee()->config->item('enable_entry_cloning') === 'y')) {
            $sections['publishing'][] = array(
                'title' => 'enable_entry_cloning',
                'desc' => 'enable_entry_cloning_desc',
                'fields' => array(
                    'enable_entry_cloning' => array(
                        'type' => 'yes_no',
                        'value' => $channel->enable_entry_cloning
                    )
                )
            );
        }

        $html = '';

        foreach ($sections as $name => $settings) {
            $html .= ee('View')->make('_shared/form/section')
                ->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
        }

        return $html;
    }

    private function getPermissionKeys()
    {
        return [
            'can_create_entries_channel_id_' => lang('can_create_entries'),
            'can_edit_self_entries_channel_id_' => lang('can_edit_self_entries'),
            'can_delete_self_entries_channel_id_' => lang('can_delete_self_entries'),
            'can_edit_other_entries_channel_id_' => lang('can_edit_other_entries'),
            'can_delete_all_entries_channel_id_' => lang('can_delete_all_entries'),
            'can_assign_post_authors_channel_id_' => lang('can_assign_post_authors')
        ];
    }

    /**
     * Renders the Permissions tab for the Channel create/edit form
     *
     * @param Channel $channel A Channel entity
     * @param null|ValidationResult $errors NULL (if nothing was submitted) or
     *   a ValidationResult object. This is needed to render any inline erorrs
     *   on the form.
     * @return string HTML
     */
    private function renderPermissionsTab($channel, $errors)
    {
        // Not a superadmin?  Preselect their roles
        if ($channel->isNew()) {
            $selected_roles = [];

            if (! ee('Permission')->isSuperAdmin()) {
                $ids = ee()->session->getMember()->getAllRoles()->pluck('role_id');
                $selected_roles = ee('Model')->get('Role', $ids)->all();
            }
        } else {
            $selected_roles = $channel->AssignedRoles;
        }

        $perms = $this->getPermissionKeys();

        $roles = ee('Model')->get('Role')
            ->filter('role_id', '>', 4)
            ->order('name', 'asc')
            ->all();

        $choices = [];
        $values = [];

        foreach ($roles as $role) {
            $children = [];
            foreach ($perms as $perm => $lang) {
                $children[$perm . ':role_id_' . $role->getId()] = $lang;
            }

            $choices['role_id_' . $role->getId()] = [
                'label' => $role->name,
                'children' => $children
            ];
        }

        foreach ($selected_roles as $role) {
            $values[] = 'role_id_' . $role->getId();

            foreach ($perms as $perm => $lang) {
                if ($channel->isNew()) {
                    $values[] = $perm . ':role_id_' . $role->getId();
                } else {
                    if ($role->has($perm . $channel->getId())) {
                        $values[] = $perm . ':role_id_' . $role->getId();
                    }
                }
            }
        }

        $section = [
            [
                'title' => 'channel_roles',
                'desc' => 'channel_roles_desc',
                'fields' => [
                    'roles' => [
                        'type' => 'checkbox',
                        'required' => true,
                        'nested' => true,
                        'auto_select_parents' => true,
                        'choices' => $choices,
                        'value' => $values,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('roles'))
                        ]
                    ]
                ]
            ]
        ];

        return ee('View')->make('_shared/form/section')
            ->render(array('name' => null, 'settings' => $section, 'errors' => $errors));
    }

    /**
     * AJAX endpoint for author list filtering
     *
     * @return array ID => Screen name array of authors
     */
    public function authorList($search = null)
    {
        if (!empty(ee('Request')->get('search'))) {
            $search = ee('Request')->get('search');
        }
        $authors = ee('Member')->getAuthors($search);

        if (AJAX_REQUEST) {
            return ee('View/Helpers')->normalizedChoices($authors);
        }

        return $authors;
    }

    /**
     * Sets channel object data with normalized POST values
     *
     * @param Channel $channel A Channel entity
     * @return Modifed Channel entity
     */
    private function setWithPost($channel)
    {
        $categoryGroups = [];
        if (isset($_POST['cat_group']) && is_array($_POST['cat_group'])) {
            $categoryGroups = ee('Request')->post('cat_group');
            $_POST['cat_group'] = implode('|', array_filter($_POST['cat_group'], 'is_numeric'));
        } else {
            $_POST['cat_group'] = '';
        }

        if (! ee('Request')->post('comment_expiration')) {
            $_POST['comment_expiration'] = 0;
        }
        $channel->set($_POST);

        $channel->CategoryGroups = ee('Model')->get('CategoryGroup', $categoryGroups)->all();
        $channel->FieldGroups = ee('Model')->get('ChannelFieldGroup', ee()->input->post('field_groups'))->all();
        $channel->CustomFields = ee('Model')->get('ChannelField', ee()->input->post('custom_fields'))->all();

        // Make sure these are the correct NULL value if they are not set.
        $channel->Statuses = ee('Model')->get('Status', ee()->input->post('statuses'))->all();

        foreach (['max_entries', 'max_revisions', 'comment_max_chars', 'comment_timelock'] as $field) {
            if ($channel->$field == '') {
                $channel->$field = 0;
            }
        }

        if ($channel->ChannelFormSettings === null) {
            $channel->ChannelFormSettings = ee('Model')->make('ChannelFormSettings');
            $channel->ChannelFormSettings->site_id = ee()->config->item('site_id');
        }

        $channel->ChannelFormSettings->default_status = ee('Request')->post('default_status');
        $channel->ChannelFormSettings->allow_guest_posts = ee('Request')->post('allow_guest_posts');
        $channel->ChannelFormSettings->default_author = ee('Request')->post('default_author') ?: 0;

        $roles = ee('Request')->post('roles');

        if (! $roles) {
            $channel->AssignedRoles = [];
        } else {
            $role_ids = [];
            foreach ($roles as $value) {
                if (strpos($value, 'role_id_') === 0) {
                    $role_ids[] = str_replace('role_id_', '', $value);
                }
            }

            $channel->AssignedRoles = ee('Model')->get('Role', $role_ids)->all();
        }

        return $channel;
    }

    /**
     * Channel preference submission handler, copied from old
     * admin_content controller
     *
     * This function receives the submitted channel preferences
     * and stores them in the database.
     */
    private function saveChannel($channel)
    {
        ee()->load->model('channel_model');

        if (ee('Request')->post('apply_expiration_to_existing')) {
            if (ee('Request')->post('comment_expiration') == 0) {
                ee()->channel_model->update_comment_expiration(
                    $channel->getId(),
                    0,
                    true
                );
            } else {
                ee()->channel_model->update_comment_expiration(
                    $channel->getId(),
                    ee('Request')->post('comment_expiration') * 86400
                );
            }
        }

        if (ee('Request')->post('clear_versioning_data')) {
            ee()->channel_model->clear_versioning_data($channel->getId());
        }

        // Set category group settings
        $this->setCategoryGroupSettings($channel);

        // Create Channel
        if ($channel->isNew()) {
            $channel->default_entry_title = $channel->default_entry_title ?: '';
            $channel->url_title_prefix = $channel->url_title_prefix ?: '';
            $channel->channel_url = $channel->channel_url ?: ee()->functions->fetch_site_index();
            $channel->channel_lang = $channel->channel_lang ?: ee()->config->item('xml_lang');
            $channel->site_id = $channel->site_id ?: ee()->config->item('site_id');

            $dupe_id = ee()->input->post('duplicate_channel_prefs');
            unset($_POST['duplicate_channel_prefs']);

            // duplicating preferences?
            if (! empty($dupe_id) && is_numeric($dupe_id)) {
                $dupe_channel = ee('Model')->get('Channel')
                    ->filter('channel_id', $dupe_id)
                    ->first();
                $channel->duplicatePreferences($dupe_channel);
            }

            $channel->save();

            // If they made the channel?  Give access to that channel to the member group?
            // If member group has ability to create the channel, they should be
            // able to access it as well
            if (! ee('Permission')->isSuperAdmin()) {
                $data = array(
                    'role_id' => ee()->session->getMember()->PrimaryRole->getId(),
                    'channel_id' => $channel->channel_id
                );

                ee()->db->insert('channel_member_roles', $data);
            }

            $success_msg = lang('channel_created');

            ee()->logger->log_action($success_msg . NBS . NBS . $_POST['channel_title']);
        } else {
            $channel->save();
        }

        if (ee('Request')->post('update_versioning')) {
            ee('Channel/ChannelEntry')->updateVersioning($channel->getId(), ee('Request')->post('enable_versioning'));
        }

        $perms = [];
        foreach ($this->getPermissionKeys() as $perm => $lang) {
            $perms[] = $perm . $channel->getId();
        }

        ee('Model')->get('Permission')
            ->filter('permission', 'IN', $perms)
            ->filter('site_id', ee()->config->item('site_id'))
            ->delete();

        foreach (ee('Request')->post('roles') as $value) {
            if (empty($value)) {
                continue;
            }

            if (strpos($value, 'role_id_') !== 0) {
                list($permission, $role_id) = explode(':role_id_', $value);

                ee('Model')->make('Permission', [
                    'role_id' => $role_id,
                    'site_id' => ee()->config->item('site_id'),
                    'permission' => $permission . $channel->getId()
                ])->save();
            }
        }

        return $channel;
    }
}

// EOF
