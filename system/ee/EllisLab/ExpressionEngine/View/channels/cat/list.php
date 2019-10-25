<?php $this->extend('_templates/default-nav'); ?>

	<?=form_open($base_url)?>

		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?=$cp_page_title?></h2>

			<div class="title-bar__extra-tools">
				<?php if ($can_create_categories):?>
					<a class="tn button button--small button--action" href="<?=ee('CP/URL')->make('categories/create/'.$cat_group->group_id)?>"><?=lang('new_category')?></a>
				<?php endif; ?>
			</div>
		</div>

		<div class="js-list-group-wrap">
			<?php if (count($categories->children()) != 0 && $can_delete_categories): ?>
				<div class="list-group-controls">
					<label class="ctrl-all"><span><?=lang('select_all')?></span> <input type="checkbox" class="checkbox--small"></label>
				</div>
			<?php endif ?>
			<div class="js-nestable-categories">
				<ul class="list-group list-group--nested">
					<?php foreach ($categories->children() as $category): ?>
						<?php $this->embed('channels/cat/_category', array('category' => $category)); ?>
					<?php endforeach ?>
					<?php if (count($categories->children()) == 0): ?>
						<li>
							<div class="tbl-row no-results">
								<div class="none">
									<p><?=lang('categories_not_found')?> <a href="<?=ee('CP/URL')->make('categories/create/'.$cat_group->group_id)?>"><?=lang('add_new')?></a></p>
								</div>
							</div>
						</li>
					<?php endif ?>
				</ul>
			</div>
		</div>
		<fieldset class="bulk-action-bar hidden">
			<select name="bulk_action">
				<option>-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<input class="button button--primary" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
		</fieldset>
	</form>


<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('categories/remove'),
	'hidden'	=> array(
		'bulk_action'	=> 'remove',
		'cat_group_id'	=> $cat_group->group_id
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
