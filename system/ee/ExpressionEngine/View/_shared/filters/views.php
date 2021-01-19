<button type="button" class="filter-bar__button has-sub js-dropdown-toggle button button--default button--small" data-filter-label="<?=strtolower(lang($label))?>">
	<?=lang($label)?>
	<?php if ($value): ?>
	<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
	<?php endif; ?>
</button>


<div class="dropdown dropdown--open">
	<div class="dropdown__item">
        <a href="#">Default View <span class="float-right" style="color: var(--ee-text-secondary) !important">Default</span></a>
	</div>
	<?php if (!empty($available_views)) : ?>
	<div class="dropdown__header">Saved Views</div>
	<?php endif; ?>
	<?php foreach ($available_views as $available_view): ?>
	<div class="dropdown__item">
		<a href="<?=$available_view['url']?>" data-id="<?=$available_view['view_id']?>"><?=$available_view['name']?></a>
	</div>
	<?php endforeach; ?>
    <div class="dropdown__divider"></div>
	<?php if (!empty($selected_view)) : ?>
	<a class="dropdown__link filter-item__link--save" href="<?=$edit_url?>" style="color: var(--ee-link) !important">Edit View</a>
	<?php endif; ?>
    <a class="dropdown__link filter-item__link--save" href="<?=$create_url?>" style="color: var(--ee-link) !important">New View</a>
</div>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-delete-view',
    'form_url' => '',//$form_url,
    'hidden' => array(
        'bulk_action' => 'remove'
    ),
    'secure_form_ctrls' => isset($confirm_remove_secure_form_ctrls) ? $confirm_remove_secure_form_ctrls : null
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
