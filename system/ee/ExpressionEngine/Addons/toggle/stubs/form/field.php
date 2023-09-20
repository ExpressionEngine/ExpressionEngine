<fieldset>
    <input type="radio" name="<?=$field_name?>" value="1" <?=($value == 1 ? ' checked' : '')?> id="<?=$field_name?>__on">
    <label for="<?=$field_name?>__on">On</label>
</fieldset>
<fieldset>
    <input type="radio" name="<?=$field_name?>" value="0" <?=($value == 0 ? ' checked' : '')?> id="<?=$field_name?>__off">
    <label for="<?=$field_name?>__off">Off</label>
</fieldset>