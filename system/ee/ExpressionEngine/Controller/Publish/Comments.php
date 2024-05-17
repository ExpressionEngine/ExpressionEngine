<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Publish;

use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use ExpressionEngine\Service\Model\Query\Builder;

/**
 * Publish/Comments Controller
 */
class Comments extends AbstractPublishController
{
    public function __construct()
    {
        parent::__construct();

        if (
            ! ee('Permission')->hasAny(
                'can_moderate_comments',
                'can_edit_own_comments',
                'can_delete_own_comments',
                'can_edit_all_comments',
                'can_delete_all_comments'
            )
        ) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * Displays all available comments
     *
     * @return void
     */
    public function index()
    {
        if (ee()->input->post('bulk_action')) {
            $this->performBulkActions();
            ee()->functions->redirect(ee('CP/URL')->make('publish/comments', ee()->cp->get_url_state()));
        }

        $vars = array(
            'can_delete' => ee('Permission')->can('delete_all_comments') && ee('Permission')->can('delete_own_comments'),
            'can_moderate' => ee('Permission')->can('moderate_comments'),
        );

        $channel = null;
        $base_url = ee('CP/URL')->make('publish/comments');

        $comments = ee('Model')->get('Comment')
            ->filter('site_id', ee()->config->item('site_id'));

        $channel_filter = ee('CP/EntryListing', ee()->input->get_post('filter_by_keyword'))->createChannelFilter();
        if ($channel_filter->value()) {
            $comments->filter('channel_id', $channel_filter->value());
            $channel = ee('Model')->get('Channel', $channel_filter->value())->first();
        }

        $status_filter = $this->createStatusFilter();
        if ($status_filter->value()) {
            $comments->filter('status', $status_filter->value());
        }

        // never show Spam here, that needs to be dealt with in the Spam module
        if (ee('Addon')->get('spam') && ee('Addon')->get('spam')->isInstalled()) {
            $comments->filter('status', '!=', 's');
        }

        $search_value = htmlentities(ee()->input->get_post('filter_by_keyword'), ENT_QUOTES, 'UTF-8');
        if (! empty($search_value)) {
            $base_url->setQueryStringVariable('filter_by_keyword', $search_value);
            $comments->filter('comment', 'LIKE', '%' . ee()->db->escape_like_str($search_value) . '%');
        }

        if (ee('Request')->get('comment_id')) {
            $comments->filter('comment_id', ee('Request')->get('comment_id'));
        }

        $filters = ee('CP/Filter')
            ->add($channel_filter)
            ->add($status_filter)
            ->add('Date')
            ->add('Keyword');

        $filter_values = $filters->values();

        if (! empty($filter_values['filter_by_date'])) {
            if (is_array($filter_values['filter_by_date'])) {
                $comments->filter('comment_date', '>=', $filter_values['filter_by_date'][0]);
                $comments->filter('comment_date', '<', $filter_values['filter_by_date'][1]);
            } else {
                $comments->filter('comment_date', '>=', ee()->localize->now - $filter_values['filter_by_date']);
            }
        }

        $count = $comments->count();

        // Add this last to get the right $count
        $filters->add('Perpage', $count, 'all_entries');

        ee()->view->filters = $filters->render($base_url);

        $filter_values = $filters->values();
        $base_url->addQueryStringVariables($filter_values);

        $page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

        $comments->limit($filter_values['perpage'])
            ->offset($offset);

        $table = $this->buildTableFromCommentQuery($comments);

        $vars['table'] = $table->viewData($base_url);
        $vars['form_url'] = $vars['table']['base_url'];

        $vars['pagination'] = ee('CP/Pagination', $count)
            ->perPage($filter_values['perpage'])
            ->currentPage($page)
            ->render($base_url);

        ee()->javascript->set_global('lang.remove_confirm', lang('comment') . ': <b>### ' . lang('comments') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/confirm_remove',
            ),
        ));

        ee()->view->cp_breadcrumbs = array(
            '' => lang('comments')
        );

        // if there are Spam comments, and the user can access them, give them a link
        if (ee('Permission')->can('moderate_spam') && ee('Addon')->get('spam') && ee('Addon')->get('spam')->isInstalled()) {
            $spam_total = ee('Model')->get('Comment')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('status', 's')
                ->count();

            if ($spam_total > 0) {
                $spam_link = ee('CP/URL')->make('addons/settings/spam', array('content_type' => 'comment'));

                ee('CP/Alert')->makeInline('comments-form')
                    ->asWarning()
                    ->withTitle(lang('spam_comments_header'))
                    ->addToBody(sprintf(lang('spam_comments'), $spam_total, $spam_link))
                    ->now();
            }
        }

        ee()->view->cp_page_title = lang('comments');

        ee()->view->header = array(
            'title' => lang('comments'),
            'toolbar_items' => array(
                'settings' => array(
                    'href' => ee('CP/URL')->make('settings/comments'),
                    'title' => lang('comment_settings')
                ),
            ),
            'action_button' => null
        );

        // Set the page heading
        if (! empty($search_value)) {
            ee()->view->cp_heading = sprintf(lang('search_results_heading'), $count, $search_value);
        } else {
            ee()->view->cp_heading = sprintf(
                lang('all_comments'),
                (!is_null($channel)) ? $channel->channel_title : ''
            );
        }

        ee()->cp->render('publish/comments/index', $vars);
    }

    /**
     * Dilsplays all comments for a given entry
     *
     * @param int $entry_id The ID# of the entry in question
     * @return void
     */
    public function entry($entry_id)
    {
        if (ee()->input->post('bulk_action')) {
            $this->performBulkActions();
            ee()->functions->redirect(ee('CP/URL')->make('publish/comments/entry/' . $entry_id, ee()->cp->get_url_state()));
        }

        $vars = array();
        $base_url = ee('CP/URL')->make('publish/comments/entry/' . $entry_id);

        $entry = ee('Model')->get('ChannelEntry', $entry_id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $entry) {
            show_error(lang('no_entries_matching_that_criteria'));
        }

        $comments = ee('Model')->get('Comment')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('entry_id', $entry_id);

        $status_filter = $this->createStatusFilter();
        if ($status_filter->value()) {
            $comments->filter('status', $status_filter->value());
        }

        // never show Spam here, that needs to be dealt with in the Spam module
        if (ee('Addon')->get('spam') && ee('Addon')->get('spam')->isInstalled()) {
            $comments->filter('status', '!=', 's');
        }

        $search_value = htmlentities(ee()->input->get_post('filter_by_keyword'), ENT_QUOTES, 'UTF-8');
        if (! empty($search_value)) {
            $base_url->setQueryStringVariable('filter_by_keyword', $search_value);
            $comments->filter('comment', 'LIKE', '%' . ee()->db->escape_like_str($search_value) . '%');
        }

        $filters = ee('CP/Filter')
            ->add($status_filter)
            ->add('Date')
            ->add('Keyword');

        $filter_values = $filters->values();

        if (! empty($filter_values['filter_by_date'])) {
            if (is_array($filter_values['filter_by_date'])) {
                $comments->filter('comment_date', '>=', $filter_values['filter_by_date'][0]);
                $comments->filter('comment_date', '<', $filter_values['filter_by_date'][1]);
            } else {
                $comments->filter('comment_date', '>=', ee()->localize->now - $filter_values['filter_by_date']);
            }
        }

        $count = $comments->count();

        // Add this last to get the right $count
        $filters->add('Perpage', $count, 'all_entries');

        ee()->view->filters = $filters->render($base_url);

        $filter_values = $filters->values();
        $base_url->addQueryStringVariables($filter_values);

        $page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

        $comments->limit($filter_values['perpage'])
            ->offset($offset);

        $table = $this->buildTableFromCommentQuery($comments);

        $vars['table'] = $table->viewData($base_url);
        $vars['form_url'] = $vars['table']['base_url'];

        $vars['pagination'] = ee('CP/Pagination', $count)
            ->perPage($filter_values['perpage'])
            ->currentPage($page)
            ->render($base_url);

        ee()->javascript->set_global('lang.remove_confirm', lang('comment') . ': <b>### ' . lang('comments') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/confirm_remove',
            ),
        ));

        // if there are Spam comments for this entry, and the user can access them, give them a link
        if (ee('Permission')->can('moderate_spam') && ee('Addon')->get('spam') && ee('Addon')->get('spam')->isInstalled()) {
            $spam_total = ee('Model')->get('Comment')
                ->filter('site_id', ee()->config->item('site_id'))
                ->filter('status', 's')
                ->filter('entry_id', $entry_id)
                ->count();

            if ($spam_total > 0) {
                $spam_link = ee('CP/URL')->make('addons/settings/spam', array('content_type' => 'comment'));

                ee('CP/Alert')->makeInline('comments-form')
                    ->asWarning()
                    ->withTitle(lang('spam_comments_header'))
                    ->addToBody(sprintf(lang('spam_comments'), $spam_total, $spam_link))
                    ->now();
            }
        }

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('publish/comments')->compile() => lang('comments'),
            ee('CP/URL')->make('publish/comments/entry/' . $entry->entry_id)->compile() => lang('entry')
        );

        ee()->view->cp_page_title = lang('comments');
        ee()->view->header = array(
            'title' => lang('comments')
        );

        // Set the page heading
        if (! empty($search_value)) {
            ee()->view->cp_heading = sprintf(lang('search_results_comments_for_entry'), ee('CP/URL', 'publish/edit/entry/' . $entry->entry_id), $entry->title, $count, htmlentities($search_value));
        } else {
            ee()->view->cp_heading = sprintf(lang('all_comments_for_entry'), ee('CP/URL', 'publish/edit/entry/' . $entry->entry_id), $entry->title);
        }

        $vars['can_delete'] = ee('Permission')->hasAny(
            'can_delete_own_comments',
            'can_delete_all_comments'
        );
        $vars['can_moderate'] = ee('Permission')->can('moderate_comments');

        ee()->cp->render('publish/comments/index', $vars);
    }

    public function edit($comment_id)
    {
        if (
            ! ee('Permission')->can('edit_all_comments') &&
            ! ee('Permission')->can('edit_own_comments') &&
            ! ee('Permission')->can('moderate_comments')
        ) {
            show_error(lang('unauthorized_access'), 403);
        }

        $comment = ee('Model')->get('Comment', $comment_id)
            ->with('Author')
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $comment) {
            show_error(lang('no_comments'));
        }

        // this form lets you edit, moderate, or both. Set permissions
        $can_edit = false;
        $can_moderate = ee('Permission')->can('moderate_comments');

        if (
            ee('Permission')->can('edit_all_comments') or
            ($comment->author_id == ee()->session->userdata('member_id') && ee('Permission')->can('edit_own_comments'))
        ) {
            $can_edit = true;
        }

        $author_information = ee('View')->make('publish/comments/partials/author_information')
            ->render(array('comment' => $comment));

        $title = $comment->getEntry()->title;

        if ($comment->Channel->preview_url) {
            $uri = str_replace(['{url_title}', '{entry_id}'], [$comment->Entry->url_title, $comment->Entry->entry_id], $comment->Channel->preview_url);
            $view_url = ee()->functions->create_url($uri);
            $title = '<a href="' . ee()->cp->masked_url($view_url) . '" rel="external">' . $title . '</a>';
        }

        $move_desc = sprintf(
            lang('move_comment_desc'),
            ee('CP/URL')->make('publish/edit/entry/' . $comment->getEntry()->getId()),
            $title,
            ee('CP/URL')->make('publish/edit/', ['filter_by_channel' => $comment->getChannel()->getId()]),
            $comment->getChannel()->channel_title
        );

        // we whitelist these sections based on the permissions, so everything starts out disabled
        $vars = [
            'ajax_validate' => true,
            'base_url' => ee('CP/URL')->make('publish/comments/edit/' . $comment_id),
            'save_btn_text' => 'btn_edit_comment',
            'save_btn_text_working' => 'btn_saving',
            'sections' => [
                [
                    'author' => [
                        'title' => 'author_information',
                        'desc' => 'author_information_desc',
                        'fields' => [
                            'author' => [
                                'type' => 'html',
                                'content' => $author_information,
                            ]
                        ]
                    ],
                    'date' => [
                        'title' => 'date',
                        'fields' => [
                            'author' => [
                                'type' => 'html',
                                'content' => ee()->localize->human_time($comment->comment_date),
                            ]
                        ]
                    ],
                    'status' => [
                        'title' => 'status',
                        'desc' => 'comment_status_desc',
                        'fields' => [
                            'status' => [
                                'type' => 'radio',
                                'choices' => [
                                    'o' => lang('open'),
                                    'c' => lang('closed'),
                                    'p' => lang('pending'),
                                    's' => lang('spam')
                                ],
                                'value' => $comment->status,
                                'disabled' => true,
                            ]
                        ]
                    ],
                    'comment' => [
                        'title' => 'comment_content',
                        'desc' => 'comment_content_desc',
                        'fields' => [
                            'comment' => [
                                'type' => 'textarea',
                                'value' => $comment->comment,
                                'attrs' => 'class="textarea--large"',
                                'required' => false,
                                'disabled' => true,
                            ]
                        ]
                    ],
                    'move' => [
                        'title' => 'move_comment',
                        'desc' => $move_desc,
                        'fields' => [
                            'move' => [
                                'type' => 'text',
                                'disabled' => true,
                            ],
                        ]
                    ],
                ]
            ]
        ];

        $rules = [];

        if ($can_edit) {
            $vars['sections'][0]['comment']['fields']['comment']['required'] = true;
            $vars['sections'][0]['comment']['fields']['comment']['disabled'] = false;

            $rules[] = [
                'field' => 'comment',
                'label' => 'lang:comment',
                'rules' => 'required'
            ];
        }

        if ($can_moderate) {
            $vars['sections'][0]['status']['fields']['status']['disabled'] = false;
            $vars['sections'][0]['move']['fields']['move']['disabled'] = false;

            $rules[] = [
                'field' => 'status',
                'label' => 'lang:status',
                'rules' => 'enum[o,c,p,s]'
            ];

            $rules[] = [
                'field' => 'move',
                'label' => 'lang:move',
                'rules' => 'is_natural'
            ];
        }

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules($rules);

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            if ($can_edit) {
                $comment->comment = ee()->input->post('comment');
            }

            if ($can_moderate) {
                $origStatus = $comment->status;

                $comment->status = ee()->input->post('status');

                if (ee()->input->post('move')) {
                    $comment->entry_id = ee()->input->post('move');
                }

                //changing status to spam?
                if ($origStatus != ee()->input->post('status') && ee()->input->post('status') == 's') {
                    ee('Spam')->moderate('comment', $comment, $comment->comment, [], $comment->author_id);
                }
            }

            $comment->save();

            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('edit_comment_success'))
                ->addToBody(lang('edit_comment_success_desc'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('publish/comments/edit/' . $comment_id));
        } elseif (ee()->form_validation->errors_exist()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('edit_comment_error'))
                ->addToBody(lang('edit_comment_error_desc'))
                ->now();
        }

        ee()->view->cp_page_title = lang('edit_comment');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('publish/edit')->compile() => lang('entries'),
            //ee('CP/URL')->make('publish/edit', array('filter_by_channel' => $comment->Channel->getId()))->compile() => $comment->Channel->channel_title,
            ee('CP/URL')->make('publish/comments/entry/' . $comment->Entry->getId())->compile() => lang('comments'),
            '' => lang('edit_comment')
        );
        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Builds a Table object from a Query of Comment model entitites
     *
     * @param Builder $comments A Query\Builder object for Comment model entities
     * @return Table A Table instance
     */
    private function buildTableFromCommentQuery(Builder $comments)
    {
        ee()->load->helper('text');
        $table = ee('CP/Table', array(
            'sort_dir' => 'desc',
            'sort_col' => 'column_comment_date',
        ));

        $table->setColumns(
            array(
                'column_comment' => array(
                    'encode' => false
                ),
                'column_comment_date',
                'column_ip_address',
                'column_status' => array(
                    'type' => Table::COL_STATUS
                ),
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                ),
                array(
                    'type' => Table::COL_CHECKBOX
                )
            )
        );
        $table->setNoResultsText(lang('no_comments'));

        $comments->order(str_replace('column_', '', $table->sort_col), $table->sort_dir);

        $data = array();

        $comment_id = ee()->session->flashdata('comment_id');

        foreach ($comments->all() as $comment) {
            switch ($comment->status) {
                case 'o':
                    $status = lang('open');

                    break;
                case 'c':
                    $status = lang('closed');

                    break;
                case 's':
                    $status = lang('spam');

                    break;
                default:
                    $status = lang("pending");
            }

            $toolbar = array();

            // You get an edit button if you can edit all comments or you can
            // edit your own comments and this comment is one of yours
            if (
                ee('Permission')->can('edit_all_comments')
                || (ee('Permission')->can('edit_own_comments') && $comment->author_id == ee()->session->userdata('member_id'))
            ) {
                $toolbar = array(
                    'edit' => array(
                        'href' => ee('CP/URL')->make('publish/comments/edit/' . $comment->comment_id),
                        'title' => lang('edit')
                    )
                );
            }

            $column = array(
                ee('View')->make('publish/comments/partials/title')->render(array('comment' => $comment)),
                ee()->localize->human_time($comment->comment_date),
                $comment->ip_address,
                $status,
                array('toolbar_items' => $toolbar),
                array(
                    'name' => 'selection[]',
                    'value' => $comment->comment_id,
                    'data' => array(
                        'confirm' => lang('comment') . ': <b>' . htmlentities(ellipsize($comment->comment, 50), ENT_QUOTES, 'UTF-8') . '</b>'
                    )
                )
            );

            $attrs = array();

            if ($comment_id && $comment->comment_id == $comment_id) {
                $attrs = array('class' => 'selected');
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => $column
            );
        }
        $table->setData($data);

        return $table;
    }

    private function createStatusFilter()
    {
        $status = ee('CP/Filter')->make('filter_by_status', 'filter_by_status', array(
            'o' => lang('open'),
            'c' => lang('closed'),
            'p' => lang('pending')
        ));
        $status->disableCustomValue();

        return $status;
    }

    private function performBulkActions()
    {
        switch (ee()->input->post('bulk_action')) {
            case 'remove':
                $this->remove(ee()->input->post('selection'));

                break;

            case 'open':
                $this->setStatus(ee()->input->post('selection'), 'o');

                break;

            case 'closed':
                $this->setStatus(ee()->input->post('selection'), 'c');

                break;

            case 'pending':
                $this->setStatus(ee()->input->post('selection'), 'p');

                break;
        }
    }

    private function remove($comment_ids)
    {
        // Cannot remove if you cannot edit
        if (
            ! ee('Permission')->can('delete_all_comments')
            && ! ee('Permission')->can('delete_own_comments')
        ) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($comment_ids)) {
            $comment_ids = array($comment_ids);
        }

        $comments = ee('Model')->get('Comment', $comment_ids)
            ->filter('site_id', ee()->config->item('site_id'));

        if (
            ! ee('Permission')->can('delete_all_comments')
            && ee('Permission')->can('delete_own_comments')
        ) {
            $comments->filter('author_id', ee()->session->userdata('member_id'));
        }

        $comment_names = array();

        ee()->load->helper('text');

        foreach ($comments->all() as $comment) {
            $comment_names[] = ellipsize($comment->comment, 50);
        }

        $comments->delete();

        ee('CP/Alert')->makeInline('comments-form')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(lang('comments_deleted_desc'))
            ->addToBody($comment_names)
            ->defer();
    }

    private function setStatus($comment_ids, $status)
    {
        if (! ee('Permission')->can('moderate_comments')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($comment_ids)) {
            $comment_ids = array($comment_ids);
        }

        $comments = ee('Model')->get('Comment', $comment_ids)
            ->filter('site_id', ee()->config->item('site_id'))
            ->all();

        ee()->load->helper('text');
        $comment_names = array();

        foreach ($comments as $comment) {
            $comment->status = $status;
            $comment->save();

            $comment_names[] = ellipsize($comment->comment, 50);
        }

        switch ($status) {
            case 'o':
                $status = lang('open');

                break;
            case 'c':
                $status = lang('closed');

                break;
            default:
                $status = lang("pending");
        }

        ee('CP/Alert')->makeInline('comments-form')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(sprintf(lang('comments_status_updated_desc'), strtolower($status)))
            ->addToBody($comment_names)
            ->defer();
    }
}

// EOF
