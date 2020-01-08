<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="title-bar title-bar--large">
	<h2 class="title-bar__title"><?=lang('sql_query_abbr')?></h2>
</div>

<div class="typography">

<pre><code><?=htmlentities($thequery, ENT_QUOTES, 'UTF-8');?></code></pre>

<p>
	<?php if ($write): ?>
		<b><?=lang('affected_rows')?>:</b> <span class="badge badge--info"><?=$affected?></span>
	<?php else: ?>
		<b><?=lang('total_results')?>:</b> <span class="badge badge--info"><?=$total_results?></span>
	<?php endif ?>
</p>

</div>

	<div class="tbl-ctrls">
		<?=form_open($table['base_url'])?>
			<?php if ( ! $write): ?>
				<fieldset class="tbl-search right">
					<input placeholder="<?=lang('search')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
				</fieldset>
			<?php endif ?>
			<h1><?=(isset($table_heading)) ? $table_heading : $cp_page_title?></h1>
			<?php $this->embed('_shared/table', $table); ?>
			<?=$pagination?>
		</form>
	</div>
