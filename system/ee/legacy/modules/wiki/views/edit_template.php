			<?php if ($not_writable): ?>
				<p class="notice"><?=lang('file_not_writable')?><br /><br /><?=lang('file_writing_instructions')?></p>
			<?php endif; ?>
			
			<div id="templateEditor" class="formArea">

			

				<div id="template_create">
					<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=update_template', '', array('theme' => $theme, 'template' => $template))?>
					<p>

					<?=form_textarea(array(
											'name'	=> 'template_data',
							              	'id'	=> 'template_data',
							              	'cols'	=> '100',
							              	'rows'	=> '30',
											'value'	=> $template_data,
											'style'	=> 'border: 0;'
									));?>
					</p>


					<p><?=form_submit('update', lang('update'), 'class="submit"')?> <?=form_submit('update_and_return', lang('update_and_return'), 'class="submit"')?></p>
					<?=form_close()?>

				</div>
			</div>

<?php
/* End of file edit_template.php */
/* Location: ./system/expressionengine/modules/wiki/views/edit_template.php */