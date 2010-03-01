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
	<div class="contents multi_edit">
		
		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
        <div id="registerUser">
	
		<?=form_open('C=content_edit'.AMP.'M=update_multi_entries', '', $form_hidden)?>

		<?php foreach ($entries->result() as $entry):?>

			<p>
				<?=lang('title', 'title['.$entry->entry_id.']')?>
				<?=form_input('title['.$entry->entry_id.']', $entry->title, 'maxlength="100" onkeyup="liveUrlTitle();" id="title['.$entry->entry_id.']"')?>
			</p>

			<p>
				<?=lang('url_title', 'url_title['.$entry->entry_id.']')?>
				<?=form_input('url_title['.$entry->entry_id.']', $entry->url_title, 'maxlength="100" id="url_title['.$entry->entry_id.']"')?>
			</p>

			<p>
				<?=lang('entry_status', 'status['.$entry->entry_id.']')?>
				<?=form_dropdown('status['.$entry->entry_id.']', $entries_status[$entry->entry_id], $entries_selected[$entry->entry_id], 'id="status['.$entry->entry_id.']"')?>
			</p>

			<p>
				<?=lang('entry_date', 'entry_date['.$entry->entry_id.']')?>
				<?=form_input('entry_date['.$entry->entry_id.']', $this->localize->set_human_time($entry->entry_date), 'class="entry_date entry_date_'.$entry->entry_id.'" id="entry_date['.$entry->entry_id.']"')?>
			</p>

			<fieldset>
				<legend><?=lang('options')?></legend>
				<?php if (count($options[$entry->entry_id]['sticky']) > 0):?>
					<p>
						<label><?=form_checkbox($options[$entry->entry_id]['sticky'])?> <?=lang('sticky')?></label>
					</p>
				<?php endif;?>
				<?php if (count($options[$entry->entry_id]['allow_comments']) > 0):?>
					<p>
						<label><?=form_checkbox($options[$entry->entry_id]['allow_comments'])?> <?=lang('allow_comments')?></label>
					</p>
				<?php endif;?>
			</fieldset>

		<hr />

		<?php endforeach;?>

		<div><?=form_submit('delete_members', lang('update'), 'class="submit"')?></div>

		</div>
		
		<?=form_close()?>

	</div> <!-- contents -->
</div> <!-- mainContent -->




<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit.php */
/* Location: ./themes/cp_themes/default/content/edit.php */