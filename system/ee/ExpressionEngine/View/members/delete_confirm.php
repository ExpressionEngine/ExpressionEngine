<?php if (isset($heirs)): ?>
	<p>
		<?php if (count($selected) == 1): ?>
			<?=lang('heir_to_member_entries')?>
		<?php elseif (count($selected) > 1): ?>
			<?=lang('heir_to_members_entries')?>
		<?php endif; ?>
	</p>
	<ul>
		<li><label class="notice"><?=form_radio('heir_action', 'delete', 'n')?> <?= lang('member_delete_dont_reassign_entries') ?></label></li>
		<li><label><?=form_radio('heir_action', 'assign', 'y')?> <?= lang('member_delete_reassign_entries')?> </label>
	</ul>

   <fieldset class="fieldset-invalid hidden">
        <div class="field-control">
            <em class="ee-form-error-message"><?=lang('heir_required')?></em>
        </div>
    </fieldset>

	<?php

    foreach ($fields as $field_name => $field) {
        $vars = array(
            'field_name' => $field_name,
            'field' => $field,
            'grid' => false
        );

        $this->embed('ee:_shared/form/field', $vars);
    }
    ?>
<?php endif; ?>
