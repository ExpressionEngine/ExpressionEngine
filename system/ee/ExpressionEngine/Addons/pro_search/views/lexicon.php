<div id="pro-lexicon" class="panel box">

    <h1 class="panel-heading"><?=lang('lexicon')?></h1>

    <div class="panel-body">

        <div class="pro-tabs col w-4" data-names="span">
            <div class="pro-tabs-pages">

                <div class="pro-tab<?php if ($total_words) :
                    ?> active<?php
                endif; ?>">
                    <span><?=lang('find_words')?></span>
                </div>
                <div class="pro-tab<?php if (! $total_words) :
                    ?> active<?php
                endif; ?>">
                    <span><?=lang('add_words')?></span>
                </div>

                <?=form_open()?>
                    <fieldset>
                        <input type="text" name="<?=$total_words ? 'find' : 'add'?>" placeholder="<?=lang('word_placeholder')?>" data-ajax-validate="no" autocomplete="off">
                        <select name="language">
                        <?php foreach ($languages as $val => $key) : ?>
                            <option value="<?=$key?>"<?php if ($key == $default) :
                                ?>selected<?php
                            endif; ?>>
                                <?=htmlspecialchars($val)?>
                                <!-- <?php if (isset($counts[$key])) :
                                    ?>&ndash; <?=number_format($counts[$key])?><?php
                                endif; ?>-->
                            </option>
                        <?php endforeach; ?>
                        </select>
                    </fieldset>
                </form>

            </div> <!-- .pro-tabs-pages -->
        </div> <!-- .pro-sidebar.pro-tabs -->

        <div class="col w-12 last">
            <div class="txt-wrap" style="padding: 10px;">
                <p class="pro-status"><?=$status?></p>
                <div class="pro-dynamic-content"></div>
            </div>
        </div>

    </div>

</div>
