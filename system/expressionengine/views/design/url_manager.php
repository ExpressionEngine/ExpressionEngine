<?php extend_template('basic') ?>
		<div class="formArea">
			<div>
				<div class="templateEditorTop">
					<h2><?=lang('url_manager')?></h2>
				</div>
			</div>
			<?=form_open('C=design'.AMP.'M=update_template_routes')?>
				<div id="url_manager">
					<?php
						$table = array();
						foreach($templates->result() as $template)
						{
							$url = cp_url('design/edit_template', array('id' => $template->template_id));
							$name = '<a id="templateId_'.$template->template_id.'" href="'.$url.'">'.$template->template_name.'</a>';
							$class = in_array($template->template_id, $error_ids) ? "class='route_error'" : NULL;
							$value = ! empty($_POST[$template->template_id]) ? $_POST[$template->template_id] : $template->route;
							$route = "<input $class name='route_{$template->template_id}' type='text' value='$value' />";
							$required = form_dropdown('required_' . $template->template_id, $options, $template->route_required);
							$table[] = array($template->group_name, $name, $route, $required);
						}

						$this->table->set_template(array(
							'table_open' => '<table class="templateTable" border="0" cellspacing="0" cellpadding="0">'
						));
						$this->table->set_heading(array('Group', 'Template', 'Route', 'Segments Required?'));
						echo $this->table->generate($table);
					?>
					<p><?=form_submit('template', lang('update'), 'class="submit"')?></p>
				</div>
			<?=form_close()?>
		</div>