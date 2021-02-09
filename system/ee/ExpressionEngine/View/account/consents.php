<?php $this->extend('_templates/default-nav'); ?>
<div class="panel">
<?=form_open($form_url)?>

    <div class="panel-heading">
      <?=ee('CP/Alert')->get('shared-form')?>
	   <div class="title-bar">
			<h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
			<?php if (isset($filters)) {
    echo $filters;
} ?>
		</div>
    </div>

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
		            'value' => "opt_out",
		            'text' => lang('opt_out')
		        ],
		        [
		            'value' => "opt_in",
		            'text' => lang('opt_in')
		        ]
		    ],
		    'modal' => true
		]); ?>
       <?php endif; ?>
<?=form_close()?>
</div>

<?php foreach ($requests as $request): ?>
	<?php ee('CP/Modal')->startModal('modal-consent-request-' . $request->getId()); ?>
		<div class="app-modal app-modal--center" rev="modal-consent-request-<?=$request->getId()?>">
			<div class="app-modal__content">
				<div class="app-modal__dismiss">
					<a class="js-modal-close" rel="modal-center" href="#"><?=lang('close_modal')?></a> <span class="txt-fade">[esc]</span>
				</div>
				<div class="md-wrap">
					<h1><?=$request->title?></h1>
					<p><?=ee()->localize->human_time($request->CurrentVersion->create_date->format('U'))?></p>
					<?php
                    $contents = $request->render();
                    if (strpos($contents, '<p>') !== 0) {
                        $contents = '<p>' . $contents . '</p>';
                    }
                    echo $contents;
                    ?>
					<?=form_open($form_url, [], ['selection[]' => $request->getId()])?>
					<button class="button button--primary" name="bulk_action" value="opt_in"><?=lang('accept')?></button>
					<button class="button button--secondary" name="bulk_action" value="opt_out"><?=lang('decline')?></button>
					<?=form_close()?>
				</div>
			</div>
		</div>
	<?php ee('CP/Modal')->endModal(); ?>
<?php endforeach; ?>
