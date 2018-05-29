<?php $this->extend('_templates/wrapper'); ?>

<?php if (isset($header)): ?>
	<div class="col-group">
		<div class="col w-16 last">
			<header class="section-header">
				<?php if (isset($header['toolbar_items']) && $header['toolbar_items']): ?>
					<div class="section-header__options">
						<?php foreach ($header['toolbar_items'] as $name => $item): ?>
							<a class="icon--<?=$name?>" href="<?=$item['href']?>" title="<?=$item['title']?>"></a>
						<?php endforeach; ?>
					</div>
				<?php endif ?>
				<div class="section-header__title">
					<?=$header['title']?>
				</div>
				<?php if (isset($header['action_button']) || isset($header['search_form_url'])): ?>
					<div class="section-header__controls">
					<?php if (isset($header['search_form_url'])): ?>
						<?=form_open($header['search_form_url'])?>
							<fieldset class="tbl-search right">
								<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=form_prep(ee()->input->get_post('search'))?>">
								<?php if (isset($header['search_button_value'])): ?>
								<input class="btn submit" type="submit" value="<?=$header['search_button_value']?>">
								<?php else: ?>
								<input class="btn submit" type="submit" value="<?=lang('search')?>">
								<?php endif; ?>
							</fieldset>
						</form>
					<?php endif ?>
					<?php if (isset($header['action_button'])): ?>
						<?php if (isset($header['action_button']['choices'])): ?>
							<div class="filter-item filter-item--right">
								<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu filter-item__link--action"><?=$header['action_button']['text']?></a>
								<div class="filter-submenu">
									<?php if (count($header['action_button']['choices']) > 8): ?>
										<div class="filter-submenu__search">
											<input type="text" value="" data-fuzzy-filter="true" placeholder="<?=$header['action_button']['filter_placeholder']?>">
										</div>
									<?php endif ?>
									<div class="filter-submenu__scroll">
										<?php foreach ($header['action_button']['choices'] as $link => $text): ?>
											<a href="<?=$link?>" class="filter-submenu__link"><?=$text?></a>
										<?php endforeach ?>
									</div>
								</div>
							</div>
						<?php else: ?>
							<a class="btn action" href="<?=$header['action_button']['href']?>"><?=$header['action_button']['text']?></a>
						<?php endif ?>
					<?php endif ?>
					</div>
				<?php endif ?>
			</header>
		</div>
	</div>
<?php endif ?>

<div class="col-group align-right">
	<?php if (isset($left_nav)): ?>
		<div class="col w-12">
	<?php else: ?>
		<div class="col w-16">
	<?php endif; ?>
		<?php if (count($cp_breadcrumbs)): ?>
			<ul class="breadcrumb">
				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
					<li><a href="<?=$link?>"><?=$title?></a></li>
				<?php endforeach ?>
				<li class="last"><?=ee('Format')->make('Text', isset($breadcrumb_title) ? $breadcrumb_title : $cp_page_title)->attributeSafe()->compile()?></li>
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
	<?php if (isset($left_nav)): ?>
		<div class="col w-4">
			<?=$left_nav?>
		</div>
	<?php endif; ?>
</div>
