<div id="mainMenu">
	<ul id="navigationTabs">
		<li class="home"><a href="<?=BASE?>" title="<?=lang('main_menu')?>" class="first_level"><span><img src="<?=$cp_theme_url?>images/home_icon.png" width="12" height="13" alt="<?=lang('main_menu')?>" /></span></a></li>

			<?php echo $menu_string; ?>

			<li><a class="addTab first_level" id="addQuickTab" href="<?=generate_quicktab($cp_page_title)?>"> Add</a></li>
		</ul>
		<div class="clear"></div>
</div>

<div id="mainWrapper">