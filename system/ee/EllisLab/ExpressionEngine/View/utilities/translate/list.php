<?php $this->extend('_templates/default-nav'); ?>

<?=form_open($table['base_url'])?>

	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

	<div class="title-bar">
		<h2 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h2>

		<div class="title-bar__extra-tools">
			<div class="search-input">
				<input class="search-input__input" placeholder="<?=lang('search')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
			</div>
		</div>
	</div>


	<?php $this->embed('_shared/table', $table); ?>

	<?=$pagination?>

	<?php if ( ! empty($table['data'])): ?>
	<fieldset class="bulk-action-bar hidden">
		<select name="bulk_action">
			<option value="">-- <?=lang('with_selected')?> --</option>
			<option value="export"><?=lang('export_download')?></option>
		</select>
		<input class="button button--primary" type="submit" value="<?=lang('submit')?>">
	</fieldset>
	<?php endif; ?>
<?=form_close()?>
