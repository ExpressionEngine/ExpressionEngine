<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
<?=form_open($form_url)?>
       <h1>
			<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></br>
       </h1>

		<?=ee('CP/Alert')->get('shared-form')?>

       <?php if (isset($filters)) echo $filters; ?>

       <?php $this->embed('_shared/table', $table); ?>

       <?php if ( ! empty($pagination)) $this->embed('_shared/pagination', $pagination); ?>

       <?php if ( ! empty($table['data'])): ?>
       <fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
       		        <option value="">-- <?=lang('with_selected')?> --</option>
       		        <option value="opt_out"><?=lang('opt_out')?></option>
       		        <option value="opt_in"><?=lang('opt_in')?></option>
       		</select>
	   		<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
       </fieldset>
       <?php endif; ?>
<?=form_close()?>
</div>

<?php foreach($requests as $request): ?>
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
					if (strpos($contents, '<p>') !== 0)
					{
						$contents = '<p>' . $contents . '</p>';
					}
					echo $contents;
					?>
					<?=form_open($form_url, [], ['selection[]' => $request->getId()])?>
					<button class="btn action" name="bulk_action" value="opt_in"><?=lang('accept')?></button>
					<button class="btn draft" name="bulk_action" value="opt_out"><?=lang('decline')?></button>
					<?=form_close()?>
				</div>
			</div>
		</div>
	<?php ee('CP/Modal')->endModal(); ?>
<?php endforeach; ?>
