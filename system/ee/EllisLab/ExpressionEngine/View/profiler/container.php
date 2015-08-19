<?php if (REQ == 'PAGE'): ?>
	<link rel="stylesheet" href="<?=URL_THEMES.'debug/debug.min.css'?>" type="text/css" media="screen" />
<?php endif; ?>

<section id="debug">
	<div class="col-group">
		<div class="col w-16">
			<div class="box has-tabs">
				<h1>Something</h1>
				<div class="tab-wrap">
					<ul class="tabs">
						<?php foreach ($sections as $i => $section): ?>
							<li><a <?=($i==0)?'class="act"':''?> href="" rel="t-<?=$i?>"><?=$section->getSummary()?></a></li>
						<?php endforeach; ?>
					</ul>
					<?php
					foreach ($rendered_sections as $rendered_section)
					{
						echo $rendered_section;
					}
					?>
				</div>
			</div>
		</div>
	</div>
</section>


<?php
if (REQ == 'PAGE')
{
	ee()->load->library('view');
	echo ee()->view->script_tag('jquery/jquery.js');
	echo ee()->view->script_tag('common.js');
}
?>
