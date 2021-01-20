<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box snap mb panel">
	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
    <div class="panel-heading">
  		<div class="app-notice-wrap">
  			<?=ee('CP/Alert')->get('view-members')?>
  		</div>
      <div class="form-btns form-btns-top">
        <div class="title-bar title-bar--large">
    			<h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
    			<?php if (isset($filters)) {
    echo $filters;
} ?>
        </div>
      </div>
		</div>


		<?php $this->embed('_shared/table', $table); ?>

		<?php if (! empty($pagination)) {
    echo $pagination;
} ?>

		<?php if (! empty($table['data']) && ($can_edit || $can_delete)): ?>
		<?php
            $options = [
                [
                    'value' => "",
                    'text' => '-- ' . lang('with_selected') . ' --'
                ]
            ];
            if ($can_edit) {
                $options[] = [
                    'value' => "approve",
                    'text' => lang('approve')
                ];
                if ($resend_available) {
                    $options[] = [
                        'value' => "resend",
                        'text' => lang('resend')
                    ];
                }
            }
            if ($can_delete) {
                $options[] = [
                    'value' => "decline",
                    'text' => lang('decline'),
                    'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-remove"'
                ];
            }
            $this->embed('ee:_shared/form/bulk-action-bar', [
                'options' => $options,
                'modal' => true
            ]);
        ?>
		<?php endif; ?>
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
					<?=form_open($form_url, '', array('bulk_action' => 'decline'))?>
						<div class="form-btns form-btns-top">
							<h2><?=lang('confirm_decline')?></h2>
						</div>
						<?=ee('CP/Alert')
						    ->makeInline()
						    ->asIssue()
						    ->addToBody(lang('confirm_decline_desc'))
						    ->render()?>
						<div class="txt-wrap">
							<ul class="checklist">
								<?php if (isset($checklist)):
                                    $end = end($checklist); ?>
									<?php foreach ($checklist as $item): ?>
									<li<?php if ($item == $end) {
                                        echo ' class="last"';
                                    } ?>><?=$item['kind']?>: <b><?=$item['desc']?></b></li>
									<?php endforeach;
                                endif ?>
							</ul>
							<div class="ajax"></div>
						</div>
						<div class="form-btns">
							<?=cp_form_submit('btn_confirm_and_decline', 'btn_confirm_and_decline_working')?>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php ee('CP/Modal')->endModal(); ?>
