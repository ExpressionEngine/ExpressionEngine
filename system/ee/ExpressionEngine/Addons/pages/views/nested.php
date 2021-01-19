	<div class="tbl-ctrls">
		<?=form_open($base_url)?>
			<fieldset class="tbl-search right">
				<div class="filters">
					<ul>
						<li>
							<a class="has-sub" href=""><?=lang('create_new')?></a>
							<div class="sub-menu">
								<fieldset class="filter-search">
									<input type="text" value="" placeholder="<?=lang('filter_channels')?>">
								</fieldset>
								<ul class="channels-pages-create">
									<?php
                                    $menus = ee()->menu->generate_menu();
                                    foreach ($menus['channels']['create'] as $channel_name => $link):
                                    ?>
										<li class="search-channel" data-search="<?=strtolower($channel_name)?>"><a href="<?=$link?>"><?=$channel_name?></a></li>
									<?php endforeach ?>
								</ul>
							</div>
						</li>
					</ul>
				</div>
			</fieldset>
			<h1><?=lang('all_pages')?></h1>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
			<div class="js-list-group-wrap">
				<?php if (count($pages->children()) != 0): ?>
					<div class="list-group-controls">
						<label class="ctrl-all"><span><?=lang('select_all')?></span> <input type="checkbox" class="checkbox--small"></label>
					</div>
				<?php endif ?>
				<div>
					<ul class="list-group list-group--nested">
						<?php foreach ($pages->children() as $page): ?>
							<?php $this->embed('pages:_page', array('page' => $page)); ?>
						<?php endforeach ?>
						<?php if (count($pages->children()) == 0): ?>
							<li>
								<div class="tbl-row no-results">
									<div class="none">
										<p><?=sprintf(lang('no_found'), lang('pages_module_name'))?></p>
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
			            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-remove"'
			        ]
			    ],
			    'modal' => true
			]); ?>
		</form>
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
