<div class="mb">
	<?php $this->embed('ee:_shared/form')?>
</div>
<br />
<div class="snap table-list-wrap">
	<div class="tbl-ctrls">
		<?=form_open(ee('CP/URL')->make('addons/settings/rte/update_toolsets'))?>
			<fieldset class="tbl-search right">
				<a class="btn tn action" href="<?=ee('CP/URL')->make('addons/settings/rte/edit_toolset')?>"><?=lang('create_new')?></a>
			</fieldset>
			<h1><?=lang('available_tool_sets')?></h1>

			<?php $this->embed('ee:_shared/table', $table); ?>

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
		<?=form_close();?>
	</div>
</div>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-remove',
    'form_url' => ee('CP/URL')->make('addons/settings/rte/delete_toolset'),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
