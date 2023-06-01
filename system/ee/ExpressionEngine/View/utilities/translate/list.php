<?php $this->extend('_templates/default-nav'); ?>
<div class="panel">
<?=form_open($table['base_url'])?>


<div class="panel-heading">
	<div class="title-bar">
		<h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>

		<div class="title-bar__extra-tools">
			<div class="search-input">
				<input class="search-input__input input--small" placeholder="<?=lang('search')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>" aria-label="<?=lang('search_input')?>">
			</div>
		</div>
	</div>
</div>

<div class="panel-body">
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>


	<?php $this->embed('_shared/table', $table); ?>

	<?=$pagination?>

  <?php if (! empty($table['data'])): ?>

	<?php $this->embed('ee:_shared/form/bulk-action-bar', [
	    'options' => [
	        [
	            'value' => "",
	            'text' => '-- ' . lang('with_selected') . ' --'
	        ],
	        [
	            'value' => "export",
	            'text' => lang('export_download')
	        ]
	    ]
	]); ?>

	<?php endif; ?>
</div>
<?=form_close()?>
</div>
