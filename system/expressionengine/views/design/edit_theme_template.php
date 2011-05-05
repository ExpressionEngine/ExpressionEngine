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

		<div class="heading"><h2><?=$cp_page_title?></h2></div>
		<div class="pageContents">
						
			<div id="templateEditor" class="formArea">

				<h2 class="clear_left"><?=lang('edit_template')?>: <?=$theme_display_name?> / <?=$template_name?></h2>

				<?php if ($not_writable): ?>
					<p class="notice"><?=lang('file_not_writable')?><br /><br /><?=lang('file_writing_instructions')?></p>
				<?php endif; ?>
			
				<?php $this->load->view('_shared/message');?>

				<div id="template_create">
					<?=form_open('C=design'.AMP.'M=update_theme_template', '', array('type' => $type, 'theme' => $theme, 'name' => $name))?>
					<p>

					<?=form_textarea(array(
											'name'	=> 'template_data',
							              	'id'	=> 'template_data',
							              	'cols'	=> '100',
							              	'rows'	=> '20',
											'value'	=> $template_data,
											'style'	=> 'border: 0;'
									));?>
					</p>
		

					<p><?=form_submit('update', lang('update'), 'class="submit"')?> <?=form_submit('update_and_return', lang('update_and_return'), 'class="submit"')?></p>
					<?=form_close()?>

				</div>
			</div>
		</div> <!-- pageContents -->
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_theme_template.php */
/* Location: ./themes/cp_themes/default/design/edit_theme_template.php */