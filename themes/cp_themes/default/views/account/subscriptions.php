<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('subscriptions')?></h3>

	<?=form_open('C=myaccount'.AMP.'M=unsubscribe', '', $form_hidden)?>

	<?php
		$this->table->set_heading(
			lang('title'), 
			lang('type'), 
			array('style'=>'width:5%','data'=>form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"'))
		);

		if (count($subscriptions) == 0) // No results?  Bah, how boring...
		{
			$this->table->add_row(array('colspan'=>4, 'data'=>lang('no_subscriptions')));
		}
		else
		{
			foreach ($subscriptions as $subscription)
			{
				$this->table->add_row(
										'<a href="'.$subscription['path'].'">'.$subscription['title'].'</a>',
//											anchor($subscription['path'] ,$subscription['title']),
										$subscription['type'],
										'<input class="toggle" type="checkbox" name="toggle[]" value="'.$subscription['id'].'" />'
									);
			}
		}
	?>

	<?=$this->table->generate()?>

	<?=$pagination?>

	<?php if (count($subscriptions) > 0):?>
	<p class="submit"><?=form_submit('unsubscribe', lang('unsubscribe'), 'class="submit"')?></p>
	<?php endif;?>

	<?=form_close()?>
</div>