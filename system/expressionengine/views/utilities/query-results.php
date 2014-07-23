<?php extend_template('default-nav', 'outer_box'); ?>

<div class="box mb">
	<h1><?=lang('sql_query_form_abbr')?></h1>
	<div class="txt-wrap">
		<ul class="checklist">
			<li><?=$thequery?></li>
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
<div class="box">
	<?=form_open($base_url, 'class="tbl-ctrls"')?>
		<fieldset class="tbl-search right">
			<input placeholder="type phrase..." type="text" name="search" value="<?=$search?>">
			<input class="btn submit" type="submit" value="search table">
		</fieldset>
		<h1><?=$cp_page_title?></h1>
		<?=$table?>
		<?php $this->view('_shared/pagination'); ?>
	</form>
</div>