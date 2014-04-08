<?php extend_template('default-nav'); ?>

<div class="col w-12 last">
	<div class="box">
		<h1><?=$cp_page_title?></h1>
		<?=form_open(cp_url('utility/clear_caching'), 'class="settings"')?>
			<fieldset class="col-group last">
				<div class="setting-txt col w-8">
					<h3><?=lang('caches_to_clear')?></h3>
					<em><?=lang('caches_to_clear_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<label class="choice block">
						<input type="checkbox" value="page"> <?=lang('templates')?>
					</label>
					<label class="choice block">
						<input type="checkbox" value="tag"> <?=lang('tags')?>
					</label>
					<label class="choice block">
						<input type="checkbox" value="db"> <?=lang('database')?>
					</label>
					<label class="choice block chosen">
						<input type="checkbox" value="all" checked="checked"> <?=lang('all')?>
					</label>
				</div>
			</fieldset>

			<fieldset class="form-ctrls">
				<input class="btn" type="submit" value="<?=lang('btn_clear_caches')?>" data-work-text="<?=lang('btn_clear_caches_working')?>">
				<input class="btn disable" type="submit" value="Fix Errors, Please">
				<input class="btn work" type="submit" value="Clearing...">
			</fieldset>
		<?=form_close()?>
	</div>
</div>