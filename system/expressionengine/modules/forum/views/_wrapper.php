<div id="forum_global_nav" class="shun">
<?php if ($_show_nav): ?>
		<?=form_open($_form_base); ?>
		<span class="button" style="float:right;">
			<?=form_dropdown('board_id', $_boards, $_board_id)?>
		</span>
		<input type="submit" class="submit js_hide" value="<?=lang('update')?>">
		<?=form_close(); ?>
<?php else: ?>
		<span style="float:right;"><a class="submit" href="<?=$_base?>"><?=lang('home')?></a></span>
<?php endif; ?>
		<div class="clear_left shun">&nbsp;</div>
	</div>

<?php if ($_show_nav): ?>
	<ul class="tab_menu" id="tab_menu_tabs">
		<li class="content_tab<?=($_current_tab == 'forum_board_home') ? ' current': ''?>">
			<a href="<?=$_id_base?>"><?=lang('forum_board_home')?></a>&nbsp;
		</li>
		<?php if ($reduced_nav == FALSE): ?>
		<li class="content_tab<?=($_current_tab == 'forum_management') ? ' current': ''?>">
			<a href="<?=$_id_base.AMP.'method=forum_management'?>"><?=lang('forum_management')?></a>&nbsp;
		</li>
		<li class="content_tab<?=($_current_tab == 'forum_admins') ? ' current': ''?>">
			<a href="<?=$_id_base.AMP.'method=forum_admins'?>"><?=lang('forum_admins')?></a>&nbsp;
		</li>
		<li class="content_tab<?=($_current_tab == 'forum_moderators') ? ' current': ''?>">
			<a href="<?=$_id_base.AMP.'method=forum_moderators'?>"><?=lang('forum_moderators')?></a>&nbsp;
		</li>
		<li class="content_tab">
			<a rel="external" href="<?=$board_forum_url?>"><?=lang('forum_launch')?></a>&nbsp;
		</li>
		<?php endif; ?>
	</ul>
	
	<div class="clear_left shun"></div>
<?php endif; ?>

	<div>
		<?=$EE_rendered_view?>
	</div>

<?php
/* End of file _wrapper.php */
/* Location: ./system/expressionengine/modules/forum/index/_wrapper.php */