<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('quicklinks_manager')?></h3>

	<?=form_open('C=myaccount'.AMP.'M=quicklinks_update', '', $form_hidden)?>

	<div class="shun">
		<?=lang('quick_link_description')?> <?=lang('quick_link_description_more')?>
	</div>

	<div class="notice del_instructions"><?=lang('quicklinks_delete_instructions')?></div>

	<?php 
	$this->table->set_heading(
		lang('link_title'),
		lang('link_url'),
		array('data'=>lang('link_order'), 'style'=>'width: 10%;')
	);

	foreach($quicklinks as $i => $quicklink)
	{
		$this->table->add_row(
			form_input(array('name'=>"title_$i", 'value'=>$quicklink['title'], 'size'=>40)),
			form_input(array('name'=>"link_$i", 'value'=>$quicklink['link'], 'size'=>40)),
			form_input(array('name'=>"order_$i", 'value'=>$quicklink['order'], 'size'=>3))
		);
	}

	$this->table->add_row(
		form_input(array('name'=>"title_$blank_count", 'value'=>'', 'size'=>40)),
		form_input(array('name'=>"link_$blank_count", 'value'=>'http://', 'size'=>40)),
		form_input(array('name'=>"order_$blank_count", 'value'=>$blank_count, 'size'=>3))
	);

	echo $this->table->generate();

	?>

	<p class="submit"><?=form_submit('quicklinks_update', lang('submit'), 'class="submit"')?></p>

	<?=form_close()?>
</div>