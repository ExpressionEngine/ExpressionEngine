<?php extend_template('default') ?>

<?=form_open('C=design'.AMP.'M=update_global_template_prefs')?>
	<?php
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
		array('data' => lang('preference'), 'style' => 'width:50%;'),
		lang('setting')
	);
	
	// Template Routes
	$label = lang('enable_template_routes', 'enable_template_routes');
	$label .= '<div class="subtext">'.lang('enable_template_routes_exp').'</div>';
	
	$this->table->add_row(array(
			$label,
			form_dropdown('enable_template_routes', $route_options, $enable_template_routes)
		)
	);
	
	// Strict URLs
	$label = lang('strict_urls', 'strict_urls');
	$label .= '<div class="subtext">'.lang('strict_urls_exp').'</div>';
	
	$this->table->add_row(array(
			$label,
			form_dropdown('strict_urls', $strict_urls_options, $strict_urls)
		)
	);
	
	// 404 Template	
	$label = lang('404_page', '404_page');
	$label .= '<div class="subtext">'.lang('site_404_exp').'</div>';

	$this->table->add_row(array(
			$label,
			form_dropdown('site_404', $template_data, $site_404)
		)
	);
	
	// Template Revisions
	$description = lang('save_tmpl_revisions', 'save_tmpl_revisions');
	$description .= '<div class="subtext">'.lang('template_rev_msg').'</div>';

	$this->table->add_row(array(
		$description,
		form_dropdown('save_tmpl_revisions', 
			$save_tmpl_revisions_options, 
			$save_tmpl_revisions_y
		)
	));

	// Max Revisions
	$description = lang('max_tmpl_revisions', 'max_tmpl_revisions');
	$description .= '<div class="subtext">'.lang('max_revisions_exp').'</div>';

	$max_tmpl_revisions = array(
		'id'		=> 'max_tmpl_revisions',
		'name'	  => 'max_tmpl_revisions',
		'size'	  => 6,
		'maxlength' => 6,
		'value'	 => set_value('max_tmpl_revisions', $max_tmpl_revisions)
	);
	
	$this->table->add_row(array(
		$description,
		form_input($max_tmpl_revisions)
		)
	);


	// Save Templates as files?
	$description = lang('save_tmpl_files', 'save_tmpl_files');
	$description .= '<div class="subtext">'.lang('save_tmpl_files_exp').'</div>';

	$this->table->add_row(array(
		$description,
		form_dropdown('save_tmpl_files', 
			$save_tmpl_files_options, 
			$save_tmpl_files_y)
		)
	);
	
	// Template Basepath
	$template_basepath = array(
		'id'		=> 'tmpl_file_basepath',
		'name'	  => 'tmpl_file_basepath',
		'size'	  => 30,
		'value'	 => set_value('tmpl_file_basepath', $tmpl_file_basepath)
	);
	
	$label = lang('tmpl_file_basepath', 'tmpl_file_basepath');
	$label .= '<div class="subtext">'.lang('tmpl_file_basepath_exp').'</div>';
	
	$this->table->add_row(array(
			form_label($label, 'tmpl_file_basepath'),
			form_input($template_basepath)
		)
	);		
		
	echo $this->table->generate()
	?>
	<p><?=form_submit('template', lang('update'), 'class="submit"')?></p>
<?=form_close()?>