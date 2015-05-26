		<div id="filterMenu">

			<?php if ($message != ''):?><p class="notice"><?=$message?></p><?php endif?>

			<?=form_open($search_form, array('name'=>'filterform', 'id'=>'filterform'), $search_form_hidden)?>

				<div class="group">
					<?=form_dropdown('channel_id', $channel_select_options, $channel_selected).NBS.NBS?>
					<?=form_dropdown('cat_id', $category_select_options, $category_selected).NBS.NBS?>
					<?=form_dropdown('status', $status_select_options, $status_selected).NBS.NBS?>
					<?=form_dropdown('date_range', $date_select_options, $date_selected, 'id="date_range"').NBS.NBS?>
					<?=form_dropdown('order', $order_select_options, $order_selected).NBS.NBS?>
					<?=form_dropdown('perpage', $perpage_select_options, $perpage_selected)?>
				</div>

        		<div id="custom_date_picker" style="display: none; margin: 0 auto 50px auto;width: 385px; height: 185px; padding: 5px 15px 5px 15px;border: 1px solid black;  background: #FFF;">
					<div id="cal1" style="width:200px; float:left; text-align:center;">
						<p><?=lang('start_date', 'custom_date_start')?>:&nbsp; <input type="text" name="custom_date_start" id="custom_date_start" value="yyyy-mm-dd" size="12" tabindex="1" /></p>
						<span id="custom_date_start_span"></span>
					</div>
	                <div id="cal2" style="text-align:center;">
						<p><?=lang('end_date', 'custom_date_end')?>:&nbsp; <input type="text" name="custom_date_end" id="custom_date_end" value="yyyy-mm-dd" size="12" tabindex="2" /></p>
						<span id="custom_date_end_span"></span>          
					</div>
                </div>

				<div>
					<?=lang('keywords', 'keywords')?> <?=form_input($keywords).NBS.NBS?>
					<?=form_checkbox('exact_match', 'yes', $exact_match, 'id="exact_match"')?> <?=lang('exact_match', 'exact_match').NBS.NBS?>
					<?=form_dropdown('search_in', $search_in_options, $search_in_selected).NBS.NBS?>
					<?=form_submit('submit', lang('search'), 'class="submit" id="search_button"')?>
				</div>

			<?=form_close()?>
	
			</div> <!-- filterMenu -->

			<?php if ($total_count == 0):?>
				<div class="tableFooter">
					<p class="notice"><?=lang('no_entries_matching_that_criteria')?></p>
				</div>
			<?php else:?>

				<?=form_open($entries_form, '', $form_hidden)?>

				<?php
					$this->table->set_heading($table_headings);

					echo $this->table->generate($entries);
				?>
		<div class="tableFooter">
			<?php if ($autosave_show):?>
				<p class="notice"><?=required()?><?=lang('autosave_data_available')?></p>
			<?php endif;?>

			<div class="tableSubmit">
				<?=form_submit('submit', lang('submit'), 'class="submit"').NBS.NBS?>
				<?php if (is_array($action_options)):?>
				<?=form_dropdown('action', $action_options).NBS.NBS?>
				<?php endif;?>
			</div>

			<?=$pagination?>&nbsp;

		</div> <!-- tableFooter -->

			<?php endif; /* if $total_count > 0*/?>

		<?=form_close()?>

