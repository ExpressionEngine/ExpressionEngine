<?php $this->load->view('account/_header')?>


		<?=form_open('C=myaccount'.AMP.'M=bookmarklet', '', $form_hidden)?>

		<?php if ($step == 1):?>
			<p class="pad"><?=lang('bookmarklet_info')?></p>

			<div class="label">
				<?=lang('bookmarklet_name', 'bookmarklet')?><br />
				<?=lang('single_word_no_spaces')?>
			</div>
			<ul>		<li><?=form_input(array('id'=>'bookmarklet','name'=>'bm_name','class'=>'field','value'=>'Bookmarklet','max_length'=>50))?></li>
			</ul>

			<div class="label">
				<?=form_label(lang('channel_name'), 'channel_id')?>
			</div>
			<ul>
				<li><?=form_dropdown('channel_id', $this->session->userdata['assigned_channels'], '', 'id="channel_id"')?></li>
			</ul>

			<?=form_submit('', lang('bookmarklet_next_step'), 'class="whiteButton"')?>

		<?php elseif ($step == 2):?>

			<div class="label">
				<?=form_label(lang('select_field'), 'field_id')?>
			</div>
			<ul>
				<li><?=form_dropdown('field_id', $field_id_options, '', 'id="field_id"')?></li>
			</ul>

			<div style="display:none">
				<label><?=form_checkbox('safari', 'y', TRUE, 'id="safari"')?> <?=lang('safari_users')?></label>
			</div>

			<?=form_submit('', lang('create_the_bookmarklet'), 'class="whiteButton"')?>

		<?php elseif ($step == 3):?>
			<p class="pad container"><?=lang('bookmarklet_created')?></p>
			<p class="pad"><?=lang('bookmarklet_instructions')?></p>
			<p class="pad"><a href="<?=$bm_link?>"><?=$bm_name?></a></p>
		<?php endif;?>


		<?=form_close()?>




</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file index.php */
/* Location: ./themes/cp_themes/default/account/bookmarklet.php */