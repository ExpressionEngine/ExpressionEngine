<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('main_menu_manager')?></h3>

	<?=form_open('C=myaccount'.AMP.'M=main_menu_update', '', $form_hidden)?>

	<p><?=lang('main_menu_manager_description')?></p>
	<p class="go_notice"><?=lang('main_menu_manager_instructions')?></p>
	<p class="go_notice"><?=lang('main_menu_manager_description_more')?></p>

	<?php if (count($quicktabs) > 0):?>
		<p class="go_notice"><?=lang('quicklinks_delete_instructions')?></p>

		<?php
			$this->table->set_heading(
				lang('tab_title'), 
				lang('tab_order')
			);

			foreach ($quicktabs as $tab)
			{
				$this->table->add_row(
										form_input('title_'.$tab['order'], $tab['title']),
										form_input('order_'.$tab['order'], $tab['order'])
									);
			}
	
			echo $this->table->generate();
		?>

		<p class="submit"><?=form_submit('quicktabs_submit', lang('update'), 'class="submit"')?></p>
	<?php endif;?>
	<?=form_close()?>
</div>