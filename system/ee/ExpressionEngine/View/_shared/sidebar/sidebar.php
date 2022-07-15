<div class="secondary-sidebar <?=$containerClass?>" data-owner="<?=$owner?>">
    <div class="box sidebar <?=$class?>">
        <?=$sidebar?>
    </div>
    <div class="secondary-sidebar-toggle">
        <a href="" class="secondary-sidebar-toggle__target <?=(isset($left_nav_collapsed) && $left_nav_collapsed ? 'collapsed' : '')?>" title="<?=lang('toggle_sidebar')?>">
            <i class="fal fa-angle-<?=(isset($left_nav_collapsed) && $left_nav_collapsed ? 'right' : 'left')?>"></i>
        </a>
    </div>
</div>
