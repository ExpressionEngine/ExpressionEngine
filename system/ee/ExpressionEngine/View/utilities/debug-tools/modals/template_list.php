<h1><?=lang('tag_inclusions')?></h1>

<div class="txt-wrap">

<?php foreach ($templates as $template) : ?>
    <p>
        <?=lang($template['type'])?>:
        <a href="<?=$template['link']?>"><?=$template['path']?></a>
    </p>
<?php endforeach; ?>
</div>
