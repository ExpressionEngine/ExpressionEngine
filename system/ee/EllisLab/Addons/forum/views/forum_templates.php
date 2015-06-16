<?php extend_view('_wrapper') ?>

<div class="shun"></div>

<?php if (count($templates) < 1):?>

	<p class="notice"><?=lang($message)?></p>
	<p class="notice"><?=lang(($theme_list ? 'unable_to_find_templates' : 'unable_to_find_template_file'))?></p>
<?php else:?>
	
	<ul class="menu_list shun">
	<?php foreach($templates['folders'] as $path => $human_name): ?>
		<li class="group<?=alternator(' odd', '')?>">
			<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=forum'.AMP.'method=forum_templates'.AMP.'folder='.$path?>">
				<?=$human_name?>
			</a>
		</li>
	<?php endforeach; ?>
	<?php foreach($templates['files'] as $path => $human_name):?>
		<li class="item<?=alternator(' odd', '')?>">
			<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=forum'.AMP.'method=edit_template'.AMP.'folder='.$path?>">
				<?=$human_name?>
			</a>
		</li>
	<?php endforeach;?>
	</ul>

<?php endif;?>



<?php

/* End of file theme_templates.php */
/* Location: ./system/expressionengine/modules/forum/views/theme_templates.php */