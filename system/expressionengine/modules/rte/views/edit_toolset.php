<div class="box">
	<h1><?=lang($header)?> <span class="required intitle">&#10033; Required Fields</span></h1>
	<?=form_open($form_action, 'class="settings"')?>
		<?php $this->ee_view('_shared/alerts')?>
		<fieldset class="col-group">
			<div class="setting-txt col w-8">
				<h3><?=lang('tool_set_name')?> <span class="required" title="required field">&#10033;</span></h3>
				<em><?=lang('tool_set_name_desc')?></em>
			</div>
			<div class="setting-field col w-8 last">
				<input class="required" type="text" name="toolset_name" value="<?=set_value('toolset_name', $toolset_name)?>">
			</div>
		</fieldset>
		<fieldset class="col-group last">
			<div class="setting-txt col w-8">
				<h3><?=lang('choose_tools')?></h3>
				<em><?=lang('choose_tools_desc')?></em>
			</div>
			<div class="setting-field col w-8 last">
				<?php foreach ($tools as $tool): ?>
				<label class="choice block<?php if ($tool['selected']) echo " chosen"; ?>">
					<input type="checkbox" name="tools[]" value="<?=$tool['id']?>"<?php if ($tool['selected']) echo ' checked="checked"'; ?>> <?=$tool['name']?> <?php if ($tool['desc']): ?><i>&mdash; <?=$tool['desc']?></i><?php endif; ?>
				</label>
				<?php endforeach; ?>
			</div>
		</fieldset>
		<fieldset class="form-ctrls">
			<?=cp_form_submit($btn_save_text, 'btn_save_settings_working')?>
		</fieldset>
	<?=form_close();?>
</div>