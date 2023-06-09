<div class="dropdown__item">
    <?php if ($url) : ?>
    <a href="<?=$url?>" <?=$attrs?> class="<?=$class?>">
        <?=$text?>
    </a>
    <?php else : ?>
        <span <?=$attrs?> class="<?=$class?>">
            <?=$text?>
        </span>
    <?php endif; ?>
    <?php if ($addlink) : ?>
    <a href="<?=$addlink?>" class="dropdown__item-button button button--secondary button--xsmall"<?=$addlinkAttibutes?>><i class="fal fa-plus"></i><span class="hidden"><?=lang('btn_create_new') . ' ' . $text?></span></a>
    <?php endif; ?>
</div>
<?php if ($divider) : ?>
<div class="dropdown__divider"></div>
<?php endif; ?>