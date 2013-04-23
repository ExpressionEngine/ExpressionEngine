<?php
$can_add_site = (bool) lang('create_new_site');
if ($can_add_site)
{
	extend_template('default');
}
else
{
	extend_template('default', 'ee_right_nav');
}
?>

<h4><?=lang('msm_product_name')?></h4>
<p><?=lang('msm_version').$msm_version.'  '.lang('msm_build_number').$msm_build_number?></p>

<?php
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
		array('data' => lang('site_id'), 'width' => '7%'),
		lang('site_label'),
		lang('site_name'),
		lang('edit_site'),
		lang('delete')
	);
							
	foreach ($site_data->result() as $site)
	{
		$this->table->add_row(
			$site->site_id,
			"<strong>{$site->site_label}</strong>",
			$site->site_name,
			'<a href="'.BASE.AMP.'C=sites'.AMP.'M=add_edit_site'.AMP.'site_id='.$site->site_id.'">'.lang('edit_site').'</a>',
			
			($site->site_id == 1) ? '----' : '<a href="'.BASE.AMP.'C=sites'.AMP.'M=site_delete_confirm'.AMP.'site_id='.$site->site_id.'">'.lang('delete').'</a>'
		);
	}

	echo $this->table->generate();
?>