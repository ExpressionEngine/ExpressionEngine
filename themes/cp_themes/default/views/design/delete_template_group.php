<?php extend_template('default') ?>

<p><?=lang('choose_delete_template_group')?></p>

<ul class="bullets">
	<?php foreach ($template_groups as $group):?>
		<li><a href="<?=BASE.AMP.'C=design'.AMP.'M=template_group_delete_confirm'.AMP.'group_id='.$group['group_id']?>"><?=$group['group_name']?></a></li>
	<?php endforeach;?>
</ul>