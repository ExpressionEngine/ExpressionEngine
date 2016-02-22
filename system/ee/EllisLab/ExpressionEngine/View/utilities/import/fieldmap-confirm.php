<?php $this->extend('_templates/default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open(ee('CP/URL')->make('utilities/import-converter/import-code-output'), '', $form_hidden)?>
		<h1><?=$cp_page_title?></h1>
		<div class="alert inline warn">
			<?php if ($form_hidden['encrypt'] == TRUE): ?>
				<p><?=lang('plaintext_passwords')?></p>
			<?php else: ?>
				<p><?=lang('encrypted_passwords')?></p>
			<?php endif ?>
		</div>
		<table cellspacing="0">
			<thead>
				<tr>
					<th class="first">Your Data</th>
					<th class="last">New Fields</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($fields[0] as $key => $value): ?>
					<tr>
						<td><?=$value?></td>
						<td><?=$paired['field_'.$key]?></td>
					</tr>
				<?php endforeach ?>
			</tbody>
		</table>

		<fieldset class="form-ctrls">
			<?=cp_form_submit('btn_create_file', 'btn_create_file_working')?>
		</fieldset>
	</form>
</div>