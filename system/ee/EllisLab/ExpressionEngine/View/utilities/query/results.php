<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box mb">
	<h1><?=lang('sql_query_abbr')?></h1>
	<div class="txt-wrap">
		<ul class="checklist">
			<li><?=htmlentities($thequery, ENT_QUOTES, 'UTF-8');?></li>
			<li class="last">
				<?php if ($write): ?>
					<b><?=lang('affected_rows')?>:</b> <?=$affected?>
				<?php else: ?>
					<b><?=lang('total_results')?>:</b> <?=$total_results?>
				<?php endif ?>
			</li>
		</ul>
	</div>
</div>
<div class="box table-list-wrap">
	<div class="tbl-ctrls">
		<?=form_open($table['base_url'])?>
			<?php if ( ! $write): ?>
				<fieldset class="tbl-search right">
					<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
					<input class="btn submit" type="submit" value="<?=lang('search_table')?>">
				</fieldset>
			<?php endif ?>
			<h1><?=(isset($table_heading)) ? $table_heading : $cp_page_title?></h1>
			<?php $this->embed('_shared/table', $table); ?>
			<?=$pagination?>
		</form>
	</div>
</div>
