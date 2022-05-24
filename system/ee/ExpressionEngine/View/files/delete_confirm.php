   <fieldset class="fieldset-invalid hidden">
        <div class="field-control">
            <em class="ee-form-error-message"><?=lang('confirmation_required')?></em>
        </div>
    </fieldset>

    <br />

    <?php
    if (isset($fieldset)) {
        $this->embed('ee:_shared/form/fieldset', $fieldset);
    }
    ?>
