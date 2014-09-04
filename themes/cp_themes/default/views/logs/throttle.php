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
		<?php if ($disabled): ?>
			<p class="no-results"><?=lang('throttling_disabled')?> <a class="btn action" href="<?=cp_url('settings/throttling')?>"><?=lang('enable_throttling')?></a></p>
		<?php else: ?>
			<?php if (empty($rows)): ?>
				<p class="no-results"><?=lang('no_throttling_logs_found')?></p>
			<?php else: ?>
				<?php foreach($rows as $row): ?>

				<div class="item">
					<ul class="toolbar">
						<li class="remove"><a href="<?=cp_url('logs/throttle/delete/'.$row['throttle_id'])?>" title="remove"></a></li>
					</ul>
					<h3><b><?=lang('date_logged')?>:</b> <?=$row['last_activity']?>, <b><abbr title="<?=lang('internet_protocol')?>"><?=lang('ip')?></abbr>:</b> <?=$row['ip_address']?></h3>
					<div class="message">
						<p><?=lang('hits')?>: <?=$row['hits']?></p>
					</div>
				</div>

				<?php endforeach; ?>

				<?php $this->view('_shared/pagination'); ?>

				<fieldset class="tbl-bulk-act">
					<a class="btn remove" href="<?=cp_url('logs/throttle/delete')?>"><?=lang('clear_throttle_logs')?></a>
				</fieldset>
			<?php endif; ?>
		<?php endif; ?>
	</section>
<?=form_close()?>
</div>