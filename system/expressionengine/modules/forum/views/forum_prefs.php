<?=form_open($_form_base.AMP.'method=forum_prefs_update', '', $hidden)?>

<span class="cp_button"><a id="toggle_accordion" href="#"><?=lang('show_hide')?></a></span>

<div class="clear_left"></div>

<div class="shun">
<?php foreach ($P as $title => $menu): ?>	
	<div class="editAccordion <?=($title == 'general') ? 'open' : ''; ?>">		
		<h3><?=lang('forum_prefs_'.$title)?></h3>
		<div>
			<table class="templateTable templateEditorTable" border="0" cellspacing="0" cellpadding="0" style="margin: 0;">
			
			<?php foreach($menu as $item => $parts): ?>
				<tr>
					<td style="width: 50%"><?=$parts['label'].$parts['subtext']; ?>
					<td><?=$parts['field']?></td>
				</tr>
			<?php endforeach;?>
			
			</table>
		</div>
	</div>
	
	<?php if ($title == 'image'): ?>
	</div>
	
	<h3><?=lang('forum_board_prefs_default')?></h3>
	<p><?=lang('forum_board_prefs_default_inst')?></p>
	
	<div class="shun">
	<?php endif;?>
	
<?php endforeach; ?>
</div>

<p><?=form_submit('update', lang('update'), 'class="submit"')?></p>

<?=form_close()?>