<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>
<div class="panel">
  <div class="panel-body">

<div class="tab-wrap">
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

<div class="tab-bar">
	<div class="tab-bar__tabs">
		<button type="button" class="tab-bar__tab active js-tab-button" rel="t-0"><?=lang('installed')?> <span class="tab-bar__tab-notification tab-notification-generic"><?=count($installed)?></span></button>
		<button type="button" class="tab-bar__tab js-tab-button" rel="t-2">
			<?=lang('updates')?>
			<span class="tab-bar__tab-notification<?php if (empty($updates)) : ?> tab-notification-generic<?php endif; ?>"><?=count($updates)?></span>
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

</div>
</div>

<?php

$modal_vars = array(
    'name' => 'modal-confirm-remove',
    'form_url' => $form_url,
    'title' => lang('confirm_uninstall'),
    'alert' => lang('confirm_uninstall_desc'),
    'button' => [
        'text' => lang('btn_confirm_and_uninstall'),
        'working' => lang('btn_confirm_and_uninstall_working')
    ],
    'hidden' => array(

    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
