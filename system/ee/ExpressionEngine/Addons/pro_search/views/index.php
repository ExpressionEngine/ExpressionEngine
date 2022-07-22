<div class="panel box home-layout pro-search-home">
    <h1 class="panel-heading"><?=lang('pro_search_module_name')?></h1>
    <div class="panel-body info">
        <p>
            <?=lang('pro_search_module_description')?>
            &mdash; v<?=$version?>
        </p>

        <?php if ($member_group == 1) : ?>
        <ul class="arrow-list">
            <!-- <li>
                <a href="https://docs.expressionengine.com">Documentation</a>
            </li> -->
            <li>
                <span>Open Search URL:</span>
                <code onclick="prompt('<?=lang('build_index_url')?>', this.innerText);"><?=$search_url?></code>
            </li>
            <li>
                <span><?=lang('build_index_url')?>:</span>
                <code onclick="prompt('<?=lang('build_index_url')?>', this.innerText);"><?=$build_url?></code>
            </li>
        </ul>
        <?php endif; ?>
    </div>
</div>
<style>
.arrow-list li:before {
    color: #eee;
    content: "\f061";
    font-family: FontAwesome;
    font-size: 12px;
    margin-right: 5px;
}
</style>
