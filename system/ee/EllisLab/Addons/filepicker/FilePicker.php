<?php

namespace EllisLab\Addons\FilePicker;


use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\File\UploadDestination;

class FilePicker {

	public $base_url = 'addons/settings/filepicker/';
	public $controller = 'addons/settings/filepicker/modal';

	public function inject($view)
	{
		// Insert the modal
		$modal_vars = array('name'=> 'modal-file', 'contents' => '');
		$modal = ee('View')->make('ee:_shared/modal')->render($modal_vars);
		ee('CP/Modal')->addModal('modal-file', $modal);

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/files/picker'
			),
		));
	}

	public function link($text, $dir = 'all', $data = array())
	{
		$qs = array('directory' => $dir);

		if ( ! empty($data['image']))
		{
			$qs['type'] = 'thumbnails';
		}
		else
		{
			$qs['type'] = 'list';
		}

		$href = ee('CP/URL', $this->controller, $qs);
		$extra = "";
		$class = "";

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

		if ( ! empty($data['callback']))
		{
			$extra .= " data-callback='{$data['callback']}'";
		}

		if ( ! empty($data['class']))
		{
			$class .= $data['class'];
		}

		return "<a class='m-link filepicker $class' rel='modal-file' href='$href' $extra>". $text ."</a>";
	}

	public function buildTableFromFileCollection(Collection $files, $limit = 20, $selected = NULL)
	{
		$table = Table::fromGlobals(array(
			'autosort' => TRUE,
			'limit' => $limit
		));
		$table->setColumns(
			array(
				'title_or_name' => array(
					'encode' => FALSE
				),
				'file_type',
				'date_added',
			)
		);
		$table->setNoResultsText(lang('no_uploaded_files'));

		if (empty($_GET['sort_col']))
		{
			$table->config['sort_col'] = 'date_added';
			$table->config['sort_dir'] = 'desc';
		}

		$data = array();

		foreach ($files as $file)
		{
			if ( ! $file->getUploadDestination()
				|| $this->hasFileGroupAccessPrivileges($file->getUploadDestination()) === FALSE
				|| ! $file->exists())
			{
				continue;
			}

			$column = array(
				$file->title . '<br><em class="faded">' . $file->file_name . '</em>',
				$file->mime_type,
				ee()->localize->human_time($file->upload_date),
			);

			$attrs = array('data-id' => $file->file_id);

			if ($file->file_id == $selected)
			{
				$attrs = array('class' => 'selected');
				$column[0] = '<span></span>' . $column[0];
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
