<?php $this->extend('_templates/wrapper'); ?>

<?php if (isset($header)): ?>
	<div class="col-group">
		<div class="col w-16 last">
			<div class="box full mb">
				<div class="tbl-ctrls">
					<?php if (isset($header['form_url'])): ?>
						<?=form_open($header['form_url'])?>
							<fieldset class="tbl-search right">
								<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=form_prep(ee()->input->get_post('search'))?>">
								<?php if (isset($header['search_button_value'])): ?>
								<input class="btn submit" type="submit" value="<?=$header['search_button_value']?>">
								<?php else: ?>
								<input class="btn submit" type="submit" value="<?=lang('search')?>">
								<?php endif; ?>
							</fieldset>
					<?php endif ?>
						<h1>
							<?=$header['title']?>
							<?php if (isset($header['toolbar_items']))
							{
								echo ee()->load->view('_shared/toolbar', $header, TRUE);
							} ?>
						</h1>
					<?php if (isset($header['form_url'])): ?>
						</form>
					<?php endif ?>
				</div>
			</div>
		</div>
	</div>
<?php endif ?>

<div class="col-group align-right">
	<?php if (isset($left_nav)): ?>
	<div class="col w-12">
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

	<?php if (isset($left_nav)): ?>
	<?=$left_nav?>
	<?php endif; ?>
</div>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>
<?php echo implode('', ee('CP/Modal')->getAllModals()); ?>
