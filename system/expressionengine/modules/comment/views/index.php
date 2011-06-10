



<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
	<thead>	
		<tr>
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
			<td><?=$comment->comment?></td>
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