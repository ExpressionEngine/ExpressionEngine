<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license
 */

namespace ExpressionEngine\Controller\Files;

use CP_Controller;

use ExpressionEngine\Model\File\File;
use ExpressionEngine\Library\CP\FileManager\Traits\FileManagerTrait;

/**
 * Abstract Files Controller
 */
abstract class AbstractFiles extends CP_Controller
{
    use FileManagerTrait;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('access_files')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('filemanager');

        ee()->view->can_edit_upload_directories = ee('Permission')->can('edit_upload_directories');
    }

    protected function generateSidebar($active = null)
    {
        $active_id = null;
        if (is_numeric($active)) {
            $active_id = (int) $active;
        }

        $sidebar = ee('CP/Sidebar')->make();

        $all_files = $sidebar->addItem(lang('all_files'), ee('CP/URL')->make('files'))->withIcon('archive');

        if ($active) {
            $all_files->isInactive();
        }

        $header = $sidebar->addHeader(lang('upload_directories'));

        $removeConfirmationFieldset = [
            'group' => 'delete-confirm',
            'setting' => [
                'title' => lang('remove_files_from_disk'),
                'desc' => lang('toggle_on_to_remove_files'),
                'fields' => [
                    'remove_files' => [
                        'type' => 'toggle',
                        'value' => 0,
                    ]
                ]
            ]
        ];
        $removeConfirmation = ee('View')->make('ee:_shared/form/fieldset')->render($removeConfirmationFieldset);

        $list = $header->addFolderList('directory')
            ->withNoResultsText(lang('zero_directories_found'))
            ->withRemoveConfirmation($removeConfirmation);

        if (ee('Permission')->can('create_upload_directories')) {
            $header->withButton(lang('new'), ee('CP/URL')->make('files/uploads/create'));

            $list->withRemoveUrl(ee('CP/URL')->make('files/rmdir', array('return' => ee('CP/URL')->getCurrentUrl()->encode())))
                ->withRemovalKey('dir_id');

            $sidebar->addDivider();

            $watermark_header = $sidebar->addItem(lang('watermarks'), ee('CP/URL')->make('files/watermarks'))->withIcon('tint');

            if ($active == 'watermark') {
                $watermark_header->isActive();
            }

            if (ee('Model')->get('File')->count()) {
                $sidebar->addItem(lang('export_all'), ee('CP/URL')->make('files/export'))->withIcon('download');
            }
        }

        $upload_destinations = ee('Model')->get('UploadDestination')
            ->filter('site_id', 'IN', [0, ee()->config->item('site_id')])
            ->filter('module_id', 0)
            ->order('name', 'asc');

        foreach ($upload_destinations->all() as $destination) {
            if ($destination->memberHasAccess(ee()->session->getMember()) === false) {
                continue;
            }

            $display_name = htmlspecialchars($destination->name, ENT_QUOTES, 'UTF-8');
            $item = $list->addItem($display_name, ee('CP/URL')->make('files/directory/' . $destination->id))
                ->withIcon('hdd')
                ->withEditUrl(ee('CP/URL')->make('files/uploads/edit/' . $destination->id))
                ->withRemoveConfirmation(lang('upload_directory') . ': <b>' . $display_name . '</b>')
                ->identifiedBy($destination->id);

            if (! ee('Permission')->can('edit_upload_directories')) {
                $item->cannotEdit();
            }

            if (! ee('Permission')->can('delete_upload_directories')) {
                $item->cannotRemove();
            }

            if ($active_id == $destination->id) {
                $item->isActive();
            }
        }

        ee()->cp->add_js_script(array(
            'ui' => array('droppable', 'sortable', 'draggable'),
            'file' => array('cp/files/menu'),
        ));
    }

    protected function stdHeader($active = null)
    {
        $uploadLocationsAndDirectoriesDropdownChoices = $this->getUploadLocationsAndDirectoriesDropdownChoices();

        $toolbar_items = [];

        ee()->view->header = array(
            'title' => lang('file_manager'),
            'toolbar_items' => $toolbar_items,
            'action_button' => ee('Permission')->can('upload_new_files') && !empty($uploadLocationsAndDirectoriesDropdownChoices) ? [
                'text' => '<i class="fal fa-cloud-upload-alt icon-left"></i>' . lang('upload'),
                'filter_placeholder' => lang('filter_upload_directories'),
                'choices' => count($uploadLocationsAndDirectoriesDropdownChoices) > 0 ? $uploadLocationsAndDirectoriesDropdownChoices : null,
                'href' => '#'
            ] : null
        );

        return [
            'uploadLocationsAndDirectoriesDropdownChoices' => $uploadLocationsAndDirectoriesDropdownChoices
        ];
    }

    protected function saveFileAndRedirect(File $file, $is_new = false, $sub_alert = null)
    {
        $action = ($is_new) ? 'upload_filedata' : 'edit_file_metadata';

        $file->save();

        if (AJAX_REQUEST) {
            ee()->output->send_ajax_response(array(
                'success' => true,
                'file_id' => $file->file_id,
                'title' => $file->title,
            ));
        }

        $alert = ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang($action . '_success'))
            ->addToBody(sprintf(lang($action . '_success_desc'), $file->title));

        if ($sub_alert) {
            $alert->setSubAlert($sub_alert);
        }

        $alert->defer();

        if ($action == 'upload_filedata') {
            ee()->session->set_flashdata('file_id', $file->file_id);
        }

        if (ee()->input->post('submit') == 'save_and_close') {
            $params = [];
            if ($file->directory_id != 0) {
                $params['directory_id'] = $file->directory_id;
            }
            ee()->functions->redirect(ee('CP/URL')->make('files/directory/' . $file->upload_location_id, $params));
        } else {
            ee()->functions->redirect(ee('CP/URL')->make('files/file/view/' . $file->getId()));
        }
    }

}

// EOF
