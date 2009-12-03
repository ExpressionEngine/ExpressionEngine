<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>

	<?php $this->load->view('_shared/right_nav')?>

	<?=form_open($form_action, $form_extra, $form_hidden)?>
	
	<div class="container pad"><?=$message?></div>
	
	<ul class="rounded">
	<?php foreach ($items as $item): ?>
		<li><?=$item?></li>
	<?php endforeach; ?>
	</ul>
	
	<p><?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'whiteButton'))?></p>

	<?=form_close()?>
	
</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file preference_delete_confirm.php */
/* Location: ./themes/cp_themes/default/admin/preference_delete_confirm.php */