<?php $this->extend('_templates/wrapper'); ?>

<?php if (isset($header)): ?>
	<div class="col-group">
		<div class="col w-16 last">
			<header class="section-header">
				<?php if (isset($header['toolbar_items'])): ?>
					<div class="section-header__options">
						<?php foreach ($header['toolbar_items'] as $name => $item): ?>
							<a class="icon--<?=$name?>" href="<?=$item['href']?>" title="<?=$item['title']?>"></a>
						<?php endforeach; ?>
					</div>
				<?php endif ?>
				<div class="section-header__title">
					<?=$header['title']?>
				</div>
			</header>
		</div>
	</div>
<?php endif ?>

<div class="col-group">
	<?php if (isset($left_nav)): ?>
	<?=$left_nav?>
	<?php endif; ?>
	<?php if (isset($left_nav)): ?>
	<div class="col w-12 last">
	<?php else: ?>
	<div class="col w-16 last">
	<?php endif; ?>
		<?php if (count($cp_breadcrumbs)): ?>
			<ul class="breadcrumb">
				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
					<li><a href="<?=$link?>"><?=$title?></a></li>
				<?php endforeach ?>
				<li class="last"><?=$cp_page_title?></li>
			</ul>
		<?php endif ?>
		<?php if ($this->enabled('outer_box')) :?>
			<div class="box">
		<?php endif ?>
			<?=$child_view?>
		<?php if ($this->enabled('outer_box')) :?>
			</div>
		<?php endif ?>
	</div>
</div>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>
<?php echo implode('', ee('CP/Modal')->getAllModals()); ?>
