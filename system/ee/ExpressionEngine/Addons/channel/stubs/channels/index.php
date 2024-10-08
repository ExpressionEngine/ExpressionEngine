{exp:channel:entries channel="<?=$channel?>" dynamic="no"}
    {title} - {path=<?=$template_group?>/entry/{url_title}}
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

{/exp:channel:entries}