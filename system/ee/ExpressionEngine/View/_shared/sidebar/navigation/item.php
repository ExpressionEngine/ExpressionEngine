<?php if ($url) : ?>
  <a href="<?=$url?>" title="<?=lang('go_to')?> <?=$text?>"
<?php else : ?>
  <span
<?php endif; ?>
 <?=$attrs?> class="ee-sidebar__item <?=$class?>">
<?php if (!empty($icon)) : ?><i class="fal fa-<?=$icon?>"></i><?php endif; ?>
    <span class="ee-sidebar__collapsed-hidden"><?=$text?></span>
<?php if ($url) : ?>
  </a>
<?php else : ?>
  </span>
<?php endif; ?>