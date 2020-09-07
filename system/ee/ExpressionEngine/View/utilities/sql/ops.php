<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>
<div class="panel">
<div class="title-bar title-bar--large">
	<h2 class="title-bar__title"><?=lang('sql_manager_abbr')?></h2>
</div>

	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?=$cp_page_title?></h2>

			<div class="title-bar__extra-tools">
				<div class="search-input">
					<input class="search-input__input input--small" placeholder="<?=lang('search')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
				</div>
			</div>
		</div>
		<?php $this->embed('_shared/table', $table);?>
	</form>
	</div>
</div>