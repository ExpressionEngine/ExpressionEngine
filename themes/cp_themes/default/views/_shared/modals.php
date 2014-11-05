<?php foreach($modals as $key => $value): ?>
<div class="modal-wrap <?=$key?>">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="box">
					<?=$value?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endforeach; ?>