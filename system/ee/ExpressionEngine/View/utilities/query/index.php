<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>
<div class="panel">
<div class="form-standard">
	<?=form_open(ee('CP/URL')->make('utilities/query'), 'class="ajax-validate"')?>

  <div class="panel-heading">
    <div class="title-bar title-bar--large">
			<h3 class="title-bar__title"><?=lang('sql_query_form_abbr')?></h3>
		</div>
  </div>
<div class="panel-body">
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

		<fieldset class="fieldset-required <?=form_error_class('thequery')?>">
			<div class="field-instruct">
				<label><?=lang('query')?></label>
			</div>
			<div class="field-control">
				<textarea class="js-sql-query-textarea" name="thequery" cols="" rows="10" aria-label="<?=lang('textarea_query')?>"><?=set_value('thequery')?></textarea>
				<?=form_error('thequery')?>

				<div class="field-instruct">
					<div class="button-group button-group-xsmall">
						<button type="button" class="button button--default font-monospace" onclick="insertIntoSQlQueryForm('SELECT * FROM `exp_` WHERE 1');">SELECT *</button>
						<button type="button" class="button button--default font-monospace" onclick="insertIntoSQlQueryForm('INSERT INTO `exp_` (``) VALUES ()');">INSERT</button>
						<button type="button" class="button button--default font-monospace" onclick="insertIntoSQlQueryForm('UPDATE `exp_` SET ``=\'\' WHERE 1');">UPDATE</button>
						<button type="button" class="button button--default font-monospace" onclick="insertIntoSQlQueryForm('SHOW VARIABLES');">SHOW VARIABLES</button>
						<button type="button" class="button button--default font-monospace" onclick="insertIntoSQlQueryForm('SHOW STATUS');">SHOW STATUS</button>
						<button type="button" class="button button--default font-monospace" onclick="insertIntoSQlQueryForm('SHOW PROCESSLIST');">SHOW PROCESSLIST</button>
					</div>
				</div>
			</div>
		</fieldset>
  </div>

<div class="panel-footer">
		<div class="form-btns">
			<?=cp_form_submit('query_btn', 'query_btn_saving')?>
		</div>
  </div>
	</form>
</div>
</div>