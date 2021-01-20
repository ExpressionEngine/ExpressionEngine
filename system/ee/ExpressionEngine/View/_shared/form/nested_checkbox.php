<?php foreach ($choices as $key => $label):
    $children = null;

    // If the value is an array, then we have children. Add them to the
    // queue with depth markers and set the real value to render the parent.
    if (is_array($label)) {
        $children = $label['children'];
        $label = $label['name'];
    }

    if (is_array($value)) {
        $selected = in_array($key, $value);
    } else {
        $selected = ((string) $value == (string) $key);
    }

    $disabled = in_array($key, $disabled_choices);
?>
<li>
	<label class="choice block<?php if ($selected):?> chosen<?php endif ?>">
		<input type="checkbox" name="<?=$field_name?>" value="<?=$key?>"<?php if ($selected):?> checked="checked"<?php endif ?><?php if ($disabled):?> disabled="disabled"<?php endif ?><?=$attrs?>> <?=$label?>
	</label>
	<?php if (isset($children)): ?>
		<ul>
				<?php $this->embed('ee:_shared/form/nested_checkbox', array(
				    'field_name' => $field_name,
				    'attrs' => $attrs,
				    'choices' => $children,
				    'disabled_choices' => $disabled_choices,
				    'value' => $value,
				)); ?>
		</ul>
	<?php endif; ?>
</li>
<?php endforeach ?>
