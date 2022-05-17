<div class="f_metadata-section">
    <?php foreach ($data as $key => $value) : ?>
        <p class="f_metadata-item">
            <span class="f_meta-name"><?=lang($key)?></span>
            <span class="f_meta-info"><?=$value?></span>
        </p>
    <?php endforeach; ?>
</div>
