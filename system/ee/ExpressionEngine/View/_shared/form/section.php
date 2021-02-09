<?php
// Tags an entire section with a group name, intended for hiding/showing via JS
$group = false;
if (isset($settings['group'])) {
    if (isset($settings['label'])) {
        $name = $settings['label'];
    }
    $group = $settings['group'];
    $settings = $settings['settings'];
}?>

<?php if ($name === 'fieldset_group'): ?>
	<div class="fieldset-group">
<?php elseif (is_string($name)): ?>
	<h2<?php if ($group): ?> data-section-group="<?=$group?>"<?php endif ?>><?=lang($name)?></h2>
<?php endif ?>
<?php
foreach ($settings as $setting) {
    $this->embed('ee:_shared/form/fieldset', array('setting' => $setting, 'group' => false));
}
?>
<?php if ($name === 'fieldset_group'): ?>
	</div>
<?php endif ?>
