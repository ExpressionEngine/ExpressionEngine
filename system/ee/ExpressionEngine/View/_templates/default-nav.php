<?php $this->extend('_templates/wrapper'); ?>

<?php $this->embed('_shared/upgrade_success_banner'); ?>

<?php if (isset($header)): ?>
<div class="main-nav<?php if ((!isset($ee_cp_viewmode) || empty($ee_cp_viewmode)) && (empty(ee()->uri->segment(2)) || ee()->uri->segment(2) == 'homepage')) : ?> hidden<?php endif; ?>">
    <div class="main-nav__wrap">

    <div class="main-nav__title">
        <h1><?=$header['title']?></h1>
    </div>

    <div class="main-nav__toolbar">
        <?php if (isset($header['toolbar_items']) && $header['toolbar_items']): ?>
            <?php foreach ($header['toolbar_items'] as $name => $item): ?>
                <a class="button button--secondary icon--<?=$name?>" href="<?=$item['href']?>" title="<?=$item['title']?> <?=lang('button')?>"><span class="hidden"><?=$item['title']?></span></a>
            <?php endforeach; ?>
        <?php endif ?>

        <?php if (isset($header['action_button']) || isset($header['search_form_url'])): ?>
            <?php if (isset($header['search_form_url'])): ?>
                <?=form_open($header['search_form_url'])?>
                    <?php if (isset($header['search_button_value'])): ?>
            <div class="field-control with-icon-start">
              <i class="fal fa-search icon-start"></i>
              <input class="main-nav__toolbar-input" placeholder="<?=$header['search_button_value']?>" type="text" name="search" value="<?=form_prep(ee()->input->get_post('search'))?>" aria-label="<?=$header['search_button_value']?>">
            </div>
                    <?php else: ?>
          <div class="field-control with-icon-start">
            <i class="fal fa-search icon-start"></i>
            <input class="main-nav__toolbar-input" placeholder="<?=lang('search')?>" type="text" name="search" value="<?=form_prep(ee()->input->get_post('search'))?>" aria-label="<?=lang('search')?>">
          </div>
                    <?php endif; ?>
                </form>
            <?php endif ?>
            <?php if (isset($header['action_button'])): ?>
                <?php if (isset($header['action_button']['choices'])): ?>
                    <button type="button" class="button button--primary js-dropdown-toggle has-sub" data-dropdown-pos="bottom-end"><?=$header['action_button']['text']?></button>
                    <div class="dropdown">
                        <?php if (count($header['action_button']['choices']) > 8): ?>
                            <div class="dropdown__search">
                                <div class="search-input">
                                    <input type="text" value="" class="search-input__input input--small" data-fuzzy-filter="true" placeholder="<?=$header['action_button']['filter_placeholder']?>">
                                </div>
                            </div>
                        <?php endif ?>

                        <div class="dropdown__scroll">
                            <ul>
                            <?php foreach ($header['action_button']['choices'] as $key => $data): ?>
                                <li>
                                <?php if (! is_array($data)) : ?>
                                    <a href="<?=$key?>" class="dropdown__link"><?=$data?></a>
                                <?php elseif (isset($data['upload_location_id'])): ?>
                                    <a href="#" data-upload_location_id="<?=$data['upload_location_id']?>" data-directory_id="0" class="dropdown__link">
                                        <?=$data['label']?>
                                    </a>
                                    <?php
                                        if (! empty($data['children'])) {
                                            echo '<ul>';
                                            $this->embed('files/subfolder-dropdown', ['data' => $data['children']]);
                                            echo '</ul>';
                                        }
                                    ?>
                                    <?php endif; ?>
                                    </li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <a class="button button--primary" href="<?=$header['action_button']['href']?>"><?=$header['action_button']['text']?></a>
                <?php endif ?>
            <?php endif ?>
        <?php endif ?>

		<?php if (isset($header['action_buttons']) && count($header['action_buttons'])): ?>
				<?php foreach ($header['action_buttons'] as $button): ?>
						<?php if (isset($button['choices'])): ?>
							<button type="button" class="button button--primary js-dropdown-toggle" data-dropdown-pos="bottom-end"><?=$button['text']?> <i class="fal fa-caret-down icon-right"></i></button>
							<div class="dropdown">
								<?php if (count($button['choices']) > 8): ?>
									<div class="dropdown__search">
										<div class="search-input">
											<input type="text" value="" class="search-input__input input--small" data-fuzzy-filter="true" placeholder="<?=$button['filter_placeholder']?>">
										</div>
									</div>
								<?php endif ?>
								<div class="dropdown__scroll">
								<?php foreach ($button['choices'] as $link => $text): ?>
									<a href="<?=$link?>" class="dropdown__link"><?=$text?></a>
								<?php endforeach ?>
								</div>
							</div>
						<?php else: ?>
							<a class="button button--<?=isset($button['type']) ? $button['type'] : 'primary'?>" href="<?=$button['href']?>" rel="<?=isset($button['rel']) ? $button['rel'] : ''?>"><?=$button['text']?></a>
						<?php endif ?>
				<?php endforeach ?>
			<?php endif ?>
	</div>
	</div>


</div>
<?php endif ?>


<div class="ee-main__content">

    <?php if (isset($left_nav)): ?>
        <div class="secondary-sidebar-container">
            <?=$left_nav?>

            <div class="container" style="position: relative;">
                <?=$child_view?>
            </div>
        </div>
    <?php else : ?>
        <div class="container" style="position: relative;">
            <?=$child_view?>
        </div>
    <?php endif; ?>
</div>
