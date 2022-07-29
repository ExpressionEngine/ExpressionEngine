<ul class="simple-list">
    <?php foreach($templates as $template): ?>
    <li>
        <a class="normal-link" href="<?=ee('CP/URL')->make('design/template/edit/' . $template->getId());?>">
            <?=$template->TemplateGroup->group_name?>/<?= $template->template_name; ?>
            <span class="meta-info float-right ml-s"><?= ee()->localize->format_date(ee()->session->userdata('date_format', ee()->config->item('date_format')), $template->edit_date)?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>
