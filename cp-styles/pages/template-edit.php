<?php include(dirname(__FILE__) . '/_header.php'); ?>


<!-- <div class="jump-menu-container">
    <div class="jump-menu">
        <div class="jump-menu__input">
            <input type="text" placeholder="Go To..">
        </div>

    </div>
</div>

<div class="modal-overlay"></div> -->


<div class="main-nav">

</div>

<div class="cp-wrap">
    <div class="sidebar">
        <div class="sidebar__items">
            <a href=""><i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <!-- <a href="" class="selected"><div class="icon"><svg class="feather"><use xlink:href="../app/assets/images/feather-sprite.svg#file"/></svg></div> Entries</a>
            <a href=""><div class="icon"><svg class="feather"><use xlink:href="../app/assets/images/feather-sprite.svg#folder"/></svg></div> Files</a>
            <a href=""><div class="icon"><svg class="feather"><use xlink:href="../app/assets/images/feather-sprite.svg#users"/></svg></div> Members</a>
            <a href=""><div class="icon"><svg class="feather"><use xlink:href="../app/assets/images/feather-sprite.svg#tag"/></svg></div> Categories</a>
            <a href=""><div class="icon"><svg class="feather"><use xlink:href="../app/assets/images/feather-sprite.svg#zap"/></svg></div> Add-Ons</a> -->
        </div>
    </div>

    <div class="container">
        <!-- <h1>Edit Template: <code>home/index</code></h1> -->
        <button class="switch-theme-button" style="z-index: 2000;">Dark Mode</button>

        <form action="">

            <fieldset class="fieldset-required">
                <div class="field-instruct ">
                    <label>Template Name</label>
                    <em>No spaces. Underscores and dashes are allowed.</em>
                </div>
                <div class="field-control">
                    <input type="text" name="template_name" value="index" class="font-monospace">
                </div>
            </fieldset>


            <fieldset>
                <div class="field-instruct">
                    <label>Type</label>
                </div>

                <div class="field-control">
                    <div class="wrapper">
                        <input id="a11y-issue-1" name="a11y-issues" type="radio" value="no-issues" checked>
                        <label for="a11y-issue-1">Web Page (HTML)</label>
                    </div>

                    <div class="wrapper">
                        <input id="a11y-issue-2" name="a11y-issues" type="radio" value="no-focus-styles">
                        <label for="a11y-issue-2">JavaScript</label>
                    </div>

                    <div class="wrapper">
                        <input id="a11y-issue-3" name="a11y-issues" type="radio" value="html-markup">
                        <label for="a11y-issue-3">RSS Page</label>
                    </div>
                </div>
            </fieldset>


            <fieldset class="fieldset-security-caution">
                <div class="field-instruct ">
                    <label>Allow PHP?</label>
                    <em>When enabled, you can use standard PHP within this template. <a href="https://docs.expressionengine.com/v5/templates/php.html" rel="external">Read about the implications before enabling</a>.</em>
                </div>
                <div class="field-control">
                    <a href="#" class="toggle-btn yes_no off" data-toggle-for="allow_php" data-state="off" role="switch" aria-checked="false" alt="off">
                        <input type="hidden" name="allow_php" value="n">
                        <span class="slider"></span>
                        <span class="option"></span>
                    </a>
                </div>
            </fieldset>

            <fieldset class="">
                <div class="field-instruct ">
                    <label>Default base URL</label>
                    <em>Use <code>{base_url}</code> to build URLs in control panel URL fields.</em>
                </div>
                <div class="field-control">
                    <input type="text" name="base_url" value="http://alpha1.local/new-cp/" class="font-monospace">
                </div>
            </fieldset>

            <fieldset class="">
                <div class="field-instruct">
                    <label><span class="ico sub-arrow js-toggle-field"></span>Checkboxes</label>
                    <em>When enabled, there is potential possibility for notifications.</em>
                </div>
                <div class="field-control">
                    <div>
                        <div class="fields-select">
                        <div class="wrapper">
                                        <input id="tea" name="tea" type="checkbox" checked>
                                        <label for="tea">Email Notifications </label>
                                    </div>
                                    <div class="wrapper">
                                        <input id="tea1" name="tea1" type="checkbox" value="">
                                        <label for="tea1">Text Notifications </label>
                                    </div>
                            <!-- <ul class="field-inputs field-nested">
                                <li class="nestable-item" data-id="1">


                                </li>
                                <li class="nestable-item" data-id="2">

                                </li>
                            </ul> -->

                            <input type="hidden" name="field_id_1[]" value="1"><input type="hidden" name="field_id_1[]" value="2">
                        </div>
                    </div>
                </div>
            </fieldset>

        </form>
    </div>
</div>


<?php include(dirname(__FILE__) . '/_footer.php'); ?>
