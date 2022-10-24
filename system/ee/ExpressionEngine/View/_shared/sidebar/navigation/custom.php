					<!-- Custom Links -->
					<?php if ($custom && $custom->hasItems()) : ?>
					<div class="ee-sidebar__items-<?=$class_suffix?>">
                        <span class="ee-sidebar__section-label"><?=$header?></span>
						<nav class="nav-custom">
							<?php foreach ($custom->getItems() as $item) : ?>
							<?php if ($item->isSubmenu()) : ?>
								<a class="js-dropdown-toggle ee-sidebar__item js-dropdown-hover" data-dropdown-use-root="true" data-dropdown-pos="right-start" href="#" title="<?= lang($item->title) ?>"><span class="ee-sidebar__item-custom-icon"><?=substr(lang($item->title), 0, 1)?></span><span class="ee-sidebar__collapsed-hidden"><?= lang($item->title) ?></span></a>
								<div class="dropdown dropdown--accent" style="margin-left: -12px;">
									<?php if ($item->hasFilter()) : ?>
									<form class="dropdown__search">
										<div class="search-input">
											<input class="search-input__input input--small" type="text" value="" placeholder="<?= lang($item->placeholder) ?>">
										</div>
									</form>
										<?php if (count($item->getItems()) < 10 && !empty($item->view_all_link)) : ?>
										<a class="dropdown__link" href="<?= $item->view_all_link ?>"><b><?= lang('view_all') ?></b></a>
											<?php if (count($item->getItems()) != 0): ?>
											<div class="dropdown__divider"></div>
											<?php endif; ?>
										<?php endif; ?>
									<?php endif; ?>

									<div class="dropdown__scroll">
									<?php foreach ($item->getItems() as $sub) : ?>
									<a class="dropdown__link" href="<?= $sub->url ?>"><?= lang($sub->title) ?></a>
									<?php endforeach; ?>

									<?php if ($item->hasAddLink()) : ?>
									<a class="dropdown__link" class="nav-add" href="<?= $item->addlink->url ?>"><i class="fal fa-plus"></i><?= lang($item->addlink->title) ?></a>
									<?php endif; ?>
									</div>
								</div>
							<?php else : ?>
							<a class="ee-sidebar__item" href="<?= $item->url ?>" title="<?= lang($item->title) ?>"><span class="ee-sidebar__item-custom-icon"><?=substr(lang($item->title), 0, 1)?></span><span class="ee-sidebar__collapsed-hidden"><?= lang($item->title) ?></span></a>
							<?php endif; ?>
							<?php endforeach; ?>
						</nav>
					</div>
					<?php endif; ?>