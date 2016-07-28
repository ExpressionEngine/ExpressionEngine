<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box mb">
	<h1><?=lang('sql_manager_abbr')?></h1>
	<div class="txt-wrap">
		<ul class="checklist">
			<li><?=lang('mysql')?> <?=$sql_version?> / <b><?=lang('total_records')?>:</b> <?=$records?> / <b><?=lang('size')?>: </b><?=$size?></li>
			<li class="last"><b><?=lang('uptime')?>:</b> <?=$database_uptime?></li>
		</ul>
	</div>
</div>
<div class="box table-list-wrap">
	<div class="tbl-ctrls">
		<?=form_open($table['base_url'])?>
			<?=ee('CP/Alert')->getAllInlines()?>
			<fieldset class="tbl-search right">
				<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
				<input class="btn submit" type="submit" name="search_form" value="<?=lang('search_tables')?>">
			</fieldset>
			<h1><?=$table_heading?></h1>
			<?php $this->embed('_shared/table', $table); ?>
			<fieldset class="tbl-bulk-act hidden">
				<select name="table_action">
					<option value="none">-- <?=lang('with_selected')?> --</option>
					<option value="REPAIR"><?=lang('repair')?></option>
					<option value="OPTIMIZE"><?=lang('optimize')?></option>
				</select>
				<input class="btn submit" type="submit" value="<?=lang('submit')?>">
			</fieldset>
		</form>
	</div>
</div>
