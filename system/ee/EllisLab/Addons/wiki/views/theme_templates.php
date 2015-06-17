			<?php if (count($templates) < 1):?>

				<p class="notice"><?=lang('unable_to_find_template_file')?></p>

			<?php else:?>
			
				<ul class="menu_list">
				<?php foreach($templates as $file => $human_name):?>
					<li<?=alternator(' class="odd"', '')?>>
						<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=edit_template'.AMP.'theme='.$theme_name.AMP.'template='.$file?>">
							<?=$human_name?>
						</a>
					</li>
				<?php endforeach;?>
				</ul>

			<?php endif;?>

<?php

/* End of file theme_templates.php */
/* Location: ./system/expressionengine/modules/wiki/views/theme_templates.php */