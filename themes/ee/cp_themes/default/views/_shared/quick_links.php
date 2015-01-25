<div id="quickLinks">
	<h4>
		<?=lang('quick_links')?>
		<span id="sidebar_quick_links_edit_desc" class="sidebar_hover_desc"><?=lang('click_to_edit')?></span>
	</h4>

	<ul class="bullets">
		<?php foreach($cp_quicklinks as $cp_quicklink):?>
			<?php
			if ( ! $cp_quicklink['external']):?>
				<li><a href="<?=$cp_quicklink['link']?>" title="<?=$cp_quicklink['title']?>"><?=$cp_quicklink['title']?></a></li>
			<?php else:?>
				<li><a rel="external" href="<?=$this->cp->masked_url($cp_quicklink['link'])?>" title="<?=$cp_quicklink['title']?>"><?=$cp_quicklink['title']?></a>&nbsp;<img src="<?=$cp_theme_url?>images/external_link.png"/></li>
			<?php endif;?>
		<?php endforeach;?>
		<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=quicklinks'.AMP.'id='.$this->session->userdata['member_id']?>"><?=lang('quicklinks_manager')?></a></li>
	</ul>
</div> 

<?php
/* End of file quick_links.php */
/* Location: ./themes/cp_themes/default/_shared/quick_links.php */