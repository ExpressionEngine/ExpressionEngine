<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=content_edit" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>

	<?=form_open('C=content_edit'.AMP.'M=update_multi_entries', '', $form_hidden)?>

	<?php foreach ($entries->result() as $entry):?>

		<div class="label" style="margin-top:15px">
			<?=lang('title', 'title['.$entry->entry_id.']')?>
		</div>
		<ul>
			<li><?=form_input('title['.$entry->entry_id.']', $entry->title, 'maxlength="100" onkeyup="liveUrlTitle();" id="title['.$entry->entry_id.']"')?></li>
		</ul>

		<div class="label">
			<?=lang('url_title', 'url_title['.$entry->entry_id.']')?>
		</div>
		<ul>
			<li><?=form_input('url_title['.$entry->entry_id.']', $entry->url_title, 'maxlength="100" id="url_title['.$entry->entry_id.']"')?></li>
		</ul>

		<div class="label">
			<?=lang('entry_status', 'status['.$entry->entry_id.']')?>
		</div>
		<ul>
			<li><?=form_dropdown('status['.$entry->entry_id.']', $entries_status[$entry->entry_id], $entries_selected[$entry->entry_id], 'id="status['.$entry->entry_id.']"')?></li>
		</ul>

		<div class="label">
			<?=lang('entry_date', 'entry_date['.$entry->entry_id.']')?>
		</div>
		<ul>
			<li><?=form_input('entry_date['.$entry->entry_id.']', $this->localize->human_time($entry->entry_date), 'class="entry_date entry_date_'.$entry->entry_id.'" id="entry_date['.$entry->entry_id.']"')?></li>
		</ul>

		<h3 class="pad"><?=lang('options')?></h3>
		<ul>
			<li>
			<?php if (count($options[$entry->entry_id]['sticky']) > 0):?>
					<label><?=form_checkbox($options[$entry->entry_id]['sticky'])?> <?=lang('sticky')?></label><br />
			<?php endif;?>
			<?php if (count($options[$entry->entry_id]['allow_comments']) > 0):?>
					<label><?=form_checkbox($options[$entry->entry_id]['allow_comments'])?> <?=lang('allow_comments')?></label><br />
			<?php endif;?>
			</li>
		</ul>
	<hr />

	<?php endforeach;?>

	<?=form_submit('delete_members', lang('update'), 'class="whiteButton"')?>

	</div>
	
	<?=form_close()?>

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End of file multi_edit.php */
/* Location: ./themes/cp_themes/mobile/content/multi_edit.php */