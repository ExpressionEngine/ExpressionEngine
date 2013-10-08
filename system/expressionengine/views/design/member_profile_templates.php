<?php extend_template('default') ?>

<?php if (count($profiles) < 1):?>

	<p class="notice"><?=lang('unable_to_find_templates')?></p>

<?php else:?>

	<ul class="menu_list">
	<?php foreach($profiles as $profile_name => $profile_human_name):?>
		<li<?=alternator(' class="odd"', '')?>>
			<a href="<?=BASE.AMP.'C=design'.AMP.'M=list_profile_templates'.AMP.'name='.$profile_name?>">
				<?=$profile_human_name?>
			</a>
		</li>
	<?php endforeach;?>
	</ul>

<?php endif;?>