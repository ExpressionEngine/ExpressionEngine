<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">

		<div class="heading"><h2><?=lang('global_template_preferences')?></h2></div>
	
			<div class="pageContents">        
	<?php $this->load->view('_shared/message');?>
	
	<?=form_open('C=design'.AMP.'M=update_global_template_prefs')?>
    <?php
	    $this->table->set_template($cp_table_template);
	    $this->table->set_heading(
            array('data' => lang('preference'), 'style' => 'width:50%;'),
			lang('setting')
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
        $label = '<strong>'.lang('404_page').'</strong>';
        $label .= '<div class="subtext">'.lang('site_404_exp').'</div>';

        $this->table->add_row(array(
                form_label($label, 'site_404'),
                form_dropdown('site_404', $template_data, $site_404)
            )
        );
        
        // Template Revisions
        $description = '<strong>'.lang('save_tmpl_revisions').'</strong>';
        $description .= '<div class="subtext">'.lang('template_rev_msg').'</div>';

        $template_revisions_radio_yes = array(
            'name'          => 'save_tmpl_revisions',
            'id'            => 'save_tmpl_revisions_y',
            'value'         => 'y',
            'checked'       => $save_tmpl_revisions_y,
        );
                    
        $template_revisions_radio_yes_label = form_label(lang('yes'), 'save_tmpl_revisions_y');

        $template_revisions_radio_no = array(
            'name'          => 'save_tmpl_revisions',
            'id'            => 'save_tmpl_revisions_n',
            'value'         => 'n',
            'checked'       => $save_tmpl_revisions_n
        );
        
        $template_revisions_radio_no_label = form_label(lang('no'), 'save_tmpl_revisions_n');
        
        $this->table->add_row(array(
                $description, 
                form_radio($template_revisions_radio_yes).'&nbsp;'.$template_revisions_radio_yes_label
                .'&nbsp;&nbsp;&nbsp;&nbsp;'.
                form_radio($template_revisions_radio_no).'&nbsp;'.$template_revisions_radio_no_label
                
            )
        );

        // Max Revisions
        $description = '<strong>'.lang('max_tmpl_revisions').'</strong>';
        $description .= '<div class="subtext">'.lang('max_revisions_exp').'</div>';
        
    	$max_tmpl_revisions = array(
    		'id'        => 'max_tmpl_revisions',
    		'name'      => 'max_tmpl_revisions',
    		'size'      => 6,
    		'maxlength' => 6,
    		'value'     => set_value('max_tmpl_revisions', $max_tmpl_revisions)
    	);
    	
        $this->table->add_row(array(
            form_label($description, 'max_tmpl_revisions'),
            form_input($max_tmpl_revisions)
            )
        );


    // Save Templates as files?
    $description = '<strong>'.lang('save_tmpl_files').'</strong>';
    $description .= '<div class="subtext">'.lang('save_tmpl_files_exp').'</div>';

    $save_templates_as_files_radio_yes = array(
        'name'        => 'save_tmpl_files',
        'id'          => 'save_tmpl_files_y',
        'value'       => 'y',
        'checked'     => $save_tmpl_files_y
    );
    
    $save_templates_as_files_radio_yes_label = form_label(lang('yes'), 'save_tmpl_files_y');
    
    $save_templates_as_files_radio_no = array(
        'name'        => 'save_tmpl_files',
        'id'          => 'save_tmpl_files_n',
        'value'       => 'n',
        'checked'     => $save_tmpl_files_n
    );
    
    $save_templates_as_files_radio_no_label = form_label(lang('no'), 'save_tmpl_files_n');
    
    $this->table->add_row(array(
            $description, 
            form_radio($save_templates_as_files_radio_yes).'&nbsp;'.$save_templates_as_files_radio_yes_label
            .'&nbsp;&nbsp;&nbsp;&nbsp;'.
            form_radio($save_templates_as_files_radio_no).'&nbsp;'.$save_templates_as_files_radio_no_label
        )
    );
    
    // Template Basepath
    $template_basepath = array(
        'id'        => 'tmpl_file_basepath',
    	'name'      => 'tmpl_file_basepath',
    	'class'     => 'input fullfield',
    	'size'      => 20,
    	'maxlength' => 100,
    	'value'     => set_value('tmpl_file_basepath', $tmpl_file_basepath)
    );
    
    $label = '<strong>'.lang('tmpl_file_basepath').'</strong>';
    $label .= '<div class="subtext">'.lang('tmpl_file_basepath_exp').'</div>';
    
    $this->table->add_row(array(
            form_label($label, 'tmpl_file_basepath'),
            form_input($template_basepath)
        )
    );        
        
	echo $this->table->generate()
	?>
	<p class="centerSubmit"><?=form_submit('template', lang('update'), 'class="submit"')?></p>
	<?=form_close()?>

		</div> <!-- pageContents -->
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file global_template_preferences.php */
/* Location: ./themes/cp_themes/corporate/design/global_template_preferences.php */