<?php $this->load->view('account/_account_header');?>

	<div>
		<h3><?=lang('administrative_options')?></h3>

		<?=form_open('C=myaccount'.AMP.'M=member_preferences_update', '', $form_hidden)?>
		
		<?php if (isset($group_id_options)): ?>
			<p>
				<?=form_label(lang('member_group_assignment'), 'group_id')?>
				<?=form_dropdown('group_id', $group_id_options, $group_id, 'id="group_id"' . (count($group_id_options) == 1 ? ' disabled' : ''))?>
			</p>
		<?php endif ?>

		<p>
			<?=form_checkbox(array('id'=>'in_authorlist','name'=>'in_authorlist','value'=>'y', 'checked'=>($in_authorlist=='y') ? TRUE : FALSE))?>
			<strong><?=lang('include_in_multiauthor_list')?></strong>
		</p>

		<p>
			<?=form_checkbox(array('id'=>'localization_is_site_default','name'=>'localization_is_site_default','value'=>'y', 'checked'=>($localization_is_site_default=='y') ? TRUE : FALSE))?>
			<strong><?=lang('localization_is_site_default')?></strong>
		</p>

		<p class="submit"><?=form_submit('member_preferences', lang('update'), 'class="submit"')?></p>

		<?=form_close()?>
	</div>

<?php $this->load->view('account/_account_footer');