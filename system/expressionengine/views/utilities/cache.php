<?php extend_template('default-nav'); ?>

<div class="col w-12 last">
	<div class="box">
		<h1><?=$cp_page_title?></h1>
		<?=form_open(cp_url('utilities/cache'), 'class="settings ajax-validate"')?>
			<?php $this->view('_shared/form_messages')?>
			<fieldset class="col-group last">
				<div class="setting-txt col w-8">
					<h3><?=lang('caches_to_clear')?></h3>
					<em><?=lang('caches_to_clear_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<label class="choice block">
						<input type="checkbox" name="cache_type[]" value="page"> <?=lang('templates')?>
					</label>
					<label class="choice block">
						<input type="checkbox" name="cache_type[]" value="tag"> <?=lang('tags')?>
					</label>
					<label class="choice block">
						<input type="checkbox" name="cache_type[]" value="db"> <?=lang('database')?>
					</label>
					<label class="choice block chosen">
						<input type="checkbox" name="cache_type[]" value="all" checked="checked"> <?=lang('all')?>
					</label>
				</div>
			</fieldset>

			<fieldset class="form-ctrls">
				<?=cp_form_submit('btn_clear_caches', 'btn_clear_caches_working')?>
			</fieldset>
		<?=form_close()?>
	</div>
</div>