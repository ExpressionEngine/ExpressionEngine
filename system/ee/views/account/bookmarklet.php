<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('bookmarklet')?></h3>

	<?=form_open('C=myaccount'.AMP.'M=bookmarklet', '', $form_hidden)?>

	<?php if ($step == 1):?>
		<p><?=lang('bookmarklet_info')?></p>

		<p>
			<?=lang('bookmarklet_name', 'bookmarklet')?>
			<?=form_input(array('id'=>'bookmarklet','name'=>'bm_name','class'=>'field','value'=>'Bookmarklet','maxlength'=>50))?>
			<br ><?=lang('single_word_no_spaces')?>
		</p>

		<p>
			<?=form_label(lang('channel_name'), 'channel_id')?>
			<?=form_dropdown('channel_id', $this->session->userdata['assigned_channels'], '', 'id="channel_id"')?>
		</p>

		<p class="submit"><?=form_submit('', lang('bookmarklet_next_step'), 'class="submit"')?></p>

	<?php elseif ($step == 2):?>

		<p>
			<?=form_label(lang('select_field'), 'field_id')?>
			<?=form_dropdown('field_id', $field_id_options, '', 'id="field_id"')?>
		</p>

		<p class="submit"><?=form_submit('', lang('create_the_bookmarklet'), 'class="submit"')?></p>

	<?php elseif ($step == 3):?>
		<p class="go_notice"><?=lang('bookmarklet_created')?></p>
		<p><?=lang('bookmarklet_instructions')?></p>
		<p><a href="<?=$bm_link?>"><?=$bm_name?></a></p>
	<?php endif;?>


	<?=form_close()?>
</div>