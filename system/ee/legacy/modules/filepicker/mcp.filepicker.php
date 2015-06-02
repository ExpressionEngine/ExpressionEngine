<?php

use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Model\File\UploadDestination;
use EllisLab\ExpressionEngine\Module\FilePicker\FilePicker as Picker;

class Filepicker_mcp {

	public function __construct()
	{
		$this->picker = new Picker();
		$this->base_url = 'addons/settings/filepicker';
		ee()->lang->loadfile('filemanager');
	}

	public function index()
	{
		// check if we have a request for a specific file id
		if ( ! empty(ee()->input->get('file')))
		{
			$id = ee()->input->get('file');
			$file = ee('Model')->get('File', $id)
				->filter('site_id', ee()->config->item('site_id'))
				->first();

			$result = $file->getValues();

			$result['path'] = $file->getAbsoluteURL();
			$result['thumb_path'] = $file->getThumbnailURL();
			$result['isImage'] = $file->isImage();

			echo json_encode($result);
			return;
		}

		$directories = array();
		$dirs = ee()->api->get('UploadDestination')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		foreach($dirs as $dir)
		{
			$directories[$dir->id] = $dir;
		}

		if ( ! empty(ee()->input->get('directory')))
		{
			$id = ee()->input->get('directory');
		}

		if (empty($id) || $id == 'all')
		{
			$id = 'all';
			$files = ee('Model')->get('File')
				->filter('site_id', ee()->config->item('site_id'))->all();
		}
		else
		{
			$dir = $directories[$id];
			$files = $dir->getFiles();
		}

		// Filter out any files that are no longer on disk
		$files->filter(function($file) { return $file->exists(); });

		$base_url = new URL($this->base_url, ee()->session->session_id());

		$filters = ee('Filter')->add('Perpage', $files->count(), 'show_all_files');

		$directories = array_map(function($dir) {return $dir->name;}, $directories);
		$directories = array('all' => lang('all')) + $directories;
		$dirFilter = ee('Filter')->make('directory', lang('directory'), $directories);
		$dirFilter->disableCustomValue();

		$filters = $filters->add($dirFilter);

		$table = $this->picker->buildTableFromFileCollection($files, $filters->values()['perpage']);

		$base_url->setQueryStringVariable('sort_col', $table->sort_col);
		$base_url->setQueryStringVariable('sort_dir', $table->sort_dir);
		$base_url->setQueryStringVariable('directory', $id);

		ee()->view->filters = $filters->render($base_url);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];
		$vars['dir'] = $id;

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$pagination = new Pagination(
				$vars['table']['limit'],
				$vars['table']['total_rows'],
				$vars['table']['page']
			);
			$vars['pagination'] = $pagination->cp_links($base_url);
		}

		ee()->view->cp_heading = $id == 'all' ? lang('all_files') : sprintf(lang('files_in_directory'), $dir->name);

		return ee()->cp->render('ModalView', $vars, TRUE);
	}

	public function modal()
	{
		$this->base_url = $this->picker->controller;
		ee()->output->_display($this->index());
		exit();
	}

}
?>
