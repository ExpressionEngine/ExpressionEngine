<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="form-standard">
	<?=form_open(ee('CP/URL')->make('utilities/sandr'), 'class="ajax-validate"')?>
		<div class="form-btns form-btns-top">
			<h1><?=$cp_page_title?></h1>
			<?=cp_form_submit('btn_sandr', 'btn_sandr_working')?>
		</div>
		<?=ee('CP/Alert')
			->makeInline()
			->asImportant()
			->addToBody(lang('sandr_warning'))
			->render()?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<fieldset class="fieldset-required <?=form_error_class('search_term')?>">
			<div class="field-instruct">
				<label><?=lang('sandr_search_text')?></label>
			</div>
			<div class="field-control">
				<textarea name="search_term" cols="" rows=""><?=set_value('search_term')?></textarea>
				<?=form_error('search_term')?>
			</div>
		</fieldset>
		<fieldset class="<?=form_error_class('replace_term')?>">
			<div class="field-instruct">
				<label><?=lang('sandr_replace_text')?></label>
			</div>
			<div class="field-control">
				<textarea name="replace_term" cols="" rows=""><?=set_value('replace_term')?></textarea>
				<?=form_error('replace_term')?>
			</div>
		</fieldset>
		<fieldset class="fieldset-required <?=form_error_class('replace_where')?>">
			<div class="field-instruct">
				<label><?=lang('sandr_in')?></label>
				<em><?=lang('sandr_in_desc')?></em>
			</div>
			<div class="field-control">
				<select name="replace_where">
					<?php foreach ($replace_options as $label => $option): ?>
						<option value="">----</option>
						<?php if ( ! isset($option['choices'])): ?>
							<option value="<?=$label?>"<?=set_select('replace_where', $label)?>><?=$option['name']?></option>
						<?php else: ?>
							<option value=""><?=$option['name']?> <?=lang('choose_below')?></option>
							<?php foreach ($option['choices'] as $value => $text): ?>
							<option value="<?=$value?>" <?=set_select('replace_where', $value)?>>&nbsp;&nbsp;&nbsp;&nbsp;<?=$text?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
				<?=form_error('replace_where')?>
			</div>
		</fieldset>

		<div class="form-btns form-btns-auth">
			<fieldset class="fieldset-required <?=form_error_class('password_auth')?>">
				<div class="field-instruct">
					<label><?=lang('current_password')?></label>
					<em><?=lang('sandr_password_desc')?></em>
				</div>
				<div class="field-control">
					<input name="password_auth" type="password" value="">
					<?=form_error('password_auth')?>
				</div>
			</fieldset>
			<?=cp_form_submit('btn_sandr', 'btn_sandr_working')?>
		</div>
	</form>
</div>
