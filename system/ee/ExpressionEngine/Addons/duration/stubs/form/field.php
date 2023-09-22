<?php
switch ($field_settings['units']) {
    case 'hours':
        $colonNotaionFormat =  lang('duration_ft_hh');
        break;
    case 'minutes':
        $colonNotaionFormat =  lang('duration_ft_hhmm');
        break;
    case 'seconds':
    default:
        $colonNotaionFormat =  lang('duration_ft_hhmmss');
        break;
};
$placeholder = sprintf(
    lang('duration_ft_placeholder'),
    lang('duration_ft_' . $field_settings['units']),
    $colonNotaionFormat
)
?>
<input type="text" name="<?=$field_name?>" value="{<?=$field_name?>}" maxlength="<?=$field_maxl?>" placeholder="<?=$placeholder?>">