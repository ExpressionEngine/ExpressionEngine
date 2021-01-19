<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel">

  <div class="panel-heading">
    <div class="title-bar title-bar--large">
      <h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
    </div>
  </div>
  
  <div class="panel-body">
    <ul class="list-data">
        <?php foreach ($items as $item => $value): ?>
            <li<?php if (end($items) === $value): ?> class="last"<?php endif ?>>
                <b><?=lang($item)?></b> <span><?=($value) ?: '&mdash;'?></span>
            </li>
        <?php endforeach; ?>
    </ul>
  </div>
</div>
