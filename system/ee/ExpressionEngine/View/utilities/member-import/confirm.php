<?php $this->extend('_templates/default-nav'); ?>

<h1><?=$cp_page_title?></h1>
<div class="tbl-ctrls">
	<?=form_open(ee('CP/URL')->make('utilities/member-import/process-xml'), '', $form_hidden)?>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php if ($added_fields && count($added_fields) > 0):?>
			<?=ee('CP/Alert')
	    ->makeInline()
	    ->asSuccess()
	    ->withTitle(lang('new_fields_success'))
	    ->addToBody($added_fields)
	    ->render()?>
		<?php endif;?>
		<?=ee('CP/Alert')
	    ->makeInline()
	    ->asImportant()
	    ->addToBody(lang('confirm_import_warning'))
	    ->render()?>
		<table cellspacing="0">
			<thead>
				<tr>
					<th class="first"><?=lang('option')?></th>
					<th class="last"><?=lang('value')?></th>
				</tr>
			</thead>
			<tbody>
				<tr class="alt">
					<td><?=lang('role')?></td>
					<td><?=$default_role_id?></td>
				</tr>
				<tr>
					<td><?=lang('mbr_language')?></td>
					<td><?=$language?></td>
				</tr>
				<tr class="alt">
					<td><?=lang('timezone')?></td>
					<td><?=$timezones?></td>
				</tr>
				<tr>
					<td><?=lang('mbr_datetime_fmt')?></td>
					<td><?=$date_format?>, <?=$time_format?></td>
				</tr>
				<tr>
					<td><?=lang('include_seconds')?></td>
					<td><?=$include_seconds?></td>
				</tr>
				<tr class="alt last">
					<td class="first"><?=lang('mbr_create_custom_fields')?></td>
					<td class="last"><?=$auto_custom_field?></td>
				</tr>
			</tbody>
		</table>
		<div class="form-btns">
			<?=cp_form_submit('confirm_import', 'btn_confirm_import_working')?>
		</div>
	</form>
</div>
