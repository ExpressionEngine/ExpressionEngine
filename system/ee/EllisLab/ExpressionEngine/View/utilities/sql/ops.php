<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<fieldset class="tbl-search right">
			<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
			<input class="btn submit" type="submit" name="search_form" value="<?=lang('search_table')?>">
		</fieldset>
		<h1><?=$cp_page_title?></h1>
		<?php $this->embed('_shared/table', $table);?>
	</form>
</div>
