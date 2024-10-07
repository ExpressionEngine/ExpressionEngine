<?php if($show_comments ?? false): ?>

{!-- Field Group: <?=$field_group?> --}
<?php endif; ?>

<?php foreach ($fields as $field) : ?>

    <?php if($show_comments ?? false): ?>

    {!-- Field: <?=$field['field_label']?> --}
    {!-- Fieldtype: <?=$field['field_type']?> --}
    {!-- Docs: <?=$field['docs_url']?> --}
    <?php endif; ?>

    <?=$this->embed($field['stub'], $field);?>

    <?php if($show_comments ?? false): ?>

    {!-- End field: <?=$field['field_label']?> --}
    <?php endif; ?>


<?php endforeach; ?>

<?php if($show_comments ?? false): ?>

{!-- End Field Group: <?=$field_group?> --}
<?php endif; ?>