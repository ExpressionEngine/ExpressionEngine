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
		
		<div class="heading">
			<h2><?=$cp_page_title?></h2>
		</div>
		
		<?php $this->load->view('_shared/message');?>


		<div class="publishPageContents">
			
			<!-- @todo consider changing this? -->
			<!-- <?php if (isset($submission_error)):?>
				<fieldset class="previewBox" id="previewBox"><legend class="previewItemTitle">&nbsp;<span class='alert'><?=lang('error')?></span>&nbsp;</legend>
					<?php echo $submission_error; ?>
					<?php echo $message; ?>
				</fieldset>
			<?php elseif ($message != ''):?>
				<fieldset class="previewBox" id="previewBox"><legend class="previewItemTitle">&nbsp;<span class='notice'><?=lang('success')?></span>&nbsp;</legend>
					<?php echo $message; ?>
				</fieldset>
			<?php endif;?> -->
			
			
			<?=form_open_multipart('C=content_publish'.AMP.'M=entry_form', array('id' => 'publishForm'))?>
			
				<!-- Tabs -->
				<ul class="tab_menu" id="tab_menu_tabs">
					<?php foreach ($tabs as $tab => $tab_fields):?>
						<li id="menu_<?=$tab?>" title="<?=form_prep($tab_labels[$tab])?>" class="content_tab">
							<a href="#" title="menu_<?=$tab?>" class="menu_<?=$tab?>"><?=lang($tab_labels[$tab])?></a>&nbsp;
						</li>
					<?php endforeach;?>
					
					<?php if ($this->session->userdata('group_id') == 1):?>
						<li class="addTabButton"><a class="add_tab_link" href="#"><?=lang('add_tab')?></a>&nbsp;</li>
					<?php endif?>
				</ul>
				
				
				<?php if ($this->session->userdata('group_id') == 1):?>
				<!-- Admin Sidebar -->
				
					<div id="tools">
						
						<h3><a href="#"><?=lang('fields')?></a></h3>
						<div>
							<ul>
								<?php foreach ($field_list as $name => $field):?>
								<?php if ($field['field_required'] == 'y'):?>
									<li><a href="#" class="field_selector" id="hide_field_<?=$field['field_id']?>"><?=required()?><?=$field['field_label']?></a></li>	
								<?php else:?>
									<li>
										<a href="#" class="field_selector" id="hide_field_<?=$field['field_id']?>"><?=$field['field_label']?></a>
										<a href="#" class="delete delete_field" id="remove_field_<?=$field['field_id']?>">
											<img src="<?=$cp_theme_url?>images/open_eye.png" alt="<?=lang('delete')?>" width="15" height="15" />
										</a>
									</li>
								<?php endif;?>						
								<?php endforeach;?>
							</ul><br />
						</div>
						
						<h3><a href="#"><?=lang('tabs')?></a></h3>
						<div>
						</div>
						
						<h3><a href="#"><?=lang('publish_layout')?></a></h3>
						<div>
						</div>
						
					</div> <!-- /tools -->
					
					<!-- Hide/Show Link -->
					<div id="showToolbarLink"><a href="#"><span><?=lang('show_toolbar')?></span>&nbsp;
						<img alt="<?=lang('hide')?>" id="hideToolbarImg" src="<?=$cp_theme_url?>images/content_hide_image_toolbar.png"  class="js_hide" />
						<img alt="<?=lang('show')?>" id="showToolbarImg" src="<?=$cp_theme_url?>images/content_show_image_toolbar.png" />
					</a></div>
				
				<?php endif;?>
				
				
				<div id="holder">
					
					
				</div>
			
			<?=form_close()?>
			
		</div> <!-- /publishPageContents -->


	</div> <!-- /contents -->
<div> <!-- /mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file publish.php */
/* Location: ./themes/cp_themes/corporate/content/publish.php */