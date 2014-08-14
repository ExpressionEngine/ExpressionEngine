<div class="col-group">
	<div class="col w-16 last">
		<a class="m-close" href="#"></a>
		<div class="box">
			<h1><?=$email->subject?></h1>
			<div class="txt-wrap">
				<ul class="checklist mb">
					<li><b><?=lang('sent')?>:</b> <?=ee()->localize->human_time($email->cache_date)?> <?=lang('to')?> <?=$email->total_sent?> <?=lang('recipients')?></li>
				</ul>
				<?=$email->message?>
			</div>
		</div>
	</div>
</div>