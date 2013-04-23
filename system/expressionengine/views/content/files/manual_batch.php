<?php extend_template('default') ?>

<?php if ($files_count === 0): ?>
	<p><?=lang('no_results')?></p>
<?php else: ?>
	<h4><?=$count_lang?></h4>
	<?=form_open($form_action, $form_hidden)?>
	<table class="mainTable padTable" cellspacing="0" cellpadding="0" border="0">
		<thead>
			<tr>
				<th><?=lang('thumbnail')?></th>
				<th><?=lang('title')?></th>
				<th><?=lang('description')?></th>
				<th style="width:10%"><?=lang('include')?> <?=form_checkbox('include')?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($files as $file): ?>
			<tr class="<?=alternator('even', 'odd')?>">
				<td><img src="<?=$file['image']?>"><br>
					<?=$file['name']?></td>
				<td><?=form_input('file_name', $file['name'])?></td>
				<td><?=form_textarea(array(
					'name'		=> 'test',
					'cols'		=> 8,
					'rows'		=> 3
				))?></td>
				<td><?=form_checkbox('include')?></td>						
			</tr>
		<?php endforeach; ?>
		</tbody>
		
	</table>
	<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
	</form>
<?php endif; ?>
