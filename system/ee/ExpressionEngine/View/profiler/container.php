<?php if (REQ == 'PAGE' or ee('LivePreview')->hasEntryData()): ?>
	<link rel="stylesheet" href="<?=URL_THEMES . 'debug/css/eecms-debug.min.css'?>" type="text/css" media="screen" />
<?php endif; ?>

<section class="ee-debugger">
	<div class="ee-debugger__inner">
		<h1 class="ee-debugger__title"><?=$uri?></h1>
		<div class="tab-wrap">
			<div class="tab-bar">
				<div class="tab-bar__tabs">
					<?php foreach ($sections as $i => $section): ?>
						<button type="button" class="tab-bar__tab js-tab-button <?=($i == 0) ? 'active' : ''?>" rel="t-<?=$i?>"><?=$section->getSummary()?></button>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
            foreach ($rendered_sections as $rendered_section) {
                echo $rendered_section;
            }
            ?>
		</div>
	</div>
</section>


<?php if (REQ == 'PAGE' or ee('LivePreview')->hasEntryData()): ?>
	<script>
	!function() {
		"use strict";

		var wrap = document.querySelector('.ee-debugger .tab-wrap');
		var tabs = wrap.querySelectorAll('.tab-bar__tab');
		var sheets = wrap.querySelectorAll('.tab');

		var removeClassFromAll = function(list, klass) {
			for (var i = 0; i < list.length; i++) {
				list[i].classList.remove(klass);
			}
		}

		var handleTabClick = function(evt) {
			evt.preventDefault();

			removeClassFromAll(tabs, 'active');
			removeClassFromAll(sheets, 'tab-open');

			var tab = this;
			var sheet = wrap.querySelector('.tab.' + this.getAttribute('rel'));

			tab.classList.add('active');
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

<script src="<?=URL_THEMES?>debug/javascript/highlight.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.ee-debugger pre code').forEach(function (block) {
		hljs.highlightBlock(block);
	});
});
</script>
