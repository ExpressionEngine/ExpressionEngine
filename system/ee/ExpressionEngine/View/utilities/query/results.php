<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>
<div class="panel">
<div class="panel-heading">
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
</div>

<div class="panel-body">
	<?=form_open($table['base_url'])?>
		<div class="title-bar">
			<h1 class="title-bar__title"><?=(isset($table_heading)) ? $table_heading : $cp_page_title?></h1>

			<div class="title-bar__extra-tools">
			<?php if (! $write): ?>
				<div class="search-input">
					<input class="search-input__input input--small" placeholder="<?=lang('search')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
				</div>
			<?php endif ?>
			</div>
		</div>

		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
	</form>
</div>
