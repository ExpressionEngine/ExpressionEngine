<?php if (REQ == 'PAGE' OR ee('LivePreview')->hasEntryData()): ?>
	<link rel="stylesheet" href="<?=URL_THEMES.'debug/css/eecms-debug.min.css'?>" type="text/css" media="screen" />
<?php endif; ?>

<section id="debug">
	<div class="col-group">
		<div class="col w-16">
			<div class="box has-tabs">
				<h1><?=$uri?></h1>
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


<?php if (REQ == 'PAGE' OR ee('LivePreview')->hasEntryData()): ?>
	<script>
	!function() {
		"use strict";

		var wrap = document.querySelector('#debug .tab-wrap');
		var tabs = wrap.querySelectorAll('ul.tabs a');
		var sheets = wrap.querySelectorAll('.tab');

		var removeClassFromAll = function(list, klass) {
			for (var i = 0; i < list.length; i++) {
				list[i].classList.remove(klass);
			}
		}

		var handleTabClick = function(evt) {
			evt.preventDefault();

			removeClassFromAll(tabs, 'act');
			removeClassFromAll(sheets, 'tab-open');

			var tab = this;
			var sheet = wrap.querySelector('.tab.' + this.rel);

			tab.classList.add('act');
			sheet.classList.add('tab-open');
		};

		for (var i = 0; i < tabs.length; i++) {
			tabs[i].addEventListener('click', handleTabClick, false);
		}

		var toggles = wrap.querySelectorAll('a.toggle');

		var toggleVisibility = function(el) {
			var detailElement = wrap.querySelector('.' + el.rel);
			var visible = +detailElement.getAttribute('data-toggle');

			el.innerHTML = ["hide details", "show more"][visible];

			detailElement.style.display = ["block", "none"][visible];
			detailElement.setAttribute('data-toggle', Math.abs(visible - 1));

			return false;
		}

		for (var i = 0; i < toggles.length; i++) {
			toggles[i].addEventListener('click', function(evt) { evt.preventDefault(); toggleVisibility(this); }, false);
		}
	}();
	</script>
<?php endif; ?>

<link rel="stylesheet" href="<?=URL_THEMES?>debug/css/highlight.css">
<script src="<?=URL_THEMES?>debug/javascript/highlight.min.js"></script>
<script>hljs.initHighlightingOnLoad();</script>
