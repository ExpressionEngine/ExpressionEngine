<div class="mb">
	<?php $this->embed('ee:_shared/form')?>
</div>
<div class="panel">
<div class="snap table-list-wrap">
	<div class="tbl-ctrls">
		<?=form_open(ee('CP/URL')->make('addons/settings/rte/update_toolsets'))?>
    <div class="panel-heading">
      <div class="form-btns form-btns-top">
        <div class="title-bar title-bar--large">
          <h3 class="title-bar__title"><?=lang('available_tool_sets')?></h3>
          <div class="title-bar__extra-tools">
    				<a class="button button--primary tn action" href="<?=ee('CP/URL')->make('addons/settings/rte/new_toolset')?>"><?=lang('create_new')?></a>
    			</div>
        </div>
      </div>

    </div>

			<?=ee('CP/Alert')->get('toolsets-form')?>

			<?php $this->embed('ee:_shared/table', $table); ?>
			<?=$pagination?>
			<?php $this->embed('ee:_shared/form/bulk-action-bar', [
			'options' => [
				[
					'value' => "",
					'text' => '-- ' . lang('with_selected') . ' --'
				],
				[
					'value' => "enable",
					'text' => lang('enable')
				],
				[
					'value' => "disable",
					'text' => lang('disable')
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
</div>

<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('addons/settings/rte/update_toolsets'),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
