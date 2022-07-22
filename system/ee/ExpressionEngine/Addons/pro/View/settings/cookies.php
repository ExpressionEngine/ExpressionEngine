<?php $this->extend('ee:_templates/default-nav'); ?>

<?php foreach ($tables as $table) : ?>
<div class="panel">

    <div class="panel-heading">
        <div class="title-bar">
            <h3 class="title-bar__title"><?=$table['heading']?></h3>
        </div>
    </div>

    <?php $this->embed('ee:_shared/table', $table['table']); ?>

</div>
<?php endforeach; ?>
