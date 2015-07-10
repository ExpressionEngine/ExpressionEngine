<?php extend_template('default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<fieldset class="tbl-search right">
			<input placeholder="<?=lang('type_phrase')?>" name="search" type="text" value="<?=$table['search']?>">
			<input class="btn submit" type="submit" name="search_form" value="<?=lang('search_table')?>">
		</fieldset>
		<h1><?=$cp_page_title?></h1>
		<?php $this->view('_shared/table', $table);?>
		<?=$pagination?>
	</form>
</div>