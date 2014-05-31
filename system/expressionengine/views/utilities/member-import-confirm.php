<?php extend_template('default-nav'); ?>

<div class="col w-12 last">
	<ul class="breadcrumb">
		<li><a href="<?=cp_url('utilities/member-import')?>"><?=lang('member_import')?></a></li>
		<li class="last"><?=$cp_page_title?></li>
	</ul>
	<div class="box">
		<form class="tbl-ctrls">
			<fieldset class="tbl-search right">
			</fieldset>
			<h1><?=$cp_page_title?></h1>
			<div class="alert inline warn">
				<?=lang(lang('confirm_import'))?>
			</div>
			<table cellspacing="0">
				<tr>
					<th class="first"><?=lang('option')?></th>
					<th class="last"><?=lang('value')?></th>
				</tr>
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
					<td><?=lang('mbr_timezone')?></td>
					<td><?=$timezones?></td>
				</tr>
				<tr>
					<td><?=lang('mbr_datetime_fmt')?></td>
					<td><?=$date_format?>, <?=$time_format?></td>
				</tr>
				<tr class="alt last">
					<td class="first"><?=lang('mbr_create_custom_fields')?></td>
					<td class="last"><?=$auto_custom_field?></td>
				</tr>
			</table>

			<fieldset class="form-ctrls">
				<?=cp_form_submit('confirm_import', 'btn_confirm_import_working')?>
			</fieldset>
		</form>
	</div>
</div>