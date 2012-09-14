<?php extend_template('default') ?>

<?php if (isset($not_writeable)):?>
	<p class="notice"><?=$not_writeable?></p>
<?php endif; ?>
<p><?=lang('choose_translation_file')?></p>

<ul class="menu_list">
	<?php foreach($language_files as $file):?>

		<li<?=alternator('', ' class="odd"');?>><a href="<?=BASE.AMP.'C=tools_utilities'.AMP.'M=translate'.AMP.'language_file='.$file?>"><?=$file?></a></li>

	<?php endforeach;?>
</ul>