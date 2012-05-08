<?php extend_template('basic'); ?>

<?php
$menu_array = array();

if (strpos($controller, '/') !== FALSE) 
{
	$menu_parts = explode('/', $controller);
	$menu_array = array($menu_parts[1] => $cp_menu_items[$menu_parts[0]][$menu_parts[1]]);
}
else
{
	if (isset($cp_menu_items[$controller]))
	{
		$menu_array = $cp_menu_items[$controller];
	}
}

foreach($menu_array as $menu_key=>$menu_value):
	if ($menu_key == 'overview') continue;
	if ($menu_value !== '----' AND ! is_array($menu_value)):
?>
<div class="overview">
	<div class="heading"><h2><?=lang('nav_'.$menu_key)?></h2></div>
	<table class="mainTable" cellspacing="0" cellpadding="0" border="0">
			<tr class="even">
			<?php if ($this->input->get('C') == 'content' && $menu_key == 'publish'):?>
				<td class="overviewItemName"><a href="<?=$menu_value?>"><?=lang($menu_key)?></a></td>
				<td class="overviewItemDesc"></td>
				<td class="overviewItemHelp"><a rel="external" href="<?=$this->cp->masked_url($this->menu->generate_help_link($controller, $menu_key, $controller))?>"><img src="<?=$cp_theme_url?>images/external_link.png"/></a></td>
			<?php else:?>
				<td class="overviewItemName"><a href="<?=$menu_value?>"><?=lang('nav_'.$menu_key)?></a></td>
				<td class="overviewItemDesc"><?=lang('nav_'.$menu_key.'_short_desc')?></td>
				<td class="overviewItemHelp"><a rel="external" href="<?=$this->cp->masked_url($this->menu->generate_help_link($controller, $menu_key, $controller))?>"><img src="<?=$cp_theme_url?>images/external_link.png"/></a></td>
			<?php endif;?>
			</tr>
	</table>
	<div class="tableFooter"></div>
</div>

<?php
	elseif ($menu_value !== '----'):
?>
	<div class="overview">
		<div class="heading"><h2><?=lang('nav_'.$menu_key)?></h2></div>
		<table class="mainTable" cellspacing="0" cellpadding="0" border="0">
			<?php
				foreach($menu_value as $item_key=>$item_value):
					if ($item_value !== '----' AND ! is_array($item_value)):
			?>
				<tr class="<?=alternator('even', 'odd')?>">
				<?php if ($this->input->get('C') == 'content' && ($menu_key == 'publish' OR $menu_key == 'edit')):?>
					<td class="overviewItemName"><a href="<?=$item_value?>"><?=lang($item_key)?></a></td>
					<td class="overviewItemDesc">&nbsp;</td>
					<td class="overviewItemHelp"><a rel="external" href="<?=$this->cp->masked_url($this->menu->generate_help_link($controller, $item_key, $controller))?>"><img src="<?=$cp_theme_url?>images/external_link.png"/></a></td>
				<?php else:?>
					<td class="overviewItemName"><a href="<?=$item_value?>"><?=lang('nav_'.$item_key)?></a></td>
					<td class="overviewItemDesc"><?=lang('nav_'.$item_key.'_short_desc')?></td>
					<td class="overviewItemHelp"><a rel="external" href="<?=$this->cp->masked_url($this->menu->generate_help_link($controller, $item_key, $controller))?>"><img src="<?=$cp_theme_url?>images/external_link.png"/></a></td>							
				<?php endif;?>
				</tr>
			<?php 
					endif;
				endforeach;
			?>
		</table>
		<div class="tableFooter"></div>
	</div>

	<?php endif;?>

<?php endforeach;?>
<?php
/* End of file overview.php */
/* Location: ./themes/cp_themes/default/_shared/overview.php */