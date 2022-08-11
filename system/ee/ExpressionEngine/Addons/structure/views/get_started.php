<div class="padder ee7 structure-gui">
    <div id="structure-ui">
        <div id="tree-header">
            <ul id="tree-controls">
                <li <?php if (!empty($valid_channels) && count($valid_channels) > 1) :
                    ?>class="tree-add"<?php
                endif; ?>><a href="<?=$add_page_url?>" class="pop" title="pop"><?=lang('ootb_add_first_page')?></a></li>
            </ul>
        </div> <!-- close #tree-header -->
        <div id="tree-ootb">
            <h1><?=lang('ootb_title')?></h1>
            <p><?=lang('ootb_intro')?>.</p>
            <ul>
                <li class="ootb-page-types">
                    <span class="ootb-intro"><strong><?=lang('ootb_title_page_types')?>:</strong> <?=lang('ootb_copy_page_types')?>.</span>
                    <span class="ootb-go"><a href="https://eeharbor.com/structure/documentation/page_types/" target="_blank"><?=lang('ootb_read_page_types')?> &rarr;</a></span>
                </li>
                <li class="ootb-settings">
                    <span class="ootb-intro"><strong><?=lang('ootb_title_channel_settings')?>:</strong> <?=lang('ootb_copy_channel_settings')?>.</span>
                    <span class="ootb-go"><a href="https://eeharbor.com/structure/documentation/channel_settings/" target="_blank"><?=lang('ootb_read_channel_settings')?> &rarr;</a></span>
                </li>
                <li class="ootb-settings">
                    <span class="ootb-intro"><strong><?=lang('ootb_title_module_settings')?>:</strong> <?=lang('ootb_copy_module_settings')?>.</span>
                    <span class="ootb-go"><a href="https://eeharbor.com/structure/documentation/access/" target="_blank"><?=lang('ootb_read_module_settings')?> &rarr;</a></span>
                </li>
                <li class="ootb-navigation-tags">
                    <span class="ootb-intro"><strong><?=lang('ootb_title_nav_tag')?>:</strong> <?=lang('ootb_copy_nav_tag')?>.</span>
                    <span class="ootb-go"><a href="https://eeharbor.com/structure/documentation/tags#tag-exp_structure_nav" target="_blank"><?=lang('ootb_read_nav_tag')?> &rarr;</a></span>
                </li>
            </ul>
        </div> <!-- close #tree-ootb -->
    </div> <!-- close #structure-ui -->
</div> <!-- close .padder -->