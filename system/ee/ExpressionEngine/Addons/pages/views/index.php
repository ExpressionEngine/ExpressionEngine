<div class="panel">
	<?=form_open($base_url, 'class="tbl-ctrls"')?>
    <div class="panel-heading">
      <div class="form-btns form-btns-top">
        <div class="title-bar title-bar--large">
      		<h3 class="title-bar__title"><?=lang('all_pages')?></h3>
          <div class="title-bar__extra-tools">
						<a class="button button--primary js-dropdown-toggle has-sub" href=""><?=lang('create_new')?></a>
						<div class="dropdown">
							<div class="channels-pages-create">
								<?php
                                $menus = ee()->menu->generate_menu();
                                foreach ($menus['channels']['create'] as $channel_name => $link):
                                ?>
									<div class="search-channel" data-search="<?=strtolower($channel_name)?>"><a href="<?=$link?>" class="dropdown__link"><?=$channel_name?></a></div>
								<?php endforeach ?>
							</div>
						</div>
          </div>
        </div>
      </div>
    </div>

		<?=ee('CP/Alert')->get('pages-form')?>

		<?php $this->embed('ee:_shared/table', $table); ?>
		<?=$pagination?>
		<?php if (! empty($table['columns']) && ! empty($table['data'])): ?>
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
		<?php endif; ?>
	<?=form_close();?>
</div>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-remove',
    'form_url' => ee('CP/URL')->make('addons/settings/pages'),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
