<?php extend_template('wrapper'); ?>

<?php if (isset($header)): ?>
	<div class="col-group">
		<div class="col w-16 last">
			<div class="box full mb">
				<div class="tbl-ctrls">
					<?php if (isset($header['form_url'])): ?>
						<?=form_open($header['form_url'])?>
							<fieldset class="tbl-search right">
								<input placeholder="<?=lang('type_phrase')?>" type="text" value="">
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

<div class="col-group">
	<div class="col w-16 last">
		<ul class="breadcrumb">
			<?php foreach ($cp_breadcrumbs as $link => $title): ?>
				<li><a href="<?=$link?>"><?=$title?></a></li>
			<?php endforeach ?>
			<li class="last"><?=$cp_page_title?></li>
		</ul>

		<div class="box">
			<h1><?=$cp_page_title?></h1>
			<?=form_open($form_url, 'class="settings"')?>
			<?=ee('Alert')->getAllInlines()?>
				<fieldset class="col-group last">
					<div class="setting-txt col w-16">
						<em><?=sprintf(lang('last_edit'), $edit_date, '-')?></em>
					</div>
					<div class="setting-field col w-16 last">
						<textarea class="template-edit" cols="" rows="" name="template_data"><?=set_value('template_data', $template_data)?></textarea>
					</div>
				</fieldset>
				<fieldset class="form-ctrls">
					<button class="btn" name="submit" type="submit" value="update" data-submit-text="<?=lang('btn_update_template')?>" data-work-text="<?=lang('btn_update_template_working')?>"><?=lang('btn_update_template')?></button>
					<button class="btn" name="submit" type="submit" value="finish" data-submit-text="<?=lang('btn_update_and_finish_editing')?>" data-work-text="<?=lang('btn_update_template_working')?>"><?=lang('btn_update_and_finish_editing')?></button>
				</fieldset>
			</form>
		</div>

	</div>
</div>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>