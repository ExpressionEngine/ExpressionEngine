<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=content_publish" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>


	<?php if (isset($ping_errors) and is_array($ping_errors)):?>
	<?=lang('xmlrpc_ping_errors')?>
	<ul>
		<?php foreach($ping_errors as $v):?>
		<li><?=$v['0']?> - <?=$v['1']?></li>
		<?php endforeach;?>
	</ul>
	<?php endif;?>

	<p><a href="<?=$entry_link?>"><?=lang('click_to_view_your_entry')?></a></p>

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End of file view_entry.php */
/* Location: ./themes/cp_themes/mobile/content/view_entry.php */