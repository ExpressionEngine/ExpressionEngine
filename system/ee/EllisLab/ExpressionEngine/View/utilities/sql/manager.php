<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="title-bar title-bar--large">
	<h2 class="title-bar__title"><?=lang('sql_manager_abbr')?></h2>
</div>

<div class="typography">
	<pre><code><?=lang('mysql')?> <?=$sql_version?> / <b><?=lang('total_records')?>:</b> <?=$records?> / <b><?=lang('size')?>: </b><?=$size?>
<br><b><?=lang('uptime')?>:</b> <?=$database_uptime?></code></pre>
</div>

	<div class="tbl-ctrls">
		<?=form_open($table['base_url'])?>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
			<fieldset class="tbl-search right">
				<input placeholder="<?=lang('search')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
			</fieldset>

			<div class="title-bar">
				<h2 class="title-bar__title"><?=$table_heading?></h2>
			</div>

			<?php $this->embed('_shared/table', $table); ?>
			<fieldset class="bulk-action-bar hidden">
				<select name="table_action">
					<option value="none">-- <?=lang('with_selected')?> --</option>
					<option value="REPAIR"><?=lang('repair')?></option>
					<option value="OPTIMIZE"><?=lang('optimize')?></option>
				</select>
				<input class="button button--primary" type="submit" value="<?=lang('submit')?>">
			</fieldset>
		</form>
	</div>
