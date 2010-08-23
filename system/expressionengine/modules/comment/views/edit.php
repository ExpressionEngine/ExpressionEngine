
				<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=update_comment', '', $hidden)?>
				<?php
				$this->table->set_template($cp_pad_table_template);
				$this->table->set_heading(
				    array('data' => '&nbsp;', 'style' => 'width:50%;'),
				    '&nbsp;'
				);
				
				$required = '<em class="required">*&nbsp;</em>';
				$email_required = ($comment_require_email == 'y') ? $required : '';
				
					$this->table->add_row(array(
							lang('entry_title'), 
							
							$title.NBS.NBS.'('.$channel_title.')'.'<div><a id="move_link" href"#">'.lang('move').'</a><span id="move_field" class="js_hide">'.
							lang('move_comment_instr').NBS.form_input('move_to', set_value('move_to', $move_to), 'class="field" name="move_to" id="move_to" size="5" style="width:20px"').form_error('move_to').'</span>'
							
						)
					);

				if ($author_id == 0) 
				{
					$this->table->add_row(array(
							$required.lang('name', 'name'),
							form_input('name', set_value('name', $name), 'class="field" name="name" id="name"').form_error('name')
						)
					);
					
					$this->table->add_row(array(
							$email_required.lang('email', 'email'),
							form_input('email', set_value('email', $email), 'class="field" name="email" id="email"').form_error('email')
						)
					);
					
					$this->table->add_row(array(
							lang('url', 'url'),
							form_input('url', set_value('url', $url), 'class="field" name="url" id="url"')
						)
					);
					
					$this->table->add_row(array(
							lang('location', 'location'),
							form_input('location', set_value('location', $location), 'class="field" name="location" id="location"')
						)
					);
				}
				else
				{
					$this->table->add_row(array(lang('name').NBS.NBS.lang('registered_member'),	$name));
					
					$this->table->add_row(array(lang('email'), $email));
					
					$this->table->add_row(array(lang('url'), $url));
					
					$this->table->add_row(array(lang('location'), $location));
				}

				$this->table->add_row(array(
							$required.lang('comment', 'comment'),
							form_textarea('comment', set_value('comment', $comment), 'class="field", name="comment",  id="comment"').form_error('comment')
					)
				);

				echo $this->table->generate();
				?>
				
				<p><?=form_submit('update_comment', lang('update'), 'class="submit"')?></p>
			
			<?=form_close()?>
