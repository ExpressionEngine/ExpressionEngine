<div class="fluid-wrap" data-row-count="0">
	<div class="fluid-actions">
		<?=$filters?>
	</div>
	<div class="fluid-field-templates hidden">
		<?php foreach ($fields as $field): ?>
			<div class="fluid-item" data-field-name="<?=$field->field_name?>">
				<div class="fluid-ctrls">
					<span class="reorder"></span>
					<h3>
						<span class="ico sub-arrow"></span><?=$field->field_label?>
						<span class="faded">(<?=$field->field_type?>)</span>
					</h3>
					<a class="fluid-remove" href="" title=""></a>
					<?=$filters?>
				</div>
				<div class="fluid-field">
					<div class="field-instruct">
						<em><?=$field->field_instructions?></em>
					</div>
					<div class="field-control">
						<?php
							$f = $field->getField();
							$f->setName($field_name . '[rows][new_row_0][field_id_' . $field->getId() . '][]');
							echo $f->getForm();
						?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>