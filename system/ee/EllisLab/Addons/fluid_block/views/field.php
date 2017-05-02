<div class="fluid-item" data-field-name="<?=$field->field_name?>" data-field-type="<?=$field->field_type?>">
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
			<?=$field->getField()->getForm()?>
		</div>
	</div>
</div>
