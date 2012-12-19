<?php extend_template('default') ?>

<?=form_open('C=members'.AMP.'M=view_all_members', array('id' => 'member_form'))?>
	<div id="filterMenu">
		<fieldset>
			<legend><?=lang('total_members')?> <?=$total_members?></legend>

			<p>
				<?=form_label(lang('keywords').NBS, 'member_name', array('class' => 'field js_hide'))?>
				<?=form_input(array('id'=>'member_name', 'name'=>'member_name', 'class'=>'field', 'placeholder' => lang('keywords'), 'value'=>$member_name))?> 
			</p>
			<p>
				<?=form_label(lang('member_group'), 'group_id')?>&nbsp;
				<?=form_dropdown('group_id', $member_groups_dropdown, $selected_group, 'id="group_id"')?> 
	
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

				<?=form_label(lang('filter_by'), 'column_filter')?>&nbsp;
				<?=form_dropdown('column_filter', $column_filter_options, $column_filter_selected, 'id="column_filter"')?> 
	
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

				<?=form_submit('submit', lang('search'), 'id="filter_member_submit" class="submit js_hide"')?>
				
				<img src="<?=$cp_theme_url?>images/indicator.gif" class="searchIndicator" alt="Edit Search Indicator" style="margin-bottom: -5px; visibility: hidden;" width="16" height="16" />
				
			</p>
		</fieldset>
	</div>
<?=form_close()?>

<?php
	echo form_open('C=members'.AMP.'M=member_confirm');
	echo $pagination_html;
	echo $table_html;	
?>
	<div class="tableSubmit">
	<?php
	if (count($member_action_options) > 0):?>
		<?=form_dropdown('action', $member_action_options).NBS.NBS?>
	<?php endif;?>

		<?=form_submit('effect_members', $delete_button_label, 'class="submit"'); ?>
	</div>	
	<?=$pagination_html?>
<?=form_close()?>