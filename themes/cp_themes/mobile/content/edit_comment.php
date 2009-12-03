<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="file_browser" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>

	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

		<?=form_open('C=content_edit'.AMP.'M=update_comment', '', $hidden)?>
		<?php
		
		$required = '<em class="required">*&nbsp;</em>';
		
		if ($author_id == 0):?>
			<div class="label">
				<?=$required.lang('name', 'name')?>
			</div>
			<ul>
				<li><?=form_input('name', $name, 'class="field"; name="name"; id="name"')?></li>
			</ul>
			
			<div class="label">			
				<?=$required.lang('email', 'email')?>
			</div>
			<ul>
				<li><?=form_input('email', $email, 'class="field"; name="email"; id="email"')?></li>
			</ul>

			<div class="label">
				<?=lang('url', 'url')?>
			</div>
			<ul>
				<li><?=form_input('url', $url, 'class="field"; name="url"; id="url"')?></li>
			</ul>
			
			<div class="label">
				<?=lang('location', 'location')?>
			</div>
			<ul>
				<li><?=form_input('location', $location, 'class="field"; name="location"; id="location"')?></li>
			</ul>
		<?php endif;?>
			
			<div class="label">
				<?=$required.lang('comment', 'comment')?>
			</div>
			<ul>
				<li><?=form_textarea('comment', $comment, 'class="field"; name="comment"; id="comment"')?></li>
			</ul>
		
		<p><?=form_submit('update_comment', lang('update'), 'class="whiteButton"')?></p>
	
	<?=form_close()?>
</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_comment.php */
/* Location: ./themes/cp_themes/mobile/tools/edit_comment.php */