<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="form-standard">
	<?=form_open(ee('CP/URL')->make('utilities/query'), 'class="ajax-validate"')?>
		<div class="form-btns form-btns-top">
			<h1><?=lang('sql_query_form_abbr')?></h1>
		</div>
		<?=ee('CP/Alert')
			->makeInline()
			->asImportant()
			->addToBody(lang('sql_warning'))
			->render()?>
		<?php if (isset($invalid_query)): ?>
			<?=ee('CP/Alert')
				->makeInline()
				->asIssue()
				->withTitle(lang('query_form_error'))
				->addToBody(htmlentities($invalid_query, ENT_QUOTES, 'UTF-8'))
				->render()?>
		<?php endif ?>
		<?=ee('CP/Alert')->getAllInlines()?>
		<fieldset>
			<div class="field-instruct">
				<label><?=lang('common_queries')?></label>
				<em><?=lang('common_queries_desc')?><br>
					<?php
					$status_query =	array(
						'thequery' => rawurlencode(base64_encode('SHOW STATUS')),
						'signature' => ee('Encrypt')->sign('SHOW STATUS')
					);
					$variables_query = array(
						'thequery' => rawurlencode(base64_encode('SHOW VARIABLES')),
						'signature' => ee('Encrypt')->sign('SHOW VARIABLES')
					);
					$process_query = array(
						'thequery' => rawurlencode(base64_encode('SHOW PROCESSLIST')),
						'signature' => ee('Encrypt')->sign('SHOW PROCESSLIST')
					);
					?>
					<a href="<?=ee('CP/URL')->make('utilities/query/run-query', $status_query)?>">SHOW STATUS</a>,
					<a href="<?=ee('CP/URL')->make('utilities/query/run-query', $variables_query)?>">SHOW VARIABLES</a>,
					<a href="<?=ee('CP/URL')->make('utilities/query/run-query', $process_query)?>">SHOW PROCESSLIST</a></em>
			</div>
		</fieldset>
		<fieldset class="fieldset-required <?=form_error_class('thequery')?>">
			<div class="field-instruct">
				<label><?=lang('sql_query_to_run')?></label>
			</div>
			<div class="field-control">
				<textarea class="has-format-options" name="thequery" cols="" rows="10"><?=set_value('thequery')?></textarea>
				<?=form_error('thequery')?>
			</div>
		</fieldset>

		<div class="form-btns">
			<?=cp_form_submit('query_btn', 'query_btn_saving')?>
		</div>
	</form>
</div>
