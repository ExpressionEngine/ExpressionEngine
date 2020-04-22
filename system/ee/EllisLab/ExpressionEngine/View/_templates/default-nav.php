<?php $this->extend('_templates/wrapper'); ?>

<?php if (isset($header)): ?>
<div class="main-nav">
	<div class="main-nav__wrap">
	<a class="main-nav__mobile-menu js-toggle-main-sidebar hidden">
		<svg xmlns="http://www.w3.org/2000/svg" width="18.585" height="13.939" viewBox="0 0 18.585 13.939"><g transform="translate(-210.99 -17.71)"><path d="M3,12.1H19.585" transform="translate(208.99 12.575)" fill="none" stroke-linecap="round" stroke-width="2"/><path d="M3,6H19.585" transform="translate(208.99 12.71)" fill="none" stroke-linecap="round" stroke-width="2"/><path d="M3,18H9.386" transform="translate(208.99 12.649)" fill="none" stroke-linecap="round" stroke-width="2"/></g></svg>
	</a>

	<div class="main-nav__title">
		<h1><?=$header['title']?></h1>

		<?php if (count($cp_breadcrumbs)): ?>
			<ul class="breadcrumb">
				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
					<li><a href="<?=$link?>"><?=$title?></a></li>
				<?php endforeach ?>
			</ul>
		<?php endif ?>
	</div>

	<div class="main-nav__toolbar">
		<a class="button button--secondary show-sidebar" href="#" title="Show sidebar">Show sidebar</a>
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
					<button type="button" class="button button--action js-dropdown-toggle has-sub" data-dropdown-pos="bottom-end"><?=$header['action_button']['text']?></button>
					<div class="dropdown">
						<?php if (count($header['action_button']['choices']) > 8): ?>
							<div class="dropdown__search">
								<div class="search-input">
									<input type="text" value="" class="search-input__input" data-fuzzy-filter="true" placeholder="<?=$header['action_button']['filter_placeholder']?>">
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
					<a class="button button--action" href="<?=$header['action_button']['href']?>"><?=$header['action_button']['text']?></a>
				<?php endif ?>
			<?php endif ?>
		<?php endif ?>

		<?php if (isset($header['action_buttons']) && count($header['action_buttons'])): ?>
				<?php foreach ($header['action_buttons'] as $button): ?>
						<?php if (isset($button['choices'])): ?>
							<button type="button" class="button button--action js-dropdown-toggle" data-dropdown-pos="bottom-end"><?=$button['text']?> <i class="fas fa-caret-down icon-right"></i></button>
							<div class="dropdown">
								<?php if (count($button['choices']) > 8): ?>
									<div class="dropdown__search">
										<div class="search-input">
											<input type="text" value="" class="search-input__input" data-fuzzy-filter="true" placeholder="<?=$button['filter_placeholder']?>">
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

	<div class="main-nav__account">
			<button type="button" data-dropdown-offset="21px, -46px" data-dropdown-pos="bottom-end" class="main-nav__account-icon js-dropdown-toggle">
				<img src="<?= $cp_avatar_path ?>" alt="<?=$cp_screen_name?>">
			</button>

			<div class="dropdown account-menu">
				<div class="account-menu__header">
					<div class="account-menu__header-title">
						<h2><?=$cp_screen_name?></h2>
						<span><?=$cp_member_primary_role_title?></span>
					</div>

					<img class="account-menu__icon js-hide-dropdowns" src="<?= $cp_avatar_path ?>" alt="">
				</div>

				<a class="dropdown__link" href="<?=ee('CP/URL')->make('members/profile', array('id' => ee()->session->userdata('member_id')))?>"><?=lang('my_profile')?></a>
				<a class="dropdown__link js-jump-menu-trigger" href=""><?= lang('go_to') ?></a>
				<a class="dropdown__link js-dark-theme-toggle" href=""><?= lang('dark_theme') ?></a>
				<a class="dropdown__link" href="<?=ee('CP/URL', 'login/logout')?>"><?=lang('log_out')?></a>

				<div class="dropdown__divider"></div>

				<h3 class="dropdown__header"><?=lang('quick_links')?></h3>
				<?php foreach($cp_quicklinks as $link): ?>
				<a class="dropdown__link" href="<?=$link['link']?>"><?=htmlentities($link['title'], ENT_QUOTES, 'UTF-8')?></a>
				<?php endforeach ?>
				<a class="dropdown__link" href="<?=ee('CP/URL')->make('members/profile/quicklinks/create', array('id' => ee()->session->userdata('member_id'), 'url' => ee('CP/URL')->getCurrentUrl()->encode(), 'name' => $cp_page_title))?>"><i class="fas fa-plus fa-sm"></i>  <?=lang('new_link')?></a>
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
