<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($base_url)?>
		<?php if ($can_create_categories):?>
		<fieldset class="tbl-search right">
			<a class="btn tn action" href="<?=ee('CP/URL')->make('channels/cat/create-cat/'.$cat_group->group_id)?>"><?=lang('create_new')?></a>
		</fieldset>
		<?php endif; ?>
		<h1><?=$cp_page_title?></h1>
		<?=ee('CP/Alert')->getAllInlines()?>
		<div class="tbl-list-wrap">
			<?php if (count($categories->children()) != 0 && $can_delete_categories): ?>
				<div class="tbl-list-ctrl">
					<label class="ctrl-all"><span>select all</span> <input type="checkbox"></label>
				</div>
			<?php endif ?>
			<div class="nestable">
				<ul class="tbl-list">
					<?php foreach ($categories->children() as $category): ?>
						<?php $this->embed('channels/cat/_category', array('category' => $category)); ?>
					<?php endforeach ?>
					<?php if (count($categories->children()) == 0): ?>
						<li>
							<div class="tbl-row no-results">
								<div class="none">
									<p><?=lang('categories_not_found')?> <a class="btn action" href="<?=ee('CP/URL')->make('channels/cat/create-cat/'.$cat_group->group_id)?>"><?=lang('create_category_btn')?></a></p>
								</div>
							</div>
						</li>
					<?php endif ?>
				</ul>
			</div>
		</div>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option>-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
		</fieldset>
	</form>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('channels/cat/remove-cat'),
	'hidden'	=> array(
		'bulk_action'	=> 'remove',
		'cat_group_id'	=> $cat_group->group_id
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
