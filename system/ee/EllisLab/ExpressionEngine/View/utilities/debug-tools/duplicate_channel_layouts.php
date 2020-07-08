<div class="box">
    <h1>Duplicate Channel Layout Tabs:</h1>
    <div class="md-wrap">
        <ul class="checklist">
            We found <?= $duplicate_tabs_count ?> duplicate channel layout tabs. Woohoo!
            <?php if ($duplicate_tabs_count): ?>
                <br><br><a href='<?= $flux->moduleUrl('removeDuplicateTabs') ?>'>Click here to remove duplicate channel layout tabs</a>
            <?php endif; ?>
        </ul>
    </div>
</div>

<br>

<?php
if ($duplicate_tabs_count) {
    echo $this->embed('ee:_shared/table', $table);
    echo $pagination;
}
