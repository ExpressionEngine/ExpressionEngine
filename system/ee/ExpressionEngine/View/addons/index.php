<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>
<div class="panel">
  <div class="panel-body">
<?=form_open($form_url)?>
<div class="tab-wrap">
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

<div class="tab-bar">
	<div class="tab-bar__tabs">
		<button type="button" class="tab-bar__tab active js-tab-button" rel="t-0"><?=lang('installed')?></button>
		<button type="button" class="tab-bar__tab js-tab-button" rel="t-2">
			<?=lang('updates')?>

			<?php if (! empty($updates)) : ?>
			<span class="tab-bar__tab-notification"><?=count($updates)?></span>
			<?php endif; ?>
		</button>
	</div>
</div>

<div class="tab t-0 tab-open">

	<div class="add-on-card-list">
		<?php $addons = $installed; foreach ($addons as $addon): ?>
			<?php $this->embed('_shared/add-on-card', ['addon' => $addon, 'show_updates' => false]); ?>
		<?php endforeach; ?>
	</div>

	<?php if (count($uninstalled)): ?>
		<h4 class="line-heading"><?=lang('uninstalled')?></h4>
		<hr>

		<div class="add-on-card-list">
			<?php foreach ($uninstalled as $addon): ?>
				<?php $this->embed('_shared/add-on-card', ['addon' => $addon, 'show_updates' => false]); ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<div class="tab t-2">
	<div class="add-on-card-list">
		<?php foreach ($updates as $addon): ?>
			<?php $this->embed('_shared/add-on-card', ['addon' => $addon, 'show_updates' => true]); ?>
		<?php endforeach; ?>
	</div>
</div>

</div>
<?=form_close()?>
</div>
</div>

<?php ee('CP/Modal')->startModal('modal-confirm-remove'); ?>
<div class="modal-wrap modal-confirm-remove hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="form-standard">
					<?=form_open($form_url, '', array('bulk_action' => 'remove'))?>
						<div class="form-btns form-btns-top">
							<h1><?=lang('confirm_uninstall')?></h1>
						</div>
						<?=ee('CP/Alert')
							->makeInline()
							->asIssue()
							->addToBody(lang('confirm_uninstall_desc'))
							->render()?>
						<div class="txt-wrap">
							<ul class="checklist">
								<?php if (isset($checklist)):
									$end = end($checklist); ?>
									<?php foreach ($checklist as $item): ?>
									<li<?php if ($item == $end) echo ' class="last"'; ?>><?=$item['kind']?>: <b><?=$item['desc']?></b></li>
									<?php endforeach;
								endif ?>
							</ul>
							<div class="ajax"></div>
						</div>
						<div class="form-btns">
							<?=cp_form_submit('btn_confirm_and_uninstall', 'btn_confirm_and_uninstall_working')?>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php ee('CP/Modal')->endModal(); ?>
