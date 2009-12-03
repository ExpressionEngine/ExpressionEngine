<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design<?=AMP?>M=snippets" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>
	
	<div class="container pad">
	
		<?=form_open('C=design'.AMP.'M=snippets_delete')?>
			<?=form_hidden('delete_confirm', TRUE)?>
			<?=form_hidden('snippet_id', $snippet_id)?>
			<p><?=lang('delete_this_snippet')?> <strong><?=$snippet_name?></strong></p>
			<p><strong class="notice"><?=lang('action_can_not_be_undone')?></strong></p>
			<p><?=form_submit('template', lang('yes'), 'class="whiteButton"')?></p>
		<?=form_close()?>

	</div>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file snippets_delete.php */
/* Location: ./themes/cp_themes/mobile/design/snippets_delete.php */