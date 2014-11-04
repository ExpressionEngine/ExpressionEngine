<?php extend_template('default') ?>
		<div class="formArea">
			<?=form_open('C=design'.AMP.'M=update_template_routes')?>
				<input type="hidden" name="route_order" id="route_order" />
				<div id="url_manager">
					<p><?= lang('template_route_notice'); ?></p>
					<?php
						$table = array();

						foreach($templates->result() as $template)
						{
							$url = cp_url('design/edit_template', array('id' => $template->template_id));
							$name = '<a id="templateId_'.$template->template_id.'" href="'.$url.'">'.$template->template_name.'</a>';
							$class = in_array($template->template_id, $error_ids) ? "class='route_error'" : NULL;
							$value = ! empty($input['route_' . $template->template_id]) ? $input['route_' . $template->template_id] : $template->route;
							$route = "<input $class name='route_{$template->template_id}' type='text' value='". htmlspecialchars($value, ENT_QUOTES) ."' />";

							if( ! empty($errors[$template->template_id]))
							{
								$message = "<p class='notice'>{$errors[$template->template_id]}</p>";
								$route = $message . $route;
							}

							$required = form_dropdown('required_' . $template->template_id, $options, $template->route_required);
							$table[] = array('&nbsp;', $template->group_name, $name, $route, $required);
						}

						$this->table->set_template(array(
							'table_open' => '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">'
						));
						$this->table->set_heading(array(
							'&nbsp;',
							lang('route_manager_group'),
							lang('route_manager_template'),
							lang('route_manager_route'),
							lang('route_manager_required')
						));
						echo $this->table->generate($table);
					?>
					<p><?=form_submit('template', lang('update'), 'class="submit"')?></p>
				</div>
			<?=form_close()?>
		</div>
