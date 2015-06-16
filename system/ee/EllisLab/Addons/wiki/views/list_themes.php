
			<?php if (count($themes) < 1):?>

				<p class="notice"><?=lang('unable_to_find_themes')?></p>
			
			<?php else:?>

				<ul class="menu_list">
				<?php foreach($themes as $theme_name => $theme_human_name):?>
					<li<?=alternator(' class="odd"', '')?>>
						<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=theme_templates'.AMP.'theme='.$theme_name?>">
							<?=$theme_human_name?>
						</a>
					</li>
				<?php endforeach;?>
				</ul>

			<?php endif;?>

<?php
/* End of file list_themes.php */
/* Location: ./system/expressionengine/modules/wiki/views/list_themes.php */