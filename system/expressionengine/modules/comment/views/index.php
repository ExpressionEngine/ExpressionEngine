		<div id="filterMenu">
			<fieldset>
				<legend><?=lang('search_comments')?></legend>

			<?=form_open($search_form, array('name'=>'filterform', 'id'=>'filterform'), $search_form_hidden)?>

				<div class="group">
					<?=form_dropdown('channel_id', $channel_select_options, $channel_selected, 'id="f_channel_id"').NBS.NBS?>
					<?=form_dropdown('status', $status_select_options, $status_selected, 'id="f_status"').NBS.NBS?>
					<?=form_dropdown('date_range', $date_select_options, $date_selected, 'id="date_range"').NBS.NBS?>
				</div>

        		<div id="custom_date_picker" style="display: none; margin: 0 auto 50px auto;width: 500px; height: 235px; padding: 5px 15px 5px 15px;border: 1px solid black;  background: #FFF;">
					<div id="cal1" style="width:250px; float:left; text-align:center;">
						<p style="text-align:left; margin-bottom:5px"><?=lang('start_date', 'custom_date_start')?>:&nbsp; <input type="text" name="custom_date_start" id="custom_date_start" value="yyyy-mm-dd" size="12" tabindex="1" /></p>
						<span id="custom_date_start_span"></span>
					</div>
	                <div id="cal2" style="width:250px; float:left; text-align:center;">
						<p style="text-align:left; margin-bottom:5px"><?=lang('end_date', 'custom_date_end')?>:&nbsp; <input type="text" name="custom_date_end" id="custom_date_end" value="yyyy-mm-dd" size="12" tabindex="2" /></p>
						<span id="custom_date_end_span"></span>          
					</div>
                </div>

				<div>
					<?=lang('keywords', 'keywords')?> <?=form_input('keywords', $keywords, 'class="field shun" id="keywords"')?><br />
					<?=form_dropdown('search_in', $search_in_options, $search_in_selected, 'id="f_search_in"').NBS.NBS?>
					<?=form_submit('submit', lang('search'), 'class="submit" id="search_button"')?>
				</div>

			<?=form_close()?>
			</fieldset>
			</div> <!-- filterMenu -->



<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=modify_comments', array('name' => 'target', 'id' => 'target'), $hidden)?>
		<?php
			$this->table->set_template($cp_pad_table_template);

			$heading = array(
				'<a id="expand_contract" style="text-decoration: none" href="#">+/-</a>',
				lang('comment'),
				lang('entry_title'),
				lang('channel'),
				lang('name'),
				lang('email'),
				lang('date'),
				lang('ip_address'),
				lang('status'),
				array('data' => '', 'class' => 'hidden_col'),
				array('data' => form_checkbox('toggle_comments', 'true', FALSE, 'class="toggle_comments"'), 'style' => 'width: 5%;')
			);

			
			$this->table->set_heading($heading);
			
			if (count($comments) > 0)
			{
				foreach ($comments as $comment)
				{
					$row = array(
					'--',	
						
					"<a class='less_important_link' href='{$comment['edit_url']}'>{$comment['comment']}</a>",

					"<a class='less_important_link' href='{$comment['entry_search_url']}'>{$comment['entry_title']}</a>",
						
					array('data' => '', 'class' => 'hidden_col'),
	
					"<a class='less_important_link'  href='{$comment['name_search_url']}'>{$comment['name']}</a>",
					
					"<a class='less_important_link'  href='{$comment['email_search_url']}'>{$comment['email']}</a>",					
					

						$comment['date'],
						
						"<a class='less_important_link' href='{$comment['ip_search_url']}'>{$comment['ip_address']}</a>",
						
						"<a class='less_important_link' href='{$comment['status_search_url']}'>{$comment['status_label']}</a>",	
						
						array('data' => '', 'class' => 'hidden_col'),
																	
						form_checkbox('toggle[]', $comment['comment_id'], FALSE, 'class="comment_toggle"')
					);

					$this->table->add_row($row);
				}
			}
			
			echo $this->table->generate();
			?>



<div class="tableFooter">
	<div class="tableSubmit">
				<?=form_submit('submit', lang('submit'), 'class="submit"').NBS.NBS?>
				<?=form_dropdown('action', $form_options, '', 'id="comment_action"').NBS.NBS?>
	</div>

	<span class="js_hide"><?=$pagination?></span>	
	<span class="pagination" id="filter_pagination"></span>
</div>	
		
		<?=form_close()?>

