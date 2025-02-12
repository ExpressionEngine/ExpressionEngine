<div class="panel">
	<?=form_open($base_url, 'class="tbl-ctrls"')?>
    <div class="panel-heading">
      <div class="form-btns form-btns-top">
        <div class="title-bar title-bar--large">
      		<h3 class="title-bar__title"><?=lang('mailinglists')?></h3>
          <div class="title-bar__extra-tools">
            <a class="button button--primary" href="<?=ee('CP/URL')->make('addons/settings/mailinglist/edit_mailing_list')?>"><?=lang('create_new')?></a>
          </div>
        </div>
      </div>
    </div>

		<?=ee('CP/Alert')->get('mailinglist-form')?>

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


<?php $this->embed('mailinglist:subscribe'); ?>









