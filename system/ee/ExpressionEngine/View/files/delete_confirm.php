<?php if (isset($fieldset) && !empty($fieldset)) : ?>
    <fieldset class="fieldset-invalid hidden">
        <div class="field-control">
            <em class="ee-form-error-message"><?=lang('confirmation_toggle_required')?></em>
        </div>
        <br />
    </fieldset>
<?php 
    $this->embed('ee:_shared/form/fieldset', $fieldset);
endif; 
?>