<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box table-list-wrap">
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<div class="tbl-ctrls">

		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
	</div>
</div>

