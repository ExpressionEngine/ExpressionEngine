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
    <a href="<?=$addlink?>" class="dropdown__item-button button button--link button--xsmall"><i class="fas fa-plus"></i></a>
    <?php endif; ?>
</div>