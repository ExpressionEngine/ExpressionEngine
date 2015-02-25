<?php extend_template('default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open($base_url)?>
		<fieldset class="tbl-search right">
			<a class="btn tn action" href="<?=cp_url('channel/cat/cat-create/'.$cat_group->group_id)?>"><?=lang('create_new')?></a>
		</fieldset>
		<h1><?=$cp_page_title?></h1>
		<?=ee('Alert')->getAllInlines()?>
		<div class="tbl-list-wrap">
			<div class="tbl-list-ctrl">
				<label><span>select all</span> <input type="checkbox"></label>
			</div>
			<style>
				.nestable, .tbl-list, .tbl-list-item, .drag-placeholder { display: block; position: relative; }
				.tbl-list-dragging { display:block; position: absolute; pointer-events: none; z-index: 9999; }
			</style>
			<div class="nestable">
				<ul class="tbl-list">
					<?php foreach ($categories as $category): ?>
						<?php $this->view('channel/cat/_category', array('category' => $category)); ?>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
		<fieldset class="tbl-bulk-act">
			<select name="bulk_action">
				<option>-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
		</fieldset>
	</form>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> cp_url('channel/cat/remove-cat'),
	'hidden'	=> array(
		'bulk_action'	=> 'remove',
		'cat_group_id'	=> $cat_group->group_id
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>