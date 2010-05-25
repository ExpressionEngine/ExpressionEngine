<?php
if ($EE_view_disable !== TRUE)
{
    $this->load->view('_shared/header');
}
?>
    <div id="home" class="current">
        <div class="toolbar">
            <h1><?=$cp_page_title?></h1>
            <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
        </div>
        <?php
        $count = 0;
        $total_results = count($cp_menu_items);
        $second_level_nav = array(); 
        $third_level_nav = array();
        $forth_level_nav = array(); ?>
        <?php
        foreach ($cp_menu_items as $name => $val): 
            $count++; 
            
                if ($count == 1): ?>
                    <ul id="home" class="rounded">      
                <?php endif; ?>
                <?php if (is_array($val)): ?>
                    <li class="arrow"><a href="#<?=$name?>" title="<?=lang('nav_'.$name)?>"><?=lang('nav_'.$name)?></a></li>
                    <?php $second_level_nav[$name] = $val; ?>
                <?php else: ?>
                    <?php if (substr($val, 0, 4) == 'http'): ?>
                    <li class="arrow"><a href="<?=$val?>" target="_blank" title="<?=lang('nav_'.$name)?>"><?=lang('nav_'.$name)?></a></li>
                    <?php else: ?>
                    <li class="arrow"><a href="<?=$name?>" title="<?=lang('nav_'.$name)?>"><?=lang($name)?></a></li>
                    <?php endif;?>
                <?php endif; ?>
                <?php if ($count == $total_results): ?>
					<li class="arrow"><a href="<?=BASE.AMP.'C=myaccount'?>" title="<?=lang('my_account')?>"><?=lang('my_account')?></a></li>
                </ul>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php foreach($second_level_nav as $name => $val):?>
    <div id="<?=$name?>" title="<?=lang('nav_'.$name)?>">
        <div class="toolbar">
            <h1><?=lang('nav_'.$name)?></h1>
            <a href="#" class="back"><?=lang('back')?></a>
            <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
        </div>
        <ul id="<?=$name?>" class="rounded">
        <?php foreach($val as $sub => $href):?>
            <?php if (is_array($href)): ?>
            <li class="arrow"><a href="#<?=$sub?>" title="<?=lang('nav_'.$sub)?>"><?=lang('nav_'.$sub)?></a></li>
            <?php
            $third_level_nav[$sub] = $href;
            else: ?>
                <?php if ($href != '----' && $sub != 'overview'):?>
                    <li class="arrow"><a href="<?=$href?>" title="<?=lang('nav_'.$sub)?>"><?=lang('nav_'.$sub)?></a></li>
                <?php endif;?>
            <?php endif;?>
        <?php endforeach;?>
        </ul>
    </div>
    <?php endforeach;?>


    <?php foreach($third_level_nav as $name => $val):?>
    <div id="<?=$name?>" title="<?=lang('nav_'.$name)?>">
        <div class="toolbar">
            <h1><?=lang('nav_'.$name)?></h1>
	        <a class="back" href="#"><?=lang('back')?></a>
            <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
        </div>
        <ul id="<?=$name?>" class="rounded">
        <?php foreach($val as $sub => $href):?>
            <?php if (is_array($href)):?>
            <li class="arrow"><a href="#<?=$sub?>" title="<?=lang('nav_'.$sub)?>"><?=lang('nav_'.$sub)?></a></li>
            <?php $forth_level_nav[$sub] = $href; ?>
            <?php else:?>
                <?php if ($href != '----'):?>
                <li class="arrow"><a href="<?=$href?>" title="<?=lang('nav_'.$sub)?>"><?=lang('nav_'.$sub)?></a></li>
                <?php endif;?>
            <?php endif;?>
        <?php endforeach;?>
        </ul>
    </div>
    <?php endforeach;?>

<?php $this->load->view('_shared/footer');?>

<?php
/* End File:  homepage.php */
/* Location:  ./themes/cp_themes/mobile/homepage.php */