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

			<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		     <div class="pageContents"> 
			
			
			<?php $this->load->view('_shared/message');?>
				
			<?php if ($message):?>
				<p class="notice"><?=$message?></p>
			<?php endif;?>
				
				<?php if (count($templates) > 0): ?>

				<?=form_open('C=design'.AMP.'M=sync_run', '', $form_hidden)?>

				<h4><?=lang('sync_templates_info1')?></h4>
				<p><?=lang('sync_templates_info2')?></p>

					<?php
					$this->table->set_template($table_template);
					$this->table->set_heading(
											lang('template_group'),
											lang('template'),
											lang('filename'),
											lang('template_edit_date'),
											lang('file_edit_date'),
											form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"').NBS.lang('sync')											
											);
					
						foreach ($templates as $group => $templates): 
						$this->table->add_row($group, '', '', '', '', '');

							foreach ($templates as $template):
								$file_edit = ($template['file_exists'] === FALSE) ? lang('no_file_exists') : $template['file_edit'];
								$date_alert_class =  ($template['file_synced'] === FALSE) ? 'notice' : '';
								$file_alert_class =  ($template['file_exists'] === FALSE) ? 'notice' : '';
								$toggle_field =  ($template['file_synced'] === FALSE) ? $template['toggle'] : '<img src="'.
$cp_theme_url.'/images/check_mark.png" height="16" />';
								
									$this->table->add_row('', 
									array('data' => $template['template_name'], 'class' => 'templateName '.$template['type']), 
									array('data' => $template['file_name'], 'class' => $file_alert_class), 
									$template['edit_date'], 
									array('data' => $file_edit, 'class' => $date_alert_class),
									$toggle_field);
							endforeach;

						endforeach;
					echo $this->table->generate();
					?>

				<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
	
				<?=form_close()?>

		<?php endif;?>
			 </div> 
		
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file sync_confirm.php */
/* Location: ./themes/cp_themes/default/design/sync_confirm.php */