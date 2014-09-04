<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('ignore_list')?></h3>

	<div class="cp_button" style="display:none;"><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=edit_profile_field'?>"><?=lang('add_member')?></a></div>

	<?=form_open('C=myaccount'.AMP.'M=ignore_list', '', $form_hidden)?>

	<div id="add_member">
	<p>
		<?=lang('member_usernames', 'name')?>
		<?=form_input(array('id'=>'name','name'=>'name','class'=>'field','value'=>'','maxlength'=>50))?>
	</p>
	<p class="submit">
		<?=form_submit('daction', lang('add_member'), 'class="submit"')?>
	</p>
	</div>

	<br class="clear_left" />

	<?php
		$this->table->set_heading(
			lang('mbr_screen_name'), 
			array('style'=>'width:2%','data'=>form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"'))
		);

		if (count($ignored_members) == 0) // No results?  Bah, how boring...
		{
			$this->table->add_row(array('colspan'=>2, 'data'=>''));
			echo "<p class='notice'>".lang('ignore_list_empty')."</p>";
		}
		else
		{
			foreach ($ignored_members as $member)
			{
				$this->table->add_row(
										$member['member_name'],
										'<input class="toggle" type="checkbox" name="toggle[]" value="'.$member['member_id'].'" />'
									);
			}

			echo $this->table->generate();
		}
	?>

	<?php if (count($ignored_members) > 0):?>
	<p class="submit"><?=form_submit('unignore', lang('unignore'), 'class="submit"')?></p>
	<?php endif;?>

	<?=form_close()?>
</div>
