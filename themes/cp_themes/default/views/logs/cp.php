<?php extend_template('default-nav') ?>

<div class="tbl-ctrls">
<?=form_open($form_url)?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$search_value?>">
		<input class="btn submit" type="submit" value="<?=lang('search_logs_button')?>">
	</fieldset>
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<?php $this->view('_shared/filters'); ?>
	<?php $this->view('_shared/alerts')?>
	<section class="item-wrap log">
		<?php if (empty($rows)): ?>
			<p class="no-results"><?=lang('no_control_panel_logs_found')?></p>
		<?php else: ?>
			<?php foreach($rows as $row): ?>

			<div class="item">
				<ul class="toolbar">
					<li class="remove"><a href="<?=cp_url('logs/cp/delete/'.$row['id'])?>" title="remove"></a></li>
				</ul>
				<h3><b><?=lang('date_logged')?>:</b> <?=$row['act_date']?>, <b><?=lang('site')?>:</b> <?=$row['site_label']?><br><b><?=lang('username')?>:</b> <?=$row['username']?>, <b><abbr title="<?=lang('internet_protocol')?>"><?=lang('ip')?></abbr>:</b> <?=$row['ip_address']?></h3>
				<div class="message">
					<p><?=$row['action']?></p>
				</div>
			</div>

			<?php endforeach; ?>

			<?php $this->view('_shared/pagination'); ?>

			<fieldset class="tbl-bulk-act">
				<a class="btn remove" href="<?=cp_url('logs/cp/delete')?>"><?=lang('clear_cp_logs')?></a>
			</fieldset>
		<?php endif; ?>
	</section>
<?=form_close()?>
</div>