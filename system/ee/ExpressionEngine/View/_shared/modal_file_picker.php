<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<?php if (is_numeric($dir)): ?>
	<fieldset class="tbl-search right">
		<a class="btn tn action" href="<?=ee('CP/URL')->make("files/upload/$dir")?>">Upload New File</a>
	</fieldset>
	<?php endif ?>
	<h1>
		<?php if (is_numeric($dir)): ?>
		<ul class="toolbar">
			<li class="sync">
				<a href="<?=ee('CP/URL')->make("settings/upload/sync/$dir")?>" title="<?=lang('sync_directories')?>"></a>
			</li>
		</ul>
		<?php endif ?>
		<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
	</h1>

	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

	<?php if (isset($filters)) {
    echo $filters;
} ?>

	<?php $this->embed('_shared/table', $table); ?>

	<?php if (! empty($pagination)) {
    $this->embed('_shared/pagination', $pagination);
} ?>

	<?php if (! empty($table['data'])): ?>
	<?php $this->embed('ee:_shared/form/bulk-action-bar', [
	    'options' => [
	        [
	            'value' => "",
	            'text' => '-- ' . lang('with_selected') . ' --'
	        ],
	        [
	            'value' => "remove",
	            'text' => lang('delete'),
	            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-remove"'
	        ]
	    ],
	    'modal' => true
	]); ?>
	<?php endif; ?>
<?=form_close()?>
</div>
