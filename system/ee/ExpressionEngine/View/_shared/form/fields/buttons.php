<?php
$class = isset($class) ? $class : '';

if (count($choices) == 0) {
    if (isset($no_results)) : ?>
        <div data-input-value="<?=$field_name?>" class="<?=$class?>">
            <?=$this->make('ee:_shared/form/no_results')->render($no_results)?>
        </div>
    <?php endif;

    return;
};

$encode = isset($encode) ? $encode : true;
$disabled_choices = isset($disabled_choices) ? $disabled_choices : [];
$allow_multi = $multi ? 'multiple' : '';

?>

<div class="button-group <?=$class?> <?=$allow_multi?>">
    <input type="hidden" name="<?=str_replace('[]', '', $field_name)?>" />
    <?php 
    if (!$multi && is_array($value)) {
        $value = end($value);
    }
    foreach ($choices as $key => $choice) :
        $label = isset($choice['label'])
            ? lang($choice['label']) : lang($choice);
        $key = isset($choice['value'])
            ? $choice['value'] : $key;
        if ($encode) {
            $label = ee('Format')->make('Text', $label)->convertToEntities();
        }
        $checked = ((is_bool($value) && get_bool_from_string($key) === $value)
            or (is_array($value) && in_array($key, $value))
            or (! is_bool($value) && $key == $value));
        $disabled = in_array($key, $disabled_choices) ? ' disabled' : ''; ?>

        <label class="button button--default<?=($checked) ? ' active' : '' ?>">
            <input class="hidden" type="checkbox" name="<?=$field_name?>" value="<?=htmlentities($key, ENT_QUOTES, 'UTF-8')?>"<?php if ($checked) :?> checked="checked"<?php endif; ?><?=isset($attrs) ? $attrs : ''?><?=$disabled?>>
            <div class="checkbox-label__text">
            <?=$label?>
            </div>
        </label>
    <?php endforeach; ?>
</div>
