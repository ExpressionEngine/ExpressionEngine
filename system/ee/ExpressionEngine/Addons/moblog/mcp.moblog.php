<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Library\CP\Table;

/**
 * Moblog Module control panel
 */
class Moblog_mcp
{
    public $channel_array = array();
    public $status_array = array();
    public $field_array = array();
    public $author_array = array();
    public $image_dim_array = array();
    public $upload_loc_array = array();

    public $default_template = '';
    public $default_channel_cat = '';

    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct()
    {
        $this->default_template = <<<EOT
{text}

{images}
<img src="{file}" width="{width}" height="{height}" alt="pic" />
{/images}

{files match="audio|files|movie"}
<a href="{file}">Download File</a>
{/files}
EOT;
    }

    /**
     * Moblog Homepage
     *
     * @access	public
     * @return	string
     */
    public function index()
    {
        $table = ee('CP/Table');
        $table->setColumns(array(
            'col_id',
            'moblog',
            'manage' => array(
                'type' => Table::COL_TOOLBAR
            ),
            array(
                'type' => Table::COL_CHECKBOX
            )
        ));

        $table->setNoResultsText(sprintf(lang('no_found'), lang('moblogs')), 'create_moblog', ee('CP/URL')->make('addons/settings/moblog/create'));

        $sort_map = array(
            'col_id' => 'moblog_id',
            'moblog' => 'moblog_full_name',
        );

        $moblogs = ee()->db->select('moblog_id, moblog_full_name')
            ->order_by($sort_map[$table->sort_col], $table->sort_dir)
            ->get('moblogs')
            ->result_array();

        $data = array();
        foreach ($moblogs as $moblog) {
            $edit_url = ee('CP/URL')->make('addons/settings/moblog/edit/' . $moblog['moblog_id']);
            $columns = array(
                $moblog['moblog_id'],
                array(
                    'content' => $moblog['moblog_full_name'],
                    'href' => $edit_url
                ),
                array('toolbar_items' => array(
                    'edit' => array(
                        'href' => $edit_url,
                        'title' => lang('edit')
                    ),
                    'copy' => array(
                        'href' => ee('CP/URL')->make('addons/settings/moblog/create/' . $moblog['moblog_id']),
                        'title' => lang('copy')
                    ),
                    'txt-only' => array(
                        'href' => ee('CP/URL')->make('addons/settings/moblog/check/' . $moblog['moblog_id']),
                        'title' => (lang('check_now')),
                        'content' => strtolower(lang('check_now'))
                    )
                )),
                array(
                    'name' => 'moblogs[]',
                    'value' => $moblog['moblog_id'],
                    'data' => array(
                        'confirm' => lang('moblog') . ': <b>' . htmlentities($moblog['moblog_full_name'], ENT_QUOTES, 'UTF-8') . '</b>'
                    )
                )
            );

            $attrs = array();
            if (ee()->session->flashdata('highlight_id') == $moblog['moblog_id']) {
                $attrs = array('class' => 'selected');
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => $columns
            );
        }

        $table->setData($data);

        $vars['base_url'] = ee('CP/URL')->make('addons/settings/moblog');
        $vars['table'] = $table->viewData($vars['base_url']);

        $vars['pagination'] = ee('CP/Pagination', count($moblogs))
            ->perPage($vars['table']['limit'])
            ->currentPage($vars['table']['page'])
            ->render($vars['table']['base_url']);

        ee()->javascript->set_global('lang.remove_confirm', lang('moblogs') . ': <b>### ' . lang('moblogs') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array('cp/confirm_remove'),
        ));

        return ee('View')->make('moblog:index')->render($vars);
    }

    /**
     * Remove moblogs handler
     */
    public function remove()
    {
        $moblog_ids = ee()->input->post('moblogs');

        if (! empty($moblog_ids) && ee()->input->post('bulk_action') == 'remove') {
            // Filter out junk
            $moblog_ids = array_filter($moblog_ids, 'is_numeric');

            if (! empty($moblog_ids)) {
                ee('Model')->get('moblog:Moblog', $moblog_ids)->delete();

                ee('CP/Alert')->makeInline('moblogs-table')
                    ->asSuccess()
                    ->withTitle(lang('moblogs_removed'))
                    ->addToBody(sprintf(lang('moblogs_removed_desc'), count($moblog_ids)))
                    ->defer();
            }
        } else {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/moblog', ee()->cp->get_url_state()));
    }

    /**
     * New moblog form
     */
    public function create($moblog_id = null)
    {
        $duplicate = ! is_null($moblog_id);

        return $this->form($moblog_id, $duplicate);
    }

    /**
     * Edit moblog form
     */
    public function edit($moblog_id)
    {
        return $this->form($moblog_id);
    }

    /**
     * Moblog creation/edit form
     *
     * @param	int		$moblog_id	ID of moblog to edit
     * @param	boolean	$duplicate	Whether or not to duplicate the passed moblog
     */
    private function form($moblog_id = null, $duplicate = false)
    {
        $vars = array();
        if (is_null($moblog_id) or $duplicate) {
            ee()->cp->add_js_script('plugin', 'ee_url_title');
            ee()->javascript->output('
				$("input[name=moblog_full_name]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=moblog_short_name]");
				});
			');

            $alert_key = 'created';
            $vars['cp_page_title'] = lang('create_moblog');
            $vars['base_url'] = ee('CP/URL')->make('addons/settings/moblog/create');

            $moblog = ee('Model')->make('moblog:Moblog');
        } else {
            $moblog = ee('Model')->get('moblog:Moblog', $moblog_id)->first();

            if (! $moblog) {
                show_error(lang('unauthorized_access'), 403);
            }

            $alert_key = 'updated';
            $vars['cp_page_title'] = lang('edit_moblog');
            $vars['base_url'] = ee('CP/URL')->make('addons/settings/moblog/edit/' . $moblog_id);
        }

        if ($duplicate) {
            $moblog = ee('Model')->get('moblog:Moblog', $moblog_id)->first();
            $vars['base_url'] = ee('CP/URL')->make('addons/settings/moblog/create/' . $moblog_id);
        }

        if (! empty($_POST)) {
            if ($duplicate) {
                $moblog = ee('Model')->make('moblog:Moblog');
            }

            $moblog->set($_POST);

            // Need to convert this field from its presentation serialization
            $moblog->moblog_valid_from = explode(',', trim(preg_replace("/[\s,|]+/", ',', $_POST['moblog_valid_from']), ','));

            $result = $moblog->validate();

            if ($result->isValid()) {
                $moblog = $moblog->save();

                if (is_null($moblog_id) or $duplicate) {
                    ee()->session->set_flashdata('highlight_id', $moblog->getId());
                }

                ee('CP/Alert')->makeInline('moblogs-table')
                    ->asSuccess()
                    ->withTitle(lang('moblog_' . $alert_key))
                    ->addToBody(sprintf(lang('moblog_' . $alert_key . '_desc'), $moblog->moblog_full_name))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL')->make('addons/settings/moblog'));
            } else {
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('moblogs-table')
                    ->asIssue()
                    ->withTitle(lang('moblog_not_' . $alert_key))
                    ->addToBody(lang('moblog_not_' . $alert_key . '_desc'))
                    ->now();
            }
        }

        $channels = ee('Model')->get('Channel')->with('Site');

        if (ee()->config->item('multiple_sites_enabled') !== 'y') {
            $channels = $channels->filter('site_id', 1);
        }
        $channels = $channels->all();

        $channels_options = array();
        foreach ($channels as $channel) {
            $channels_options[$channel->channel_id] = (ee()->config->item('multiple_sites_enabled') === 'y')
                ? $channel->Site->site_label . ' - ' . $channel->channel_title : $channel->channel_title;
        }

        $moblog_authors = array_merge(array('0' => lang('none')), ee('Member')->getAuthors(null, false));

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'moblog_name',
                    'fields' => array(
                        'moblog_full_name' => array(
                            'type' => 'text',
                            'value' => $moblog->moblog_full_name,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'moblog_short_name',
                    'desc' => 'alphadash_desc',
                    'fields' => array(
                        'moblog_short_name' => array(
                            'type' => 'text',
                            'value' => $moblog->moblog_short_name,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'moblog_check_interval',
                    'desc' => 'moblog_check_interval_desc',
                    'fields' => array(
                        'moblog_time_interval' => array(
                            'type' => 'text',
                            'value' => $moblog->moblog_time_interval,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'moblog_enabled',
                    'fields' => array(
                        'moblog_enabled' => array(
                            'type' => 'yes_no',
                            'value' => is_null($moblog->moblog_enabled) ? true : $moblog->moblog_enabled
                        )
                    )
                ),
                array(
                    'title' => 'file_archive_mode',
                    'desc' => 'file_archive_mode_desc',
                    'fields' => array(
                        'moblog_file_archive' => array(
                            'type' => 'yes_no',
                            'value' => $moblog->moblog_file_archive
                        )
                    )
                )
            ),
            'channel_entry_settings' => array(
                array(
                    'title' => 'channel',
                    'desc' => 'moblog_channel_desc',
                    'fields' => array(
                        'moblog_channel_id' => array(
                            'type' => 'select',
                            'choices' => $channels_options,
                            'value' => $moblog->moblog_channel_id
                        )
                    )
                ),
                array(
                    'title' => 'cat_id',
                    'fields' => array(
                        'moblog_categories' => array(
                            'type' => 'checkbox',
                            'choices' => ee('Model')->get('Category')->fields('cat_id', 'cat_name')->all()->getDictionary('cat_id', 'cat_name'),
                            'value' => $moblog->moblog_categories,
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('categories'))
                            ]
                        )
                    )
                ),
                array(
                    'title' => 'field_id',
                    'fields' => array(
                        'moblog_field_id' => array(
                            'type' => 'select',
                            'choices' => ee('Model')->get('ChannelField')->fields('field_id', 'field_label')->all()->getDictionary('field_id', 'field_label'),
                            'value' => $moblog->moblog_field_id
                        )
                    )
                ),
                array(
                    'title' => 'default_status',
                    'fields' => array(
                        'moblog_status' => array(
                            'type' => 'select',
                            'choices' => ee('Model')->get('Status')->fields('status')->all()->getDictionary('status', 'status'),
                            'value' => $moblog->moblog_status
                        )
                    )
                ),
                array(
                    'title' => 'author_id',
                    'fields' => array(
                        'moblog_author_id' => array(
                            'type' => 'select',
                            'choices' => $moblog_authors,
                            'value' => $moblog->moblog_author_id
                        )
                    )
                ),
                array(
                    'title' => 'moblog_sticky_entry',
                    'fields' => array(
                        'moblog_sticky_entry' => array(
                            'type' => 'yes_no',
                            'value' => $moblog->moblog_sticky_entry
                        )
                    )
                ),
                array(
                    'title' => 'moblog_allow_overrides',
                    'desc' => 'moblog_allow_overrides_subtext',
                    'fields' => array(
                        'moblog_allow_overrides' => array(
                            'type' => 'yes_no',
                            'value' => $moblog->moblog_allow_overrides
                        )
                    )
                ),
                array(
                    'title' => 'moblog_template',
                    'fields' => array(
                        'moblog_template' => array(
                            'type' => 'textarea',
                            'value' => $moblog->moblog_template ?: $this->default_template
                        )
                    )
                )
            ),
            'moblog_email_settings' => array(
                array(
                    'title' => 'moblog_email_type',
                    'fields' => array(
                        'moblog_email_type' => array(
                            'type' => 'radio',
                            'choices' => array('pop3' => lang('pop3')),
                            'value' => $moblog->moblog_email_type
                        )
                    )
                ),
                array(
                    'title' => 'moblog_email_address',
                    'fields' => array(
                        'moblog_email_address' => array(
                            'type' => 'text',
                            'value' => $moblog->moblog_email_address,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'moblog_email_server',
                    'desc' => 'server_example',
                    'fields' => array(
                        'moblog_email_server' => array(
                            'type' => 'text',
                            'value' => $moblog->moblog_email_server,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'moblog_email_login',
                    'desc' => 'data_encrypted',
                    'fields' => array(
                        'moblog_email_login' => array(
                            'type' => 'text',
                            'value' => $moblog->moblog_email_login,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'moblog_email_password',
                    'desc' => 'data_encrypted',
                    'fields' => array(
                        'moblog_email_password' => array(
                            'type' => 'password',
                            'value' => $moblog->moblog_email_password,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'moblog_subject_prefix',
                    'desc' => 'moblog_subject_subtext',
                    'fields' => array(
                        'moblog_subject_prefix' => array(
                            'type' => 'text',
                            'value' => $moblog->moblog_subject_prefix
                        )
                    )
                ),
                array(
                    'title' => 'moblog_auth_required',
                    'desc' => 'moblog_auth_subtext',
                    'fields' => array(
                        'moblog_auth_required' => array(
                            'type' => 'yes_no',
                            'value' => $moblog->moblog_auth_required
                        )
                    )
                ),
                array(
                    'title' => 'moblog_auth_delete',
                    'desc' => 'moblog_auth_delete_subtext',
                    'fields' => array(
                        'moblog_auth_delete' => array(
                            'type' => 'yes_no',
                            'value' => $moblog->moblog_auth_delete
                        )
                    )
                ),
                array(
                    'title' => 'moblog_valid_from',
                    'desc' => 'valid_from_subtext',
                    'fields' => array(
                        'moblog_valid_from' => array(
                            'type' => 'textarea',
                            'value' => implode("\n", $moblog->moblog_valid_from)
                        )
                    )
                ),
                array(
                    'title' => 'moblog_ignore_text',
                    'desc' => 'ignore_text_subtext',
                    'fields' => array(
                        'moblog_ignore_text' => array(
                            'type' => 'textarea',
                            'value' => $moblog->moblog_ignore_text
                        )
                    )
                )
            ),
            'moblog_file_settings' => array(
                array(
                    'title' => 'moblog_upload_directory',
                    'fields' => array(
                        'moblog_upload_directory' => array(
                            'type' => 'select',
                            'choices' => ee('Model')->get('UploadDestination')
                                ->fields('site_id', 'module_id', 'id', 'name')
                                ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
                                ->filter('module_id', 0)
                                ->all()
                                ->getDictionary('id', 'name'),
                            'value' => $moblog->moblog_upload_directory
                        )
                    )
                ),
                array(
                    'title' => 'moblog_image_size',
                    'fields' => array(
                        'moblog_image_size' => array(
                            'type' => 'select',
                            'choices' => array('0' => lang('none')),
                            'value' => $moblog->moblog_image_size
                        )
                    )
                ),
                array(
                    'title' => 'moblog_thumb_size',
                    'fields' => array(
                        'moblog_thumb_size' => array(
                            'type' => 'select',
                            'choices' => array('0' => lang('none')),
                            'value' => $moblog->moblog_thumb_size
                        )
                    )
                )
            )
        );

        $this->_filtering_menus('moblog_create');
        ee()->javascript->compile();

        $vars['save_btn_text'] = 'save_moblog';
        $vars['save_btn_text_working'] = 'btn_saving';

        return array(
            'heading' => $vars['cp_page_title'],
            'breadcrumb' => array(ee('CP/URL')->make('addons/settings/moblog')->compile() => lang('moblog') . ' ' . lang('configuration')),
            'body' => ee('View')->make('moblog:create')->render($vars)
        );
    }

    /**
     * JavaScript filtering code
     *
     * Creates some javascript functions that are used to switch
     * various pull-down menus
     *
     * @access public
     * @return void
     */
    public function _filtering_menus($form_name)
    {
        // In order to build our filtering options we need to gather
        // all the channels, categories and custom statuses

        /** -----------------------------
        /**  Allowed Channels
        /** -----------------------------*/
        $allowed_channels = ee()->functions->fetch_assigned_channels(true);
        $channel_info = array();

        if (count($allowed_channels) > 0) {
            $channels = ee('Model')->get('Channel')
                ->with('Statuses', 'CategoryGroups')
                ->order('channel_title');

            if (! ee('Permission')->can('edit_other_entries')) {
                $channels->filter('channel_id', 'IN', $allowed_channels);
            }

            $authors = array(
                array('0', lang('none'))
            );

            $super_admins = ee('Model')->get('Role', 1)->first();

            foreach ($super_admins->Members as $admin) {
                $authors[] = array($admin->getId(), $admin->getMemberName());
            }

            foreach ($channels->all() as $channel) {
                $statuses = array(
                    array('none', lang('none'))
                );

                if ($channel->Statuses) {
                    foreach ($channel->Statuses as $status) {
                        $statuses[] = array($status->status, lang($status->status));
                    }
                }

                $categories = array(
                    array('', lang('all'))
                );

                foreach ($channel->getCategoryGroups() as $cat_group) {
                    foreach ($cat_group->Categories as $category) {
                        $categories[] = array($category->cat_id, $category->cat_name);
                    }
                }

                $fields = array(
                    array('none', lang('none'))
                );

                foreach ($channel->getAllCustomFields() as $field) {
                    $fields[] = array($field->field_id, $field->field_label);
                }

                $channel_info[$channel->getId()] = array(
                    'moblog_categories' => $categories,
                    'moblog_status' => $statuses,
                    'moblog_field_id' => $fields,
                    'moblog_author_id' => $authors,
                );

                foreach ($channel->AssignedRoles as $role) {
                    foreach ($role->Members as $member) {
                        $channel_info[$channel->getId()]['moblog_author_id'][] = array($member->getId(), $member->getMemberName());
                    }
                }
            }
        }

        $channel_info = json_encode($channel_info);
        $none_text = lang('none');

        $javascript = <<<MAGIC

// An object to represent our channels
var channel_map = $channel_info;

var empty_select =  '<option value="0">$none_text</option>';
var spaceString = new RegExp('!-!', "g");

// We prep the magic array as soon as we can, basically
// converting everything into option elements
(function() {
	jQuery.each(channel_map, function(key, details) {

		// Go through each of the individual settings and build a proper dom element
		jQuery.each(details, function(group, values) {
			var html = new String();

			if (group == 'moblog_categories') {
				var checkbox_values = [];
				// Categories are checkboxes
				$('input[name="moblog_categories[]"]:checked').each(function() {
					checkbox_values.push(this.value);
				});
				jQuery.each(values, function(a, b) {
					var checked = '',
						chosen = '';
					if ($.inArray(b[0], checkbox_values) > -1) {
						checked = ' checked';
						chosen = ' chosen';
					}
					html += '<label class="choice block'+chosen+'"><input type="checkbox" name="moblog_categories[]" value ="' + b[0] + '"'+checked+'>' + b[1].replace(spaceString, String.fromCharCode(160)) + "</label>";
				});
			} else {
				var value = $('select[name="'+group+'"]').val();
				// Add the new option fields
				jQuery.each(values, function(a, b) {
					var selected = (value == b[0]) ? ' selected' : '';
					html += '<option value="' + b[0] + '"'+selected+'>' + b[1].replace(spaceString, String.fromCharCode(160)) + "</option>";
					//console.log(html);
				});
			}

			channel_map[key][group] = html;
		});
	});
})();

// Change the submenus
// Gets passed the channel id
function changemenu(index)
{
	var channels = 'null';

	if (channel_map[index] === undefined) {
		$('select[name=moblog_field_id], select[name="moblog_categories"], select[name=moblog_status], select[name=moblog_author_id]').empty().append(empty_select);
	}
	else {
		jQuery.each(channel_map[index], function(key, val) {
			switch(key) {
				case 'moblog_field_id':		$('select[name=moblog_field_id]').empty().append(val);
					break;
				case 'moblog_categories':	$('input[name="moblog_categories[]"]').parents('.setting-field').empty().append(val);
					break;
				case 'moblog_status':	$('select[name=moblog_status]').empty().append(val);
					break;
				case 'moblog_author_id':		$('select[name=moblog_author_id]').empty().append(val);
					break;
			}
		});
	}
}

$('select[name=moblog_channel_id]').change(function() {
	changemenu(this.value);
}).change();

MAGIC;

        // And same idea for file upload dirs and dimensions
        $this->upload_loc_array = array('0' => lang('none'));
        $this->image_dim_array = array('0' => $this->upload_loc_array);

        // Fetch Upload Directories
        ee()->load->model(array('file_model', 'file_upload_preferences_model'));

        $sizes_q = ee()->file_model->get_dimensions_by_dir_id();
        $sizes_array = array();

        foreach ($sizes_q->result_array() as $row) {
            $sizes_array[$row['upload_location_id']][$row['id']] = $row['short_name'];
        }

        foreach (ee()->session->getMember()->getAssignedUploadDestinations() as $destination) {
            $this->image_dim_array[$destination->id] = array('0' => lang('none'));
            $this->upload_loc_array[$destination->id] = $destination->name;

            // Get sizes
            if (isset($sizes_array[$destination->id])) {
                foreach ($sizes_array[$destination->id] as $id => $title) {
                    $this->image_dim_array[$destination->id][$id] = $title;
                }
            }
        }

        $upload_info = json_encode($this->image_dim_array);

        $javascript .= <<<MAGIC

// An object to represent our channels
var upload_info = $upload_info;

var empty_select =  '<option value="0">$none_text</option>';
var spaceString = new RegExp('!-!', "g");

// We prep the magic array as soon as we can, basically
// converting everything into option elements
(function(undefined) {
	jQuery.each(upload_info, function(key, options) {

		var html = '';

		// add option fields
		jQuery.each(options, function(k, v) {

			html += '<option value="' + k + '">' + v.replace(spaceString, String.fromCharCode(160)) + "</option>";
		});

		if (html) {
			upload_info[key] = html;
		}
	});
})();

// Change the submenus
// Gets passed the channel id
function upload_changemenu(index)
{
	$('select[name=moblog_image_size]').empty().append(upload_info[index]);
	$('select[name=moblog_thumb_size]').empty().append(upload_info[index]);
}

$('select[name=moblog_upload_directory]').change(function() {
	upload_changemenu(this.value);
}).change();

MAGIC;

        ee()->javascript->output($javascript);
    }

    /** -------------------------
    /**  Check Moblog
    /** -------------------------*/
    public function check($moblog_id)
    {
        $where = array(
            'moblog_enabled' => 'y',
            'moblog_id' => $moblog_id
        );

        $query = ee()->db->get_where('moblogs', $where);

        if ($query->num_rows() == 0) {
            return ee()->output->show_user_error('submission', array(lang('invalid_moblog')));
        }

        if (! class_exists('Moblog')) {
            require PATH_ADDONS . 'moblog/mod.moblog.php';
        }

        $MP = new Moblog();
        $MP->moblog_array = $query->row_array();

        $error = false;

        if ($MP->moblog_array['moblog_email_type'] == 'imap') {
            $this->_moblog_check_return($MP->check_imap_moblog(), $MP);
        } else {
            $this->_moblog_check_return($MP->check_pop_moblog(), $MP);
        }
    }

    /** -------------------------
    /**  Moblog Check Return
    /** -------------------------*/
    public function _moblog_check_return($response, $MP)
    {
        if (! $response) {
            ee('CP/Alert')->makeInline('moblogs-table')
                ->asIssue()
                ->withTitle(lang('moblog_check_failure'))
                ->addToBody($MP->errors())
                ->defer();
        } else {
            ee('CP/Alert')->makeInline('moblogs-table')
                ->asSuccess()
                ->withTitle(lang('moblog_check_success'))
                ->addToBody(lang('emails_done') . NBS . $MP->emails_done)
                ->addToBody(lang('entries_added') . NBS . $MP->entries_added)
                ->addToBody(lang('attachments_uploaded') . NBS . $MP->uploads)
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/moblog', ee()->cp->get_url_state()));
    }
}
// END CLASS

// EOF
