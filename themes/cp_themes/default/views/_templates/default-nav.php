<?php extend_template('wrapper'); ?>

<div class="col-group">
	<?=$left_nav?>
	<div class="col w-12 last">
		<?php if (count($cp_breadcrumbs)): ?>
			<ul class="breadcrumb">
				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
					<li><a href="<?=$link?>"><?=$title?></a></li>
				<?php endforeach ?>
				<li class="last"><?=$cp_page_title?></li>
			</ul>
		<?php endif ?>
		<?php if (enabled('outer_box')) :?>
			<div class="box">
		<?php endif ?>
			<?=$EE_rendered_view?>
		<?php if (enabled('outer_box')) :?>
			</div>
		<?php endif ?>
	</div>
</div>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>