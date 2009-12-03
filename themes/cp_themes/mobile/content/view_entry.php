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

	<div class="pad container">
		<?=$entry_contents?>
	</div>
		<ul class="bullets">
	<?php if ($show_edit_link !== FALSE):?>
		<li><a href="<?=$show_edit_link?>"><?=$this->lang->line('edit_this_entry')?></a></li>
	<?php endif;?>

	<?php if ($show_comments_link !== FALSE):?>
		<li><a href="<?=$show_comments_link?>"><?=$this->lang->line('view_comments')." ({$comment_count})"?></a></li>
	<?php endif;?>

	<?php if ($live_look_link !== FALSE):?>
		<li><a href="<?=$live_look_link?>"><?=$this->lang->line('live_look')?></a></li>
	<?php endif;?>
		</ul>

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End of file view_entry.php */
/* Location: ./themes/cp_themes/mobile/content/view_entry.php */