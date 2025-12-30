<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

<div class="panel">
  <div class="tbl-ctrls">
    <?=form_open($base_url)?>
      <div class="panel-body">
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

        <?php $this->embed('_shared/table-list', ['data' => $addons]); ?>
        <?php if (isset($pagination)) {
            echo $pagination;
        } ?>

      </div>
    </form>
  </div>
</div>