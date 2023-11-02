<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel">
<div class="panel-heading">
	<div class="title-bar title-bar--large">
		<h2 class="title-bar__title"><?=lang('sql_manager_abbr')?></h2>
	</div>

	<div class="typography">
		<pre><code><?=lang('mysql')?> <?=$sql_version?> / <b><?=lang('total_records')?>:</b> <?=$records?> / <b><?=lang('size')?>: </b><?=$size?>
	<br><b><?=lang('uptime')?>:</b> <?=$database_uptime?></code></pre>
	</div>
</div>

<div class="panel-body">
	<div class="tbl-ctrls">
		<?=form_open($table['base_url'])?>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

			<div class="title-bar">
				<h2 class="title-bar__title"><?=$table_heading?></h2>

				<div class="title-bar__extra-tools">
					<div class="search-input">
						<input class="search-input__input input--small" placeholder="<?=lang('search')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>" aria-label="<?=lang('search_input')?>">
					</div>
				</div>
			</div>

			<?php $this->embed('_shared/table', $table); ?>
			<?php $this->embed('ee:_shared/form/bulk-action-bar', [
			    'options' => [
			        [
			            'value' => "none",
			            'text' => '-- ' . lang('with_selected') . ' --'
			        ],
			        [
			            'value' => "REPAIR",
			            'text' => lang('repair')
			        ],
			        [
			            'value' => "OPTIMIZE",
			            'text' => lang('optimize')
			        ]
			    ]
			]); ?>
		</form>
	</div>
</div>
</div>