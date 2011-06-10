
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment')?>
	<?php // The inline style is here so as to not add extra muck to the globa.css for now.  -ga ?>
	<fieldset style="margin-bottom:15px">
		<legend><?=lang('filter_comments')?></legend>
		<div class="group">
			<?=form_dropdown('channel_id', $channel_select_opts, $channel_selected, 'id="f_channel_id"').NBS.NBS?>
			<?=form_dropdown('status', $status_select_opts, $status_selected, 'id="f_status"').NBS.NBS?>
			<?=form_dropdown('date_range', $date_select_opts, $date_selected, 'id="date_range"').NBS.NBS?>
			<?=form_submit('submit', lang('search'), 'class="submit" id="search_button"')?>
		</div>
	</fieldset>
<?=form_close()?>



<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
	<thead>	
		<tr>
			<th><a id="expand_contract" style="text-decoration: none" href="#">+/-</a></th>
			<th><?=lang('comment')?></th>
			<th><?=lang('entry_title')?></th>
			<th><?=lang('name')?></th>
			<th><?=lang('email')?></th>
			<th><?=lang('date')?></th>
			<th><?=lang('ip_address')?></th>
			<th><?=lang('status')?></th>
			<th><?=form_checkbox('toggle_comments', 'true', FALSE, 'class="toggle_comments"')?></th>
		</tr>
	</thead>
	<tbody>
	<?php if ( ! $comments): ?>
		<tr>
			<td colspan="8"><?=lang('no_results')?></td>
		</tr>
	<?php else: ?>
		<?php foreach ($comments as $comment): ?>
		<tr>
			<td>--</td>
			<td><?=$comment->comment_edit_link?></td>
			<td><?=$comment->entry_title?></td>
			<td><?=$comment->name?></td>
			<td><?=$comment->email?></td>
			<td><?=$this->localize->set_human_time($comment->comment_date)?></td>
			<td><?=$comment->ip_address?></td>
			<td><?=$comment->status?></td>
			<td><?=form_checkbox('toggle[]', $comment->comment_id, FALSE, 'class="comment_toggle"')?></td>
		</tr>
		<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>


<?=$pagination?>