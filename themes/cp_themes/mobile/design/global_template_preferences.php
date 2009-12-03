<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

	<?php $this->load->view('_shared/message');?>

		<?=form_open('C=design'.AMP.'M=update_global_template_prefs')?>
        <?php
		    $this->table->set_template($cp_pad_table_template);
		    $this->table->set_heading(
                array('data' => lang('preference'), 'style' => 'width:60%;'),
				lang('setting')
			);
			
		    // 404 Template
	
	        $label = lang('404_page', '404_page').'<br />';
	        $label .= lang('site_404_exp');
	
            $this->table->add_row(array(
                    $label,
                    form_dropdown('site_404', $template_data, $site_404)
                )
            );
            
            // Template Revisions
            $description = lang('save_tmpl_revisions', 'save_tmpl_revisions').'<br />';
            $description .= lang('template_rev_msg');

            $this->table->add_row(array(
                    $description,
                    form_dropdown('save_tmpl_revisions', 
                                   $save_tmpl_revisions_options, 
                                   $save_tmpl_revisions_y)
                )
            );

            // Max Revisions
            $description = lang('max_tmpl_revisions', 'max_tmpl_revisions').'<br />';
            $description .= lang('max_revisions_exp');
            
        	$max_tmpl_revisions = array(
        		'id'        => 'max_tmpl_revisions',
        		'name'      => 'max_tmpl_revisions',
        		'size'      => 6,
        		'maxlength' => 6,
        		'value'     => set_value('max_tmpl_revisions', $max_tmpl_revisions)
        	);
        	
            $this->table->add_row(array(
                $description,
                form_input($max_tmpl_revisions)
                )
            );


        // Save Templates as files?
        $description = lang('save_tmpl_files', 'save_tmpl_files').'<br />';
        $description .= lang('save_tmpl_files_exp');

        $this->table->add_row(array(
                $description,
                form_dropdown('save_tmpl_files', 
                               $save_tmpl_files_options, 
                               $save_tmpl_files_y)
            )
        );
        
        // Template Basepath
        $template_basepath = array(
            'id'        => 'tmpl_file_basepath',
        	'name'      => 'tmpl_file_basepath',
        	'size'      => 30,
        	'maxlength' => 100,
        	'value'     => set_value('tmpl_file_basepath', $tmpl_file_basepath)
        );
        
        $label = lang('tmpl_file_basepath', 'tmpl_file_basepath').'<br />';
        $label .= lang('tmpl_file_basepath_exp');
        
        $this->table->add_row(array(
                form_label($label, 'tmpl_file_basepath'),
                form_input($template_basepath)
            )
        );        
            
		echo $this->table->generate()
		?>
		<p><?=form_submit('template', lang('update'), 'class="whiteButton"')?></p>
		<?=form_close()?>

</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file global_template_preferences.php */
/* Location: ./themes/cp_themes/mobile/design/global_template_preferences.php */