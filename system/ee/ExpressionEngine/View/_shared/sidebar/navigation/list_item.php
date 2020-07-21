<div class="dropdown__item">
    <a href="<?=$url?>" <?=$attrs?> class="<?=$class?>">
        <?=$text?>
    </a>
    <?php if ($addlink) : ?>
    <a href="<?=$addlink?>" class="dropdown__item-button button button--link button--xsmall"><i class="fas fa-plus"></i></a>
    <?php endif; ?>
</div>