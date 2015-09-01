<?php $this->extend('_templates/wrapper'); ?>

<div class="col-group snap">
	<div class="col w-16 last">
		<div class="box">
			<h1>404: Item does not exist.</h1>
			<div class="txt-wrap">
				<p>Sorry, we could not find the item you are trying to access in the system.</p>
				<?php if (trim($url)): ?>
					<p><b>URL:</b> <?=$url?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
