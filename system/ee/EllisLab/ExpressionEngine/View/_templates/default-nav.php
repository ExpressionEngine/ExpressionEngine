<?php $this->extend('_templates/wrapper'); ?>

<?php if (isset($header)): ?>
<div class="main-nav">
	<a class="main-nav__mobile-menu"><i class="fas fa-bars"></i></a>

	<div class="main-nav__title">
		<h1><?=$header['title']?></h1>

		<!-- <ul class="breadcrumb">
			<li><a href=""></a></li>
		</ul> -->
	</div>

	<div class="main-nav__toolbar">
		<?php if (isset($header['toolbar_items']) && $header['toolbar_items']): ?>
			<div class="section-header__options">
				<?php foreach ($header['toolbar_items'] as $name => $item): ?>
					<a class="icon--<?=$name?>" href="<?=$item['href']?>" title="<?=$item['title']?>"></a>
				<?php endforeach; ?>
			</div>
		<?php endif ?>

		<?php if (isset($header['action_button']) || isset($header['search_form_url'])): ?>
			<?php if (isset($header['search_form_url'])): ?>
				<?=form_open($header['search_form_url'])?>
					<fieldset class="tbl-search right">
						<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=form_prep(ee()->input->get_post('search'))?>">
						<?php if (isset($header['search_button_value'])): ?>
						<input class="btn submit" type="submit" value="<?=$header['search_button_value']?>">
						<?php else: ?>
						<input class="btn submit" type="submit" value="<?=lang('search')?>">
						<?php endif; ?>
					</fieldset>
				</form>
			<?php endif ?>
			<?php if (isset($header['action_button'])): ?>
				<?php if (isset($header['action_button']['choices'])): ?>
					<div class="filter-item filter-item--right">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu filter-item__link--action"><?=$header['action_button']['text']?></a>
						<div class="filter-submenu">
							<?php if (count($header['action_button']['choices']) > 8): ?>
								<div class="filter-submenu__search">
									<input type="text" value="" data-fuzzy-filter="true" placeholder="<?=$header['action_button']['filter_placeholder']?>">
								</div>
							<?php endif ?>
							<div class="filter-submenu__scroll">
								<?php foreach ($header['action_button']['choices'] as $link => $text): ?>
									<a href="<?=$link?>" class="filter-submenu__link"><?=$text?></a>
								<?php endforeach ?>
							</div>
						</div>
					</div>
				<?php else: ?>
					<a class="button button--action" href="<?=$header['action_button']['href']?>"><?=$header['action_button']['text']?></a>
				<?php endif ?>
			<?php endif ?>
		<?php endif ?>


		<a class="main-nav__account" href="">
			<!-- <div class="nav-global-user">
					<div class="nav-user">
						<a class="nav-has-sub" href=""><i class="icon-user"></i><span class="nav-txt-collapse"><?=$cp_screen_name?></span></a>
						<ul class="nav-sub-menu">
							<li><a href="<?=ee('CP/URL')->make('members/profile', array('id' => ee()->session->userdata('member_id')))?>"><?=lang('my_profile')?></a></li>
							<?php foreach($cp_quicklinks as $link): ?>
							<li><a href="<?=$link['link']?>"><?=htmlentities($link['title'], ENT_QUOTES, 'UTF-8')?></a></li>
							<?php endforeach ?>
							<li><a class="nav-add" href="<?=ee('CP/URL')->make('members/profile/quicklinks/create', array('id' => ee()->session->userdata('member_id'), 'url' => ee('CP/URL')->getCurrentUrl()->encode(), 'name' => $cp_page_title))?>"><i class="icon-add"></i><?=lang('new_link')?></a></li>
						</ul>
					</div>
					<a class="nav-logout" href="<?=ee('CP/URL', 'login/logout')?>"><i class="icon-logout"></i><span class="nav-txt-collapse"><?=lang('log_out')?></span></a>
				</div> -->
			<!-- <a href="<?=ee('CP/URL')->make('members/profile', array('id' => ee()->session->userdata('member_id')))?>"><?=lang('my_profile')?></a> -->
			<img src="../build/images/profile-icon.png" alt="">
			<!-- <span class="main-nav__account-name">Jordan Ellis</span> -->
		</a>
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


<!-- if ($this->enabled('outer_box'))

<?php if (count($cp_breadcrumbs)): ?>
			<ul class="breadcrumb">
				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
					<li><a href="<?=$link?>"><?=$title?></a></li>
				<?php endforeach ?>
				<li class="last"><?=ee('Format')->make('Text', isset($breadcrumb_title) ? $breadcrumb_title : $cp_page_title)->attributeSafe()->compile()?></li>
			</ul>
		<?php endif ?>

 -->
