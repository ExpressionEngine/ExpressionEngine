<?php extend_template('default-nav') ?>

<div class="col w-12 last">
	<div class="box">
		<form class="tbl-ctrls">
			<fieldset class="tbl-search right">
				<input placeholder="<?=lang('type_phrase')?>" type="text" value="">
				<input class="btn submit" type="submit" value="<?=lang('search_logs_button')?>">
			</fieldset>
			<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
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
				<?php if (empty($rows)): ?>
					<?=$no_results?>
				<?php else: ?>
					<?php foreach($rows as $row): ?>

					<div class="item">
						<ul class="toolbar">
							<li class="solo remove"><a href="http://localhost/el-projects/ee-cp/views/" title="remove"></a></li>
						</ul>
						<h3><b><?=lang('date_logged')?>:</b> <?=$row['act_date']?>, <b><?=lang('site')?>:</b> <?=$row['site_label']?><br><b><?=lang('username')?>:</b> <?=$row['username']?>, <b><abbr title="<?=lang('internet_protocol')?>"><?=lang('ip')?></abbr>:</b> <?=$row['ip_address']?></h3>
						<div class="message">
							<p><?=$row['action']?></p>
						</div>
					</div>

					<?php endforeach; ?>
				<?php endif; ?>

				<div class="paginate">
					<ul>
						<li><a href="">First</a></li>
						<li><a href="">Prev</a></li>

						<li><a href="">1</a></li>
						<li><a class="act" href="">2</a></li>
						<li><a href="">3</a></li>

						<li><a href="">Next</a></li>
						<li><a class="last" href="">Last</a></li>
					</ul>
				</div>
				<fieldset class="tbl-bulk-act">
					<a class="btn remove" href="<?=cp_url('logs/clear_log_files', array('type' => 'cp'))?>"><?=lang('clear_cp_logs')?></a>
				</fieldset>
			</section>
		</form>
	</div>
</div>
