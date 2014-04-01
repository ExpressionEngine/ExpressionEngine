		</section>
		<section class="product-bar">
			<div class="snap">
				<div class="left">
					<p><b>ExpressionEngine</b> <span class="version" title="About ExpressionEngine"><?=APP_VER?></span></p>
					<!-- use class out-of-date on the version link. -->
					<!-- <div class="version-info">
						<h3>Installed</h3>
						<p>ExpressionEngine <b>3</b>.0<br><em>build date: 07.12.2014</em></p>
						<h3>Latest Version (<a href="" rel="external">download</a>)</h3>
						<p>ExpressionEngine <b>3</b>.0.1<br><em>build date: 09.16.2014</em></p>
						<a href="" class="close">&#10006;</a>
						<div class="status out">out of date</div>
					</div> -->
					<div class="version-info">
						<h3>Installed</h3>
						<p>ExpressionEngine <?=APP_VER?><br><em><?=lang('build').APP_BUILD?></em></p>
						<a href="" class="close">&#10006;</a>
						<div class="status">current</div>
					</div>
				</div>
				<div class="right"><p><a href="/report-bug" rel="external">Report Bug</a> <b class="sep">&middot;</b> <a href="/new-ticket" rel="external">New Ticket</a> <b class="sep">&middot;</b> <a href="/manual" rel="external">Manual</a></p></div>
			</div>
		</section>
		<section class="footer">
			<div class="snap">
				<div class="left"><p>&copy;2003&mdash;<?=date('Y')?> <a href="<?=ee()->cp->masked_url('http://ellislab.com/expressionengine')?>" rel="external">EllisLab</a>, Inc.</p></div>
				<div class="right"><p><a class="scroll" href="#top">scroll to top</a></p></div>
			</div>
		</section>
		<div class="overlay"></div>
		<!-- <div class="alert warn">
			<h3>Exclamation!</h3>
			<p>A message that relates to the above exclamation of success, failure or just a heads up.</p>
			<a class="close" href=""></a>
		</div> -->
		<?=ee()->view->script_tag('jquery/jquery.js')?>
		<?=ee()->view->script_tag('v3/cmon-ck.js')?>
		<?php
		if (isset($cp_global_js))
		{
			echo $cp_global_js;
		}
		echo $this->cp->render_footer_js();

		if (isset($library_src))
		{
			echo $library_src;
		}

		if (isset($script_foot))
		{
			echo $script_foot;
		}

		foreach ($this->cp->footer_item as $item)
		{
			echo $item."\n";
		}
		?>
	</body>
</html>


<!--<div id="idle-modal" class="pageContents">
	<p id="idle-description" class="shun"><?=lang('session_idle_description')?></p>

	<p class="idle-fourth"><strong>User:</strong></p>

	<?=form_open('C=login&M=authenticate')?>
	<div class="idle-three-fourths shun">
		<p class="idle-fourth">
			<img src="<?=$cp_avatar_path ? $cp_avatar_path : $cp_theme_url.'images/site_logo.gif'?>" width="50" alt="User Avatar" />
		</p>
		<p class="idle-three-fourths">
			<p id="idle-screen-name"><?=$cp_screen_name?></p>
			<input type="hidden" name="username" value="<?=form_prep($this->session->userdata('username'))?>" />
			<span class="idle-member-group"><?=$this->session->userdata('group_title')?></span>
		</p>
	</div>

	<div class="idle-fourth">
		<p><label for="logout-confirm-password">Password:</label></p>
	</div>
	<div class="idle-three-fourths shun">
		<p><input type="password" name="password" class="field" id="logout-confirm-password"/></p>
	</div>

	<p id="idle-button-group">
		<a href="<?=BASE.AMP.'C=login&M=logout'?>"><?=sprintf(lang('session_idle_not_name'), $cp_screen_name)?></a> &nbsp;
		<input type="submit" class="submit" id="idle-login-button" value="<?=lang('login')?>" />
	</p>
	<?=form_close()?>
</div>

<div id="notice_container">
	<div id="notice_texts_container">
		<a id="close_notice" href="javascript:jQuery.ee_notice.destroy();">&times;</a>

		<div class="notice_texts notice_success"></div>
		<div class="notice_texts notice_alert"></div>
		<div class="notice_texts notice_error"></div>
		<div class="notice_texts notice_custom"></div>
	</div>
	<div id="notice_flag">
		<p id="notice_counts">
			<span class="notice_success"><img src="<?=$cp_theme_url?>images/success.png" alt="" width="14" height="14" /></span>
			<span class="notice_alert"><img src="<?=$cp_theme_url?>images/alert.png" alt="" width="14" height="14" /></span>
			<span class="notice_error"><img src="<?=$cp_theme_url?>images/error.png" alt="" width="14" height="14" /></span>
			<span class="notice_info"><img src="<?=$cp_theme_url?>images/info.png" alt="" width="14" height="14" /></span>
		</p>
	</div>
</div>-->