<?php $this->extend('_templates/wrapper'); ?>

<?php if (isset($header)): ?>
<div class="main-nav<?php if ((!isset($ee_cp_viewmode) || empty($ee_cp_viewmode)) && (empty(ee()->uri->segment(2)) || ee()->uri->segment(2)=='homepage')) : ?> hidden<?php endif; ?>">
	<div class="main-nav__wrap">
	<a class="main-nav__mobile-menu js-toggle-main-sidebar hidden">
		<svg xmlns="http://www.w3.org/2000/svg" width="18.585" height="13.939" viewBox="0 0 18.585 13.939"><g transform="translate(-210.99 -17.71)"><path d="M3,12.1H19.585" transform="translate(208.99 12.575)" fill="none" stroke-linecap="round" stroke-width="2"/><path d="M3,6H19.585" transform="translate(208.99 12.71)" fill="none" stroke-linecap="round" stroke-width="2"/><path d="M3,18H9.386" transform="translate(208.99 12.649)" fill="none" stroke-linecap="round" stroke-width="2"/></g></svg>
	</a>

	<div class="main-nav__title">
		<h1></i> <?=$header['title']?></h1>
	</div>

	<div class="main-nav__toolbar">
		<?php if (isset($header['toolbar_items']) && $header['toolbar_items']): ?>
			<?php foreach ($header['toolbar_items'] as $name => $item): ?>
				<a class="button button--secondary icon--<?=$name?>" href="<?=$item['href']?>" title="<?=$item['title']?>"></a>
			<?php endforeach; ?>
		<?php endif ?>

		<?php if (isset($header['action_button']) || isset($header['search_form_url'])): ?>
			<?php if (isset($header['search_form_url'])): ?>
				<?=form_open($header['search_form_url'])?>
					<?php if (isset($header['search_button_value'])): ?>
					<input class="main-nav__toolbar-input" placeholder="<?=$header['search_button_value']?>" type="text" name="search" value="<?=form_prep(ee()->input->get_post('search'))?>">
					<?php else: ?>
					<input class="main-nav__toolbar-input" placeholder="<?=lang('search')?>" type="text" name="search" value="<?=form_prep(ee()->input->get_post('search'))?>">
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
						<?php foreach ($header['action_button']['choices'] as $link => $text): ?>
							<a href="<?=$link?>" class="dropdown__link"><?=$text?></a>
						<?php endforeach ?>
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
							<button type="button" class="button button--primary js-dropdown-toggle" data-dropdown-pos="bottom-end"><?=$button['text']?> <i class="fas fa-caret-down icon-right"></i></button>
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
							<a class="button button--<?=isset($button['type']) ? $button['type'] : 'action'?>" href="<?=$button['href']?>" rel="<?=isset($button['rel']) ? $button['rel'] : ''?>"><?=$button['text']?></a>
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
		<div class="secondary-sidebar">
			<?=$left_nav?>
		</div>
	<?php endif; ?>

	<div class="container">
		<?=$child_view?>
	</div>

	<?php if (isset($left_nav)): ?>
	</div>
	<?php endif; ?>
</div>
