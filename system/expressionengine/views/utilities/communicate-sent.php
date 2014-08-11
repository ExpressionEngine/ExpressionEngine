<?php extend_template('default-nav'); ?>

<?=form_open(cp_url('utilities/communicate/sent'), 'class="tbl-ctrls"')?>
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<?php $this->view('_shared/alerts')?>
	<table cellspacing="0">
		<tr>
			<th<?php if($highlight == 'subject'): ?> class="highlight"<?php endif; ?>><?=lang('subject')?> <a href="<?=$subject_sort_url?>" class="ico sort <?=$subject_direction?> right"></a></th>
			<th<?php if($highlight == 'date'): ?> class="highlight"<?php endif; ?>><?=lang('date')?> <a href="<?=$date_sort_url?>" class="ico sort <?=$date_direction?> right"></a></th>
			<th<?php if($highlight == 'total_sent'): ?> class="highlight"<?php endif; ?>><?=lang('total_sent')?> <a href="<?=$total_sent_sort_url?>" class="ico sort <?=$total_sent_direction?> right"></a></th>
			<th<?php if($highlight == 'status'): ?> class="highlight"<?php endif; ?>><?=lang('status')?> <a href="<?=$status_sort_url?>" class="ico sort <?=$status_direction?> right"></a></th>
			<th><?=lang('manage')?></th>
			<th class="check-ctrl"><input type="checkbox" title="<?=strtolower(lang('select_all'))?>"></th>
		</tr>

		<?php foreach($emails as $email): ?>
		<tr>
			<td><?=$email['subject']?></td>
			<td><?=$email['date']?></td>
			<td><?=$email['total_sent']?></td>
			<td><?=$email['status']?></td>
			<td>
				<ul class="toolbar">
					<li class="view"><a class="m-link" rel="modal-email" href="" title="<?=lang('view_email')?>"></a></li>
					<li class="sync"><a href="<?=cp_url('utilities/communicate/resend/' . $email['id'])?>" title="<?=lang('resend')?>"></a></li>
				</ul>
			</td>
			<td><input type="checkbox" name="selection[]" value="<?=$email['id']?>"></td>
		</tr>
		<?php endforeach; ?>

	</table>

	<?php $this->view('_shared/pagination'); ?>

	<fieldset class="tbl-bulk-act">
		<select name="bulk_action">
			<option value="">-- <?=lang('with_selected')?> --</option>
			<option value="remove"><?=lang('remove')?></option>
		</select>
		<input class="btn submit" type="submit" value="<?=lang('submit')?>">
	</fieldset>
<?=form_close()?>