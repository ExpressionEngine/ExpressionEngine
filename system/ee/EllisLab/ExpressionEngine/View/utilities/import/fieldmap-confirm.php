<?php $this->extend('_templates/default-nav'); ?>

<h1><?=$cp_page_title?></h1>
<div class="tbl-ctrls">
	<?=form_open(ee('CP/URL')->make('utilities/import-converter/import-code-output'), '', $form_hidden)?>
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

		<div class="form-btns">
			<?=cp_form_submit('btn_assign_fields', 'btn_saving')?>
		</div>
	</form>
</div>
