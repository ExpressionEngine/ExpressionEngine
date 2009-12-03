<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
    // $this->load->view('_shared/main_menu');
    // $this->load->view('_shared/sidebar');
    // $this->load->view('_shared/breadcrumbs');
}
?>
<div id="overview" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=homepage"><?=lang('back')?></a>
    </div>

    <?php
    if (strpos($controller, '/') !== FALSE) 
    {
    	$menu_parts = explode('/', $controller);
    	$menu_array = array($menu_parts[1]=>$cp_menu_items[$menu_parts[0]][$menu_parts[1]]);
    }
    else
    {
    	$menu_array = $cp_menu_items[$controller];
    }

    foreach($menu_array as $menu_key=>$menu_value):
     if ($menu_key == 'overview') continue;
     if ($menu_value !== '----' AND ! is_array($menu_value)):
    ?>

    <ul id="<?=$menu_key?>">
        <li><a href="<?=$menu_value?>"><?=lang('nav_'.$menu_key)?></a></li>
    </ul>
    
    <?php
		elseif ($menu_value !== '----'):
	?>
	
	<h2><?=lang('nav_'.$menu_key)?></h2>
	<ul id="<?=$menu_key?>">
	    <?php
			foreach($menu_value as $item_key=>$item_value):
				if ($item_value !== '----' AND ! is_array($item_value)):
		?>
	    <li><a href="<?=$item_value?>"><?=lang('nav_'.$item_key)?></a></li>
    <?php 
			endif;
		endforeach;
	?>    		
	</ul>
	

	<?php endif;?>
    
    <?php endforeach;?>


</div>


<?php
if ($EE_view_disable !== TRUE)
{
    // $this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file overview.php */
/* Location: ./themes/cp_themes/mobile/_shared/overview.php */