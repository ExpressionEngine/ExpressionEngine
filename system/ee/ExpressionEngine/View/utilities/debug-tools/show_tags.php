<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel">

	<div class="panel-heading">
		<div class="title-bar">
			<h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
		</div>
	</div>

	<div class="panel-body">
		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
	</div>

</div>

