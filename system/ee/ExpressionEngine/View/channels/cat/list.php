<?php $this->extend('_templates/default-nav'); ?>
<div class="panel">
	<div class="tbl-ctrls">
	<?=form_open($base_url)?>
  <div class="panel-heading">
    <div class="form-btns form-btns-top">
      <div class="title-bar title-bar--large">
        <h3 class="title-bar__title">
          <?=$cp_page_title?>
          <br>
          <i>#<?=$cat_group->group_id?></i>
        </h3>
        <div class="title-bar__extra-tools">
  				<?php if ($can_create_categories):?>
  					<a class="tn button button--primary" href="<?=ee('CP/URL')->make('categories/create/' . $cat_group->group_id)?>"><?=lang('new_category')?></a>
  				<?php endif; ?>
  			</div>
      </div>
    </div>
  </div>

  <div class="panel-body">
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="js-list-group-wrap">
			<?php if (count($categories->children()) != 0 && $can_delete_categories): ?>
				<div class="list-group-controls">
					<label class="ctrl-all"><span><?=lang('select_all')?></span> <input type="checkbox"></label>
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
									<p><?=lang('categories_not_found')?> <a href="<?=ee('CP/URL')->make('categories/create/' . $cat_group->group_id)?>"><?=lang('add_new')?></a></p>
								</div>
							</div>
						</li>
					<?php endif ?>
				</ul>
			</div>
		</div>
		<?php $this->embed('ee:_shared/form/bulk-action-bar', [
		    'options' => [
		        [
		            'value' => "",
		            'text' => '-- ' . lang('with_selected') . ' --'
		        ],
		        [
		            'value' => "remove",
		            'text' => lang('delete'),
		            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete"'
		        ]
		    ],
		    'modal' => true
		]); ?>
  </div>
	</form>
</div>
</div>


<?php

$modal_vars = array(
    'name' => 'modal-confirm-delete',
    'form_url' => ee('CP/URL')->make('categories/remove'),
    'hidden' => array(
        'bulk_action' => 'remove',
        'cat_group_id' => $cat_group->group_id
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
