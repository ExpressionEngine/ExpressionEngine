<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
<?=form_open($form_url)?>
       <h1>
			<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></br>
       </h1>

       <?php $this->embed('_shared/table', $table); ?>

       <?php if ( ! empty($pagination)) $this->embed('_shared/pagination', $pagination); ?>

<?=form_close()?>
</div>

<?php foreach($versions as $version): ?>
	<?php ee('CP/Modal')->startModal('modal-consent-request-' . $version->getId()); ?>
		<div class="app-modal app-modal--center" rev="modal-consent-request-<?=$version->getId()?>">
			<div class="app-modal__content">
				<div class="app-modal__dismiss">
					<a class="js-modal-close" rel="modal-center" href="#"><?=lang('close_modal')?></a> <span class="txt-fade">[esc]</span>
				</div>
				<div class="md-wrap">
					<h1><?=$version->ConsentRequest->title?> (#<?=$version->getId()?>)</h1>
					<p><?=ee()->localize->human_time($version->create_date->format('U'))?></p>
					<textarea readonly="readonly"><?=ee('Format')->make('Text', $version->request)->convertToEntities()?></textarea>
				</div>
			</div>
		</div>
	<?php ee('CP/Modal')->endModal(); ?>
<?php endforeach; ?>
