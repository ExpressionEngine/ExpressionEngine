<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members\Profile;

use CP_Controller;
use ExpressionEngine\Library\CP\Table;

/**
 * Member Profile Bookmarks Settings Controller
 */
class Bookmarks extends Settings
{
    private $base_url = 'members/profile/bookmarks';
    public $bookmarks;
    private $index_url;

    public function __construct()
    {
        parent::__construct();
        $this->bookmarks = array();
        $bookmarks = is_string($this->member->bookmarklets) ? (array) json_decode($this->member->bookmarklets) : array();

        foreach ($bookmarks as $bookmark) {
            $this->bookmarks[] = $bookmark;
        }

        $this->index_url = $this->base_url;
        $this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);
    }

    /**
     * Bookmarks index
     */
    public function index()
    {
        $table = ee('CP/Table');
        $links = array();
        $data = array();

        foreach ($this->bookmarks as $id => $bookmark) {
            $toolbar = array('toolbar_items' => array(
                'edit' => array(
                    'href' => ee('CP/URL')->make('members/profile/bookmarks/edit/' . $id, $this->query_string),
                    'title' => strtolower(lang('edit'))
                )
            ));

            $path = ee()->config->item('cp_url') . '?/cp/publish/create/' . $bookmark->channel;
            $path .= '&BK=1&';

            $type = (isset($_POST['safari'])) ? "window.getSelection()" : "document.selection?document.selection.createRange().text:document.getSelection()";
            $link = "bm=$type;void(bmentry=window.open('" . $path . "title='+encodeURI(document.title)+'&field_id_" . $bookmark->field . "='+encodeURI(bm),'bmentry',''))";
            $link = 'javascript:' . urlencode($link);

            $links[] = array(
                'name' => "<a href='$link'>" . htmlentities($bookmark->name, ENT_QUOTES, 'UTF-8') . "</a>",
                $toolbar,
                array(
                    'name' => 'selection[]',
                    'value' => $id,
                    'data' => array(
                        'confirm' => lang('bookmarklet') . ': <b>' . htmlentities($bookmark->name, ENT_QUOTES, 'UTF-8') . '</b>'
                    )
                )
            );
        }

        $table->setColumns(
            array(
                'name' => array(
                    'encode' => false
                ),
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                ),
                array(
                    'type' => Table::COL_CHECKBOX
                )
            )
        );

        $table->setNoResultsText('no_bookmarklets_found');
        $table->setData($links);

        $data['table'] = $table->viewData($this->base_url);
        $data['new'] = ee('CP/URL')->make('members/profile/bookmarks/create', $this->query_string)->compile();
        $data['form_url'] = ee('CP/URL')->make('members/profile/bookmarks/delete', $this->query_string)->compile();

        ee()->javascript->set_global('lang.remove_confirm', lang('bookmarks') . ': <b>### ' . lang('bookmarks') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array('cp/confirm_remove'),
        ));

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('bookmarklets');

        ee()->view->cp_breadcrumbs = array_merge($this->breadcrumbs, [
            '' => lang('bookmarklets')
        ]);

        ee()->cp->render('account/bookmarks', $data);
    }

    /**
     * Create new bookmark
     *
     * @access public
     * @return void
     */
    public function create()
    {
        $this->base_url = ee('CP/URL')->make($this->index_url . '/create', $this->query_string);

        $vars = array(
            'cp_page_title' => lang('create_bookmarklet')
        );

        $id = count($this->bookmarks) ?: 0;

        if (! empty($_POST)) {
            $order = count($this->bookmarks) + 1;
            $this->bookmarks[$order] = array(
                'name' => ee()->input->post('name'),
                'channel' => ee()->input->post('channel'),
                'field' => ee()->input->post('field')
            );
        }

        ee()->view->cp_breadcrumbs = array_merge($this->breadcrumbs, [
            ee('CP/URL')->make($this->index_url, $this->query_string)->compile() => lang('bookmarklets'),
            '' => lang('create')
        ]);

        $this->form($vars, ! empty($_POST) ? $_POST : array(), $id);
    }

    /**
     * Edit bookmark
     *
     * @param int $id  The ID of the bookmark to be updated
     * @access public
     * @return void
     */
    public function edit($id)
    {
        $this->base_url = ee('CP/URL')->make($this->index_url . "/edit/$id", $this->query_string);

        $vars = array(
            'cp_page_title' => lang('edit_bookmarklet')
        );

        if (! empty($_POST)) {
            $this->bookmarks[$id] = new \stdClass();
            $this->bookmarks[$id]->name = ee()->input->post('name');
            $this->bookmarks[$id]->channel = ee()->input->post('channel');
            $this->bookmarks[$id]->field = ee()->input->post('field');
        }

        $values = array(
            'name' => $this->bookmarks[$id]->name,
            'channel' => $this->bookmarks[$id]->channel,
            'field' => $this->bookmarks[$id]->field
        );

        ee()->view->cp_breadcrumbs = array_merge($this->breadcrumbs, [
            ee('CP/URL')->make($this->index_url, $this->query_string)->compile() => lang('bookmarklets'),
            '' => lang('edit')
        ]);

        $this->form($vars, $values, $id);
    }

    /**
     * Delete Bookmarks
     *
     * @access public
     * @return void
     */
    public function delete()
    {
        $selection = array_map(function ($x) {
            return (string) $x;
        }, ee()->input->post('selection'));
        $this->bookmarks = array_diff_key($this->bookmarks, array_flip($selection));
        $this->saveBookmarks();

        ee()->functions->redirect(ee('CP/URL')->make($this->index_url, $this->query_string));
    }

    /**
     * saveBookmarks compiles the links and saves them for the current member
     *
     * @access private
     * @return bool
     */
    private function saveBookmarks()
    {
        $this->member->bookmarklets = json_encode($this->bookmarks);
        $this->member->save();

        return true;
    }

    /**
     * Display a generic form for creating/editing a bookmark
     *
     * @param mixed $vars
     * @param array $values
     * @access private
     * @return void
     */
    private function form($vars, $values, $id)
    {
        if (empty($values)) {
            $values = [];
        }

        $name = isset($values['name']) ? $values['name'] : '';
        $channel_id = isset($values['channel']) ? $values['channel'] : '';
        $field = isset($values['field']) ? $values['field'] : '';
        $fields = array();

        $channels = ee('Model')->get('Channel')->all()->getDictionary('channel_id', 'channel_title');
        $filter = ee()->input->post('filter');

        if (empty($channel_id)) {
            $channel = ee('Model')->get('Channel')->first();
        } else {
            $channel = ee('Model')->get('Channel', $channel_id)->first();
        }

        if (! empty($channel)) {
            $fields = $channel->getAllCustomFields()->getDictionary('field_id', 'field_label');
        }

        if ($channels) {
            $bookmarklet_field_fields = array(
                'channel' => array(
                    'type' => 'radio',
                    'choices' => $channels,
                    'value' => $channel->getId(),
                    'required' => true,
                    'no_results' => [
                        'text' => 'no_channels'
                    ]
                ),
                'field' => array(
                    'type' => 'radio',
                    'choices' => $fields,
                    'value' => $field,
                    'required' => true,
                    'no_results' => [
                        'text' => sprintf(lang('no_found'), lang('fields'))
                    ]
                )
            );
        } else {
            $bookmarklet_field_fields = array(
                'channel' => array(
                    'type' => 'radio',
                    'choices' => $channels,
                    'value' => $channel,
                    'required' => true,
                    'no_results' => array(
                        'text' => 'no_channels',
                        'link_text' => 'create_new_channel',
                        'link_href' => ee('CP/URL', 'channels/create')
                    )
                ),
            );
        }

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'name',
                    'desc' => 'alphadash_desc',
                    'fields' => array(
                        'name' => array(
                            'type' => 'text',
                            'value' => $name,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'bookmarklet_field',
                    'desc' => 'bookmarklet_field_desc',
                    'fields' => $bookmarklet_field_fields
                )
            )
        );

        ee()->form_validation->set_rules(array(
            array(
                'field' => 'name',
                'label' => 'lang:bookmark_name',
                'rules' => 'required|valid_xss_check'
            )
        ));

        if (empty($filter)) {
            if (AJAX_REQUEST) {
                ee()->form_validation->run_ajax();
                exit;
            } elseif (ee()->form_validation->run() !== false) {
                if ($this->saveBookmarks()) {
                    if (ee('Request')->post('submit') == 'save_and_new') {
                        ee()->functions->redirect(ee('CP/URL')->make($this->index_url . '/create', $this->query_string));
                    } elseif (ee()->input->post('submit') == 'save_and_close') {
                        ee()->functions->redirect(ee('CP/URL')->make($this->index_url, $this->query_string));
                    } else {
                        ee()->functions->redirect(ee('CP/URL')->make($this->index_url . '/edit/' . $id, $this->query_string));
                    }
                }
            } elseif (ee()->form_validation->errors_exist()) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('settings_save_error'))
                    ->addToBody(lang('settings_save_error_desc'))
                    ->now();
            }
        }

        ee()->javascript->output('
			$("select[name=channel]").change(function(e) {
				$("<input>").attr({
				    type: "hidden",
				    value: "true",
				    name: "filter"
				}).appendTo($(this).parents("form"));
				$(this).parents("form").submit();
			});
		');

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;

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
}
// END CLASS

// EOF
