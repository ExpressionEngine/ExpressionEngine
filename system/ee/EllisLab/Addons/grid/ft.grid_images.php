<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

require_once PATH_ADDONS.'grid/ft.grid.php';

/**
 * Grid Images Fieldtype
 */
class Grid_images_ft extends Grid_ft {

	var $info = [
		'name'		=> 'Grid Images',
		'version'	=> '1.0.0'
	];

	public function display_settings($data)
	{
		$directory_choices = array('all' => lang('all'));

		$directory_choices += ee('Model')->get('UploadDestination')
			->fields('id', 'name')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('module_id', 0)
			->order('name', 'asc')
			->all()
			->getDictionary('id', 'name');

		$vars = $this->getSettingsVars();
		$vars['group'] = 'grid_images';

		$settings = [
			'field_options_grid_images' => [
				'label' => 'field_options',
				'group' => 'grid_images',
				'settings' => [
					[
						'title' => 'grid_min_rows',
						'desc' => 'grid_min_rows_desc',
						'fields' => [
							'grid_min_rows' => [
								'type' => 'text',
								'value' => isset($data['grid_min_rows']) ? $data['grid_min_rows'] : 0
							]
						]
					],
					[
						'title' => 'grid_max_rows',
						'desc' => 'grid_max_rows_desc',
						'fields' => [
							'grid_max_rows' => [
								'type' => 'text',
								'value' => isset($data['grid_max_rows']) ? $data['grid_max_rows'] : ''
							]
						]
					],
					[
						'title' => 'grid_allow_reorder',
						'fields' => [
							'allow_reorder' => [
								'type' => 'yes_no',
								'value' => isset($data['allow_reorder']) ? $data['allow_reorder'] : 'y'
							]
						]
					],
					[
						'title' => 'file_ft_content_type',
						'desc' => 'file_ft_content_type_desc',
						'fields' => [
							'field_content_type' => [
								'type' => 'radio',
								'choices' => [
									'all' => lang('all'),
									'image' => lang('file_ft_images_only')
								],
								'value' => isset($data['field_content_type']) ? $data['field_content_type'] : 'image'
							]
						]
					],
					[
						'title' => 'file_ft_allowed_dirs',
						'desc' => 'file_ft_allowed_dirs_desc',
						'fields' => [
							'allowed_directories' => [
								'type' => 'radio',
								'choices' => $directory_choices,
								'value' => isset($data['allowed_directories']) ? $data['allowed_directories'] : 'all',
								'no_results' => [
									'text' => sprintf(lang('no_found'), lang('file_ft_upload_directories')),
									'link_text' => 'add_new',
									'link_href' => ee('CP/URL')->make('files/uploads/create')
								]
							]
						]
					]
				]
			],
			'field_options_grid_images_fields' => [
				'label' => 'grid_images_setup',
				'group' => 'grid_images',
				'settings' => [$vars['grid_alert'], ee('View')->make('grid:settings')->render($vars)]
			]
		];

		ee()->javascript->output('EE.grid_settings($(".fields-grid-setup[data-group=grid_images]"));');
		ee()->javascript->output('FieldManager.on("fieldModalDisplay", function(modal) {
			EE.grid_settings($(".fields-grid-setup[data-group=grid_images]"));
		});');

		return $settings;
	}
}

// EOF
