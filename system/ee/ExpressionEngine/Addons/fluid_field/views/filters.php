<?php if (count($filters) < 20) : ?>
    <?php foreach ($filters as $filter) : ?>
        <a href="#" class="button button--auto button--default button--small" data-field-name="<?=$filter->name?>">
            <img src="<?=$filter->icon?>" width="16" height="16" alt="<?=lang('add')?> <?=$filter->label?>" /><br />
            <?=lang('add')?> <?=$filter->label?>
        </a>
    <?php endforeach; ?>

<?php else : ?>

    <a href="javascript:void(0)" class="js-dropdown-toggle button button--auto button--default button--small"><i class="fa-2x icon--add"></i><br /> <?=lang('add_field')?></a>
    <div class="dropdown">
        <?php foreach ($filters as $filter) : ?>
            <a href="#" class="dropdown__link" data-field-name="<?=$filter->name?>"><img src="<?=$filter->icon?>" width="12" height="12" /> <?=$filter->label?></a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>