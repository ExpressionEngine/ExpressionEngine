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
		<div class="box">
			<?=$EE_rendered_view?>
		</div>
	</div>
</div>