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
				<h2><?=lang('offline_template')?></h2>
		</div>
		<div class="pageContents">
			<?php $this->load->view('_shared/message');?>
	
			<div id="template_create">
		
				<?=form_open('C=design'.AMP.'M=system_offline')?>
				<?=form_hidden('template_id', $template_id)?>

				<?php $this->load->view('_shared/message');?>

				<p><?=lang('offline_template_desc')?></p>

				<p><?=form_textarea(array('id'=>'template_data','name'=>'template_data','cols'=>100,'rows'=>25,'class'=>'markItUpEditor','value'=>$template_data))?></p>
				<p><?=form_submit('template', lang('update'), 'class="submit"')?></p>
				<?=form_close()?>

			</div>
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file system_offline.php */
/* Location: ./themes/cp_themes/default/design/system_offline.php */