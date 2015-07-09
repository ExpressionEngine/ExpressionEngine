<?php


use EllisLab\ExpressionEngine\Model\File\UploadDestination;
use EllisLab\Addons\FilePicker\FilePicker as Picker;

class Filepicker_mcp {

	public function __construct()
	{
		$this->picker = new Picker();
		$this->base_url = 'addons/settings/filepicker';
		$this->access = FALSE;

		if (ee()->cp->allowed_group('can_access_content', 'can_access_files'))
		{
			$this->access = TRUE;
		}

		ee()->lang->loadfile('filemanager');
	}

	public function index()
	{
		// check if we have a request for a specific file id
		if ( ! empty(ee()->input->get('file')))
		{
			$this->fileInfo(ee()->input->get('file'));
		}

		if ($this->access === FALSE)
		{
			show_error(lang('unauthorized_access'));
		}

		$dirs = ee()->api->get('UploadDestination')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$directories = $dirs->indexBy('id');

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

		$type = ee()->input->get('type') ?: 'all';

		if ($type == 'img')
		{
			$files = $files->filter(function($file)
			{
				return $file->isImage();
			});
		}

		// Filter out any files that are no longer on disk
		$files->filter(function($file) { return $file->exists(); });

		$base_url = ee('CP/URL', $this->base_url);

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
		$base_url->setQueryStringVariable('type', $type);

		ee()->view->filters = $filters->render($base_url);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];
		$vars['dir'] = $id;

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
				->perPage($vars['table']['limit'])
				->currentPage($vars['table']['page'])
				->render($base_url);
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

	/**
	 * Return an AJAX response for a particular file ID
	 *
	 * @param mixed $id
	 * @access private
	 * @return void
	 */
	private function fileInfo($id)
	{
		$file = ee('Model')->get('File', $id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $file || ! $file->exists())
		{
			ee()->output->send_ajax_response(lang('file_not_found'), TRUE);
		}

		$member_group = ee()->session->userdata['group_id'];

		if ($file->memberGroupHasAccess($member_group) === FALSE || $this->access === FALSE)
		{
			ee()->output->send_ajax_response(lang('unauthorized_access'), TRUE);
		}

		$result = $file->getValues();

		$result['path'] = $file->getAbsoluteURL();
		$result['thumb_path'] = ee('Thumbnail')->get($file)->url;
		$result['isImage'] = $file->isImage();

		ee()->output->send_ajax_response($result);
	}
	// }}}

}
?>
