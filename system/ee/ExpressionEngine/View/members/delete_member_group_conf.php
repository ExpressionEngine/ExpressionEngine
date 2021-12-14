<?php if ($members_count_primary > 0 || $members_count_secondary > 0) :?>
    <div class="field-instruct"><em><?=sprintf(lang('member_assignment_warning'), $members_count_primary, $members_count_secondary)?></em></div>
    <?php if ($members_count_primary > 0) : ?>
    <p><?=lang('member_reassignment_warning')?></p>
    <p><?=form_dropdown('replacement', $new_roles, ee()->config->item('default_primary_role'))?></p>
    <?php endif;?>
<?php endif;?>
