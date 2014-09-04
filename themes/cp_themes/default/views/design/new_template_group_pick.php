<?php extend_template('default') ?>

<?php if (!$template_groups):?>

<h3><?=lang('no_templates_available'); ?></h3>
<?php else: ?>

<h3><?=lang('template_group_choose')?></h3>
<ul class="bullets">
		<?php foreach ($template_groups as $group):?>
			<li><a href="<?=BASE.AMP.'C=design'.AMP.'M='.$link_to_method.AMP.'group_id='.$group['group_id']?>"><?=$group['group_name']?></a></li>
		<?php endforeach;?>
</ul>
<?php endif; ?>