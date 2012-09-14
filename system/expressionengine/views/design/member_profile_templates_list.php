<?php extend_template('default') ?>
		
<?php if (count($templates) < 1):?>

	<p class="notice"><?=lang('unable_to_find_templates')?></p>

<?php else:?>
			
	<ul class="menu_list">
	<?php foreach($templates as $file => $human_name):?>
		<li<?=alternator(' class="odd"', '')?>>
			<a href="<?=BASE.AMP.'C=design'.AMP.'M=edit_profile_template'.AMP.'theme='.$theme_name.AMP.'name='.$file?>">
				<?=$human_name?>
			</a>
		</li>
	<?php endforeach;?>
	</ul>

<?php endif;?>