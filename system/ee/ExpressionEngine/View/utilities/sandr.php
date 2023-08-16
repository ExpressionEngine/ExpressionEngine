<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>
<div class="panel">
<div class="form-standard">
	<?=form_open(ee('CP/URL')->make('utilities/sandr'), 'class="ajax-validate"')?>
  <div class="panel-heading">
    <div class="title-bar">
			<h3 class="title-bar__title"><?=$cp_page_title?></h3>

			<div class="title-bar__extra-tools">
			<?=cp_form_submit('btn_sandr', 'btn_sandr_working')?>
			</div>
		</div>
  </div>

  <div class="panel-body">

		<?=ee('CP/Alert')
		    ->makeInline()
		    ->asImportant()
		    ->addToBody(lang('sandr_warning'))
		    ->render()?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<fieldset class="fieldset-required <?=form_error_class('search_term')?>">
			<div class="field-instruct">
				<label for="search_term"><?=lang('sandr_search_text')?></label>
			</div>
			<div class="field-control">
				<textarea name="search_term" cols="" rows="" id="search_term"><?=set_value('search_term')?></textarea>
				<?=form_error('search_term')?>
			</div>
		</fieldset>
		<fieldset class="<?=form_error_class('replace_term')?>">
			<div class="field-instruct">
				<label for="replace_term"><?=lang('sandr_replace_text')?></label>
			</div>
			<div class="field-control">
				<textarea name="replace_term" cols="" rows="" id="replace_term"><?=set_value('replace_term')?></textarea>
				<?=form_error('replace_term')?>
			</div>
		</fieldset>
		<fieldset class="fieldset-required <?=form_error_class('replace_where')?>">
			<div class="field-instruct">
				<label for="replace_where"><?=lang('sandr_in')?></label>
				<em><?=lang('sandr_in_desc')?></em>
			</div>
			<div class="field-control">
				<select name="replace_where" id="replace_where">
					<?php foreach ($replace_options as $label => $option): ?>
						<option value="">----</option>
						<?php if (! isset($option['choices'])): ?>
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
    <fieldset class="fieldset-required <?=form_error_class('password_auth')?>">
      <div class="field-instruct">
        <label for="password_auth"><?=lang('current_password')?></label>
        <em><?=lang('sandr_password_desc')?></em>
      </div>
      <div class="field-control">
        <input name="password_auth" type="password" value="" id="password_auth">
        <?=form_error('password_auth')?>
      </div>
    </fieldset>
  </div>
<div class="panel-footer">
		<div class="form-btns">

			<?=cp_form_submit('btn_sandr', 'btn_sandr_working')?>
		</div>
  </div>
	</form>
</div>
</div>
