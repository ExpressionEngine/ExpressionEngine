<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=addons_plugins" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

	<div class="container pad">
		
		<?=form_open('C=addons_plugins'.AMP.'M=remove')?>
		<?php
			foreach($hidden as $plugin)
			{
				echo form_hidden('deleted[]', $plugin);
			}
		?>

		<p class="go_notice"><?=lang($message)?></p>

		<p class="notice"><?=lang('action_can_not_be_undone')?></p>

		<p><?=form_submit('delete', lang('plugin_remove'), 'class="whiteButton"')?></p>

		<?=form_close()?>

	</div>


</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file plugin_delete.php */
/* Location: ./themes/cp_themes/mobile/addons/plugin_delete.php */