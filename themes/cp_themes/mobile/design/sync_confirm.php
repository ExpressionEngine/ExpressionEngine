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


	<?php if ($message):?>
		<p class="container pad"><?=$message?></p>
	<?php endif;?>

	<?php if (count($templates) > 0): ?>

	<?=form_open('C=design'.AMP.'M=sync_run', '', $form_hidden)?>

	<div class="pad container">
		<h4><?=lang('sync_templates_info1')?></h4>
		<p style="font-weight:normal"><?=lang('sync_templates_info2')?></p>
	</div>
	<?php foreach ($templates as $group => $templates): ?>

		<h3 class="pad"><strong><?=lang('template_group')?>:</strong> <?=$group?></h3>
		
		<?php foreach($templates as $template):
			$file_edit = ($template['file_exists'] === FALSE) ? lang('no_file_exists') : $template['file_edit'];
			$date_alert_class =  ($template['file_synced'] === FALSE) ? 'notice' : '';
			$file_alert_class =  ($template['file_exists'] === FALSE) ? 'notice' : '';
			$toggle_field =  ($template['file_synced'] === FALSE) ? $template['toggle'] : '<img src="'.
$cp_theme_url.'/images/check_mark.png" height="16" />';
		?>
		<div class="label">
			<strong><?=lang('template')?>:</strong> <?=$template['template_name']?><br />
			<strong><?=lang('filename')?>:</strong> <?=$template['file_name']?><br />
			<strong><?=lang('template_edit_date')?>:</strong> <?=$template['edit_date']?><br />
			<strong><?=lang('file_edit_date')?>:</strong> <?=$file_edit?>
		</div>
		<ul class="rounded">
			<li><?php echo $toggle_field = str_replace('class="toggle"', '', $toggle_field)?></li>
		</ul>

		<?php endforeach;?>
	<?php endforeach;?>
	
	
	
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
		// echo $this->table->generate();
		?>

	<p><?=form_submit('submit', lang('submit'), 'class="whiteButton"')?></p>

	<?=form_close()?>
	<?php endif;?>

	

</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file sync_confirm.php */
/* Location: ./themes/cp_themes/mobile/design/sync_confirm.php */