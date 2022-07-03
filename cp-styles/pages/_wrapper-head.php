<?php

include(dirname(__FILE__) . '/_header.php');

?>

<div class="theme-switch-circle"></div>

<!-- <div class="jump-menu-container">
    <div class="jump-menu">
        <div class="jump-menu__input">
            <input type="text" placeholder="Go To..">
        </div>

    </div>
</div>

<div class="modal-overlay"></div> -->


<div class="ee-wrapper-overflow">
<div class="ee-wrapper">
    <div class="ee-sidebar">
		<div class="ee-sidebar__title"><span class="ee-sidebar__title-name"><i class="fal fa-desktop fa-fw"></i> Balk's Hardware</span> <!-- <span class="ee-sidebar__title-down-arrow"><i class="fal fa-angle-down"></i></span> --></div>
        <div class="ee-sidebar__items">
            <div class="ee-sidebar__items-top">
				<a href="./homepage.php"  class="ee-sidebar__item <?=($current_page == 'homepage' ? 'active' : '')?>"><i class="fal fa-tachometer-alt"></i> Dashboard</a>
				<a href="./entries.php" class="ee-sidebar__item <?=($current_page == 'entries' ? 'active' : '')?>"><i class="fal fa-newspaper"></i> Entries</a>
				<a href="./entry.php" class="ee-sidebar__item <?=(($current_page == 'entry') ? 'active' : '')?>"><i class="fal fa-newspaper"></i> Single Entry</a>
				<a href="./files.php"  class="ee-sidebar__item <?=($current_page == 'files' ? 'active' : '')?>"><i class="fal fa-folder"></i> Files</a>
				<a href="./members.php"  class="ee-sidebar__item <?=($current_page == 'members' ? 'active' : '')?>"><i class="fal fa-users"></i> Members</a>
				<!-- <a href="./categories.php"  class="ee-sidebar__item <?=($current_page == 'categories' ? 'active' : '')?>"><i class="fal fa-tags"></i> Categories</a> -->
				<a href="./add-ons.php"  class="ee-sidebar__item <?=($current_page == 'add-ons' ? 'active' : '')?>"><i class="fal fa-bolt"></i> Add-Ons</a>
			</div>

			<div class="ee-sidebar__items-custom">
				<a href="./foundation.php"  class="ee-sidebar__item <?=($current_page == 'foundation' ? 'active' : '')?>"><i class="fal fa-tint"></i> Colors</a>
				<a href="./typography.php"  class="ee-sidebar__item <?=($current_page == 'typography' ? 'active' : '')?>"><i class="fal fa-heading"></i> Typography</a>
				<a href="./components.php"  class="ee-sidebar__item <?=($current_page == 'components' ? 'active' : '')?>"><i class="fal fa-cubes"></i> Components</a>
				<a href="./fields.php"  class="ee-sidebar__item <?=($current_page == 'fields' ? 'active' : '')?>"><i class="fal fa-pen-field"></i> Fields</a>
				<a href="./support.php"  class="ee-sidebar__item <?=($current_page == 'support' ? 'active' : '')?>"><i class="fal fa-life-ring"></i> Support</a>
			</div>

			<div class="ee-sidebar__items-bottom">
				<a href="" class="ee-sidebar__item"><i class="fal fa-database"></i> Developer</a>
				<a href="./settings.php" class="ee-sidebar__item"><i class="fal fa-sliders-h"></i> Settings</a>
				<!-- <a href="./settings.php" class="ee-sidebar__item ee-sidebar__whats-new"><i class="fal fa-gift"></i> What's New?</a> -->
				<a href="" class="ee-sidebar__item ee-sidebar__version">ExpressionEngine <span>6.0.0</span></a>
				<!-- <a href="" class="ee-sidebar__whats-new"><i class="fal fa-gift"></i></a> -->
			</div>

		</div>

	</div>
	<div class="ee-main">

    <div class="ee-main-header">
      <a href="" class="sidebar-toggle" title="Collapse Sidebar"><i class="fal fa-angle-left"></i></a>

      <div class="breadcrumb-wrapper">
        <ul class="breadcrumb">
            <li><a href=""><i class="fal fa-home"></i></a></li>
            <li><a href="">Breadcrumb</a></li>
            <li><a href="">Breadcrumb</a></li>
            <li><span>Breadcrumb</span></li>
        </ul>
      </div>

      <div class="field-control field-control_input--jump with-icon-start with-input-shortcut">
        <i class="fal fa-bullseye fa-fw icon-start"></i>
        <input type="text" class="input--jump input--rounded" placeholder="Jump to...">
        <span class="input-shortcut">⌘J</span>
      </div>

      <div class="main-header__account">
				<button type="button" class="main-header__account-icon">
					<img src="../app/assets/images/profile-icon.png" alt="">
				</button>

				<div class="dropdown account-menu">
					<div class="account-menu__header">
						<div class="account-menu__header-title">
							<h2>Jordan Ellis</h2>
							<span>Super Admin</span>
						</div>

						<img class="account-menu__icon" src="../app/assets/images/profile-icon.png" alt="">
					</div>

					<a href="" class="dropdown__link">My Profile</a>
					<!-- <a href="" class="dropdown__link">Get Support</a> -->
					<!-- <a href="" class="dropdown__link account-menu__dark-theme-toggle"><i class="fal fa-moon"></i> Dark Theme</a> -->
					<a href="" class="dropdown__link">Go To <span class="dropdown__link-shortcut">&#8984;J</span></a>
					<a href="" class="dropdown__link js-dark-theme-toggle">Dark Theme</a>

					<a href="" class="dropdown__link">Log Out</a>


					<div class="dropdown__divider"></div>

					<h3 class="dropdown__header">Quick Links</h3>
					<a href="" class="dropdown__link"><i class="fal fa-plus fa-sm"></i> New Link</a>
				</div>
			</div>

    </div>

		<div class="main-nav">
			<a class="main-nav__mobile-menu js-toggle-main-sidebar hidden">
				<svg xmlns="http://www.w3.org/2000/svg" width="18.585" height="13.939" viewBox="0 0 18.585 13.939"><g transform="translate(-210.99 -17.71)"><path d="M3,12.1H19.585" transform="translate(208.99 12.575)" fill="none" stroke-linecap="round" stroke-width="2"/><path d="M3,6H19.585" transform="translate(208.99 12.71)" fill="none" stroke-linecap="round" stroke-width="2"/><path d="M3,18H9.386" transform="translate(208.99 12.649)" fill="none" stroke-linecap="round" stroke-width="2"/></g></svg>
			</a>

			<div class="main-nav__title">
				<h1><?=$page_title?></h1>

				<!-- <ul class="breadcrumb">
					<li><a href=""><?=$page_title?></a></li>
				</ul> -->
			</div>

			<div class="main-nav__toolbar">
				<?=$page_toolbar ?? ""?>
				<!-- <input class="main-nav__toolbar-input" type="text" placeholder="Search Files"> -->
				<!-- <a href="" class="button button--action">Upload File</a> -->
			</div>

			<div class="main-nav__account">
				<button type="button" class="main-nav__account-icon">
					<img src="../app/assets/images/profile-icon.png" alt="">
				</button>

				<div class="dropdown account-menu">
					<div class="account-menu__header">
						<div class="account-menu__header-title">
							<h2>Jordan Ellis</h2>
							<span>Super Admin</span>
						</div>

						<img class="account-menu__icon" src="../app/assets/images/profile-icon.png" alt="">
					</div>

					<!-- <a href="" class="dropdown__link"><i class="fal fa-user"></i> My Profile</a>
					<a href="" class="dropdown__link"><i class="fal fa-life-ring"></i> Get Support</a>
					<a href="" class="dropdown__link account-menu__dark-theme-toggle"><i class="fal fa-moon"></i> Dark Theme</a>
					<a href="" class="dropdown__link"><i class="fal fa-sign-out-alt"></i> Log Out</a> -->

					<a href="" class="dropdown__link">My Profile</a>
					<!-- <a href="" class="dropdown__link">Get Support</a> -->
					<!-- <a href="" class="dropdown__link account-menu__dark-theme-toggle"><i class="fal fa-moon"></i> Dark Theme</a> -->
					<a href="" class="dropdown__link">Go To <span class="dropdown__link-shortcut">&#8984;J</span></a>
					<a href="" class="dropdown__link js-dark-theme-toggle">Dark Theme</a>

					<a href="" class="dropdown__link">Log Out</a>


					<div class="dropdown__divider"></div>

					<h3 class="dropdown__header">Quick Links</h3>
					<a href="" class="dropdown__link"><i class="fal fa-plus fa-sm"></i> New Link</a>
				</div>
			</div>
		</div>

		<div class="ee-main__content">
