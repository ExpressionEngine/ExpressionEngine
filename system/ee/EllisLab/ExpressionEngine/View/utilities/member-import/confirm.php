<?php $this->extend('_templates/default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open(ee('CP/URL')->make('utilities/member-import/process-xml'), '', $form_hidden)?>
		<h1><?=$cp_page_title?></h1>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php if ($added_fields && count($added_fields) > 0):?>
			<div class="alert inline success">
				<h3><?=lang('new_fields_success')?></h3>
				<p><?=implode('<br />', $added_fields)?></p>
			</div>
		<?php endif;?>
		<div class="alert inline warn">
			<?=lang(lang('confirm_import_warning'))?>
		</div>
		<table cellspacing="0">
			<thead>
				<tr>
					<th class="first"><?=lang('option')?></th>
					<th class="last"><?=lang('value')?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?=lang('mbr_xml_file')?></td>
					<td><?=$xml_file?></td>
				</tr>
				<tr class="alt">
					<td><?=lang('member_group')?></td>
					<td><?=$default_group_id?></td>
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

		<fieldset class="form-ctrls">
			<?=cp_form_submit('confirm_import', 'btn_confirm_import_working')?>
		</fieldset>
	</form>
</div>
