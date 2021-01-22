<?php $this->extend('_templates/default-nav'); ?>

<h1><?=$cp_page_title?></h1>
<?=form_open(ee('CP/URL')->make('utilities/member-import/create-custom-fields'), 'class="settings"', $form_hidden)?>
	<div class="form-btns form-btns-top">
		<?=cp_form_submit('btn_add_fields', 'btn_add_fields_working')?>
	</div>
	<?=ee('CP/Alert')->getAllInlines()?>
	<fieldset class="grid-publish col-group last">
		<div class="setting-txt col w-16">
			<h3><?=lang('map_custom_fields')?></h3>
			<em><?=lang('map_custom_fields_desc')?></em>
		</div>
		<div class="setting-field col w-16 last">
			<div class="tbl-wrap">
				<table class="grid-input-form" cellespacing="0">
					<thead>
						<tr>
							<th class="first check-ctrl">
								<?=form_checkbox('select_all', 'true', set_checkbox('select_all', 'true'), 'title="' . lang('select_all') . '"')?>
							</th>
							<th><?=lang('field_name')?></th>
							<th><?=lang('field_label')?></th>
							<th><?=lang('field_required')?></th>
							<th><?=lang('field_public')?></th>
							<th class="last"><?=lang('field_registration')?></th>
						</tr>
					</thead>
					<tbody>
						<?php
                        $i = 0;
                        foreach ($new_fields as $value): ?>
							<tr class="last">
								<td class="first">
									<?=form_checkbox('create_ids[' . $i . ']', 'y', false, 'class="toggle"')?>
									<input type="hidden" name="<?='m_field_order[' . $i . ']'?>" value="<?=$order_start + $i?>">
								</td>
								<td>
									<?=form_input(array(
									    'name' => 'm_field_name[' . $i . ']',
									    'value' => set_value('m_field_name[' . $i . ']', $value)
									))?>
								</td>
								<td>
									<?=form_input(array(
									    'name' => 'm_field_label[' . $i . ']',
									    'value' => set_value('m_field_name[' . $i . ']', $value)
									))?>
								</td>
								<?php foreach (array('required', 'public', 'reg_form') as $field): ?>
									<td>
										<label class="choice yes">
											<?=form_checkbox($field . '[' . $i . ']', 'y', set_checkbox($field . '[' . $i . ']', 'y'))?> <?=lang('yes')?>
										</label>
									</td>
								<?php endforeach ?>
							</tr>
						<?php $i++; endforeach ?>
					</tbody>
				</table>
			</div>
		</div>
	</fieldset>
	<div class="form-btns">
		<?=cp_form_submit('btn_add_fields', 'btn_add_fields_working')?>
	</div>
</form>
