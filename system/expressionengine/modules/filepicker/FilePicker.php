<?php

namespace EllisLab\ExpressionEngine\Module\FilePicker;

use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\File\UploadDestination;

class FilePicker {

	public $controller = 'addons/settings/filepicker/modal';

	public function inject($view)
	{
		// Insert the modal
		$modal_vars = array('name'=> 'modal-file', 'contents' => '');
		$view->blocks['modals'] .= $view->render('_shared/modal', $modal_vars, TRUE);

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/files/picker'
			),
		));
	}

	public function link($text, $dir = 'all', $data = array())
	{
		$href = cp_url($this->controller, array('directory' => $dir));
		$extra = "";

		if ( ! empty($data['image']))
		{
			$extra .= " data-input-image='{$data['image']}'";
		}

		if ( ! empty($data['input']))
		{
			$extra .= " data-input-value='{$data['input']}'";
		}

		if ( ! empty($data['name']))
		{
			$extra .= " data-input-name='{$data['name']}'";
		}

		return "<a class='m-link filepicker' rel='modal-file' href='$href' $extra>". $text ."</a>";
	}

	public function buildTableFromFileCollection(Collection $files, $limit = 20)
	{
		$table = Table::create(array('autosort' => TRUE, 'limit' => $limit));
		$table->setColumns(
			array(
				'title_or_name',
				'file_type',
				'date_added',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText(lang('no_uploaded_files'));

		$data = array();

		$file_id = ee()->session->flashdata('file_id');

		foreach ($files as $file)
		{
			if ( ! $file->getUploadDestination()
				|| $this->hasFileGroupAccessPrivileges($file->getUploadDestination()) === FALSE)
			{
				continue;
			}

			$toolbar = array(
				'view' => array(
					'href' => '',
					'rel' => 'modal-view-file',
					'class' => 'm-link',
					'title' => lang('view'),
					'data-file-id' => $file->file_id
				),
				'edit' => array(
					'href' => cp_url('files/file/edit/' . $file->file_id),
					'title' => lang('edit')
				),
				'crop' => array(
					'href' => cp_url('files/file/crop/' . $file->file_id),
					'title' => lang('crop'),
				),
				'download' => array(
					'href' => cp_url('files/file/download/' . $file->file_id),
					'title' => lang('download'),
				),
			);

			if ( ! $file->isImage())
			{
				unset($toolbar['view']);
				unset($toolbar['crop']);
			}

			$column = array(
				$file->title . '<br><em class="faded">' . $file->file_name . '</em>',
				$file->mime_type,
				ee()->localize->human_time($file->upload_date),
				array('toolbar_items' => $toolbar),
				array(
					'name' => 'selection[]',
					'value' => $file->file_id,
					'data' => array(
						'confirm' => lang('file') . ': <b>' . htmlentities($file->title, ENT_QUOTES) . '</b>'
					)
				)
			);

			$attrs = array();

			if ($file_id && $file->file_id == $file_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		return $table;
	}

	public function hasFileGroupAccessPrivileges(UploadDestination $dir)
	{
		// 2 = Banned
		// 3 = Guests
		// 4 = Pending
		$hardcoded_disallowed_groups = array('2', '3', '4');

		$member_group_id = ee()->session->userdata['group_id'];
		// If the user is a Super Admin, return true
		if ($member_group_id == 1)
		{
			return TRUE;
		}

		if (in_array($member_group_id, $hardcoded_disallowed_groups))
		{
			return FALSE;
		}

		if ( ! $dir)
		{
			return FALSE;
		}

		if (in_array($member_group_id, $dir->getNoAccess()->pluck('group_id')))
		{
			return FALSE;
		}

		return TRUE;
	}

}

?>
