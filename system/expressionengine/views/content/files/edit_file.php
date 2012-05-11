<?php extend_template('default') ?>

<?=form_open('C=content_files'.AMP.'M=edit_file', 'id="publishForm"', $form_hiddens)?>
	<ul class="tab_menu" id="tab_menu_tabs">
		<?php foreach ($tabs as $index => $tab): ?>
			<li id="menu_<?=$tab?>" title="<?=$tab?>" class="content_tab <?=($index == 0) ? 'current' : ''?>">
				<a href="#" title="menu_<?=$tab?>" class="menu_<?=$tab?>"><?=lang($tab)?></a>&nbsp;
			</li>
		<?php endforeach ?>
	</ul>
	<div id="holder">
		<div id="file_metadata" class="main_tab group">
			<?php foreach ($fields as $field_name => $field): ?>
				<div class="publish_field publish_<?=$field['type']?>" style="width: 100%; ">
					<label class="hide_field">
						<span>
							<?php if (isset($field['required']) AND $field['required']): ?>
								<em class="required">* </em>
							<?php endif ?>
							<?=lang($field_name)?>
						</span>
					</label>
					<div id="sub_hold_field_title">
						<fieldset class="holder">
							<?=$field['field']?>
							<?=form_error($field_name)?>
						</fieldset>
					</div> <!-- /sub_hold_field -->
				</div>
			<?php endforeach ?>
		</div> <!-- #file_metadata -->
		<?php if (isset($categories)): ?>
			<div id="categories" class="main_tab js_hide group">
				<div class="publish_field publish_multiselect" id="hold_field_category" style="width: 100%; ">
					<label class="hide_field">
						<span>Categories</span>
					</label>
					<div id="sub_hold_field_category">
						<fieldset class="holder">
							<?= $categories['category']['string_override'] ?>
						</fieldset>
					</div> <!-- /sub_hold_field -->
				</div> <!-- /publish_field -->
			</div> <!-- #categories -->
		<?php endif ?>
	</div> <!-- #holder -->
	<ul id="publish_submit_buttons">
		<li><input type="submit" class="submit" name="save_file" id="save_file" value="<?=lang('save_file')?>" /></li>
	</ul>
<?=form_close()?>