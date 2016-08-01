<div class="nestable">
	<ul class="nested-list managed">
		<?php $this->embed('settings/menu-manager/item'); ?>
		<?php if (count($options) == 0): ?>
		<li>
			<div class="tbl-row no-results">
				<div class="none">
					<p><?=lang('no_menu_items')?></p>
				</div>
			</div>
		</li>
		<?php endif; ?>
	</ul>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-menu-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('settings/menu-manager/remove-item', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove',
		'item_id' => ''
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);


$modal_vars = array('name' => 'modal-menu-edit', 'contents' => '');
$modal = $this->make('ee:_shared/modal-form')->render($modal_vars);
ee('CP/Modal')->addModal('menu-edit', $modal);

?>
