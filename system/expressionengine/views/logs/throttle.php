<?php extend_template('default-nav') ?>

<?=form_open(cp_url('logs/cp'), 'class="tbl-ctrls"')?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="filter_by_phrase" value="<?=$filter_by_phrase_value?>">
		<input class="btn submit" type="submit" value="<?=lang('search_logs_button')?>">
	</fieldset>
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<?php $this->view('_shared/form_messages')?>
	<fieldset class="tbl-filter">
		<?php
		if (isset($filters) && is_array($filters))
		{
			foreach ($filters as $filter)
			{
				echo $filter;
			}
		}
		?>
	</fieldset>
	<section class="item-wrap log">
		<?php if ($disabled): ?>
			<p class="no-results"><?=lang('throttling_disabled')?> <a class="btn action" href="<?=cp_url('settings/throttling')?>"><?=lang('enable_throttling')?></a></p>
		<?php else: ?>
			<?php if (empty($rows)): ?>
				<p class="no-results"><?=lang('no_search_results')?></p>
			<?php else: ?>
				<?php foreach($rows as $row): ?>

				<div class="item">
					<ul class="toolbar">
						<li class="remove"><a href="<?=cp_url('logs/delete/throttle/'.$row['throttle_id'])?>" title="remove"></a></li>
					</ul>
					<h3><b><?=lang('date_logged')?>:</b> <?=$row['last_activity']?>, <b><abbr title="<?=lang('internet_protocol')?>"><?=lang('ip')?></abbr>:</b> <?=$row['ip_address']?></h3>
					<div class="message">
						<p><?=lang('hits')?>: <?=$row['hits']?></p>
					</div>
				</div>

				<?php endforeach; ?>

				<?php $this->view('_shared/pagination'); ?>

				<fieldset class="tbl-bulk-act">
					<a class="btn remove" href="<?=cp_url('logs/delete/throttle')?>"><?=lang('clear_throttle_logs')?></a>
				</fieldset>
			<?php endif; ?>
		<?php endif; ?>
	</section>
<?=form_close()?>