<style>
.ee2 {
    margin-bottom: 15px;
    padding: 10px 10px 1px 10px;
    }
.ee2 .tbl-search {
    float: right;
    display: inline;
    border: 0;
    position: relative;
    z-index: 10;
    }
.ee2 h1 {
    color: gray;
    font-size: 18px;
    font-weight: 400;
    padding: 10px;
    position: relative;
    border-bottom: 1px solid #e8e8e8;
    background-image: -moz-linear-gradient(top,#fff,#f9f9f9);
    background-image: -webkit-linear-gradient(top,#fff,#f9f9f9);
    background-image: linear-gradient(to right,top,#fff,#f9f9f9);
    }
.ee2 fieldset.col-group {
    border: 0;
    border-bottom: 1px solid #e3e3e3;
    }
.ee2 .col-group .col {
    float: left;
    display: inline;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
    }
.ee2 .col-group .col.w-8 {
    width: 50%;
    }
.ee2 .col-group {
    background-color: #fff;
    }
.ee2 .setting-txt h3 {
    font-size: 14px !important;
    }
.ee2 .setting-txt em {
    color: gray;
    font-size: 12px;
    font-style: normal;
    margin: 5px 0;
    padding-right: 10px;
    display: block;
    }
.ee2 fieldset.col-group:last-of-type {
    margin-bottom: 20px !important;
    }
.ee2 .txt-wrap {
    padding: 10px;
    border: 1px solid #e3e3e3;
    border-top: 0;
    background-color: #fff;
    }
.ee2 .form-submit {
    border: 0 !important;
    }
.license_status {
    margin: 15px 0;
    padding: 10px;
    line-height: 18px;
    background-color: #f5f5f5;
    border: 1px solid #e8e8e8;
    }
.license_status h4 {
    font-weight: bold;
    }
.license_status_badge {
    margin-bottom: 10px;
    }
.license_status_warning {
    margin-top: 15px;
    padding: 10px;
    color: #e0251c;
    text-align: center;
    border: 1px solid #f59792;
    background-color: #fcf5f5;
    }
.license_status_disabled .license_status {
    border: 2px solid #ff0000;
    background-color: #fcf5f5;
    }
.license_status_disabled .license_status ol {
    margin: 0 0 0 35px !important;
    }
</style>
<!-- <div class="tbl-ctrls"> -->

<div class="box ee<?=$ee_ver?> addon-license">
    <h1>License</h1>

    <?php echo form_open($action_url, array('class'=>'settings')); ?>

<?php if ($ee_ver > 2) { ?>
    <div class="app-notice-wrap"><?php echo ee('CP/Alert')->getAllInlines(); ?></div>
<?php } ?>
    <fieldset class="col-group required">
        <div class="setting-txt col w-8">
            <h3>License Key</h3>
            <em>You can retrieve your license key from <b><a href="https://eeharbor.com/members">your Account page on EEHarbor.com</a></b>.</em>
        </div>
        <div class="setting-field col w-8 last">
            <?php echo form_input('license_key', $license_key); ?>
        </div>
    </fieldset>

    <fieldset class="col-group license-status-group">
        <div class="setting-txt col w-8">
            <h3>License Status</h3>
        </div>
        <div class="setting-txt col w-8 last">
            <div class="license_status_badge"></div>

            <div class="license_status_i" style="display:none;">
                <div class="license_status_warning">
                    This add-on will cease to function if put on a production website!
                </div>
                <div class="license_status">
                    <h4>Invalid</h4>
                    We were unable to find a match for your License Key in our system. You can use the add-on while performing
                    <strong>local development</strong> but you <strong>must</strong> enter a valid license before making your
                    site live. To purchase a license or look up an existing license, please visit
                    <a href="https://eeharbor.com/">EEHarbor.com</a>.
                </div>
            </div>
            <div class="license_status_u" style="display:none;">
                <div class="license_status_warning">
                    This add-on will cease to function if put on a production website!
                </div>
                <div class="license_status">
                    <h4>Unlicensed</h4>
                    You have not entered a license key. You can use the add-on while performing <strong>local development</strong>
                    but you <strong>must</strong> enter a valid license before making your site live. To purchase a license or look
                    up an existing license, please visit
                    <a href="https://eeharbor.com/">EEHarbor.com</a>.
                </div>
            </div>
            <div class="license_status license_status_e" style="display:none;">
                <h4>Expired</h4>
                Your license is valid but has expired. You can continue to use you add-on while it is expired but if you wish to update
                to the latest version, you will need to purchase an upgrade. To upgrade, please login to your account on
                <a href="https://eeharbor.com/">EEHarbor.com</a>, find your license and click the "Renew" button.
            </div>
            <div class="license_status license_status_d" style="display:none;">
                <h4>Duplicate</h4>
                Your license key is currently registered on another website. For more information, please login to your account on
                <a href="https://eeharbor.com/">EEHarbor.com</a>.
            </div>
            <div class="license_status_w" style="display:none;">
                <div class="license_status_warning">
                    This add-on will cease to function if put on a production website!
                </div>
                <div class="license_status">
                    <h4>License Mismatch</h4>
                    The license key you entered is registered to a different add-on. For more information, please login to your account
                    on <a href="https://eeharbor.com/">EEHarbor.com</a>.
                </div>
            </div>
            <div class="license_status_p" style="display:none;">
                <div class="license_status_warning">
                    This add-on will cease to function if put on a production website!
                </div>
                <div class="license_status">
                    <h4>License Missing Production Domain</h4>
                    <p>You must enter your production domain in your Account page on EEHarbor.com.</p>
                    <p>This would be the final domain the add-on is going to run on (i.e. http://www.clientsite.com).</p>
                    <p>For more information, please login to your account on <a href="https://eeharbor.com/">EEHarbor.com</a>.</p>
                </div>
            </div>
            <div class="license_status_m" style="display:none;">
                <div class="license_status">
                    <h4>Maintenance Mode</h4>
                    The licensing server is undergoing maintenance. Your add-on will not be affected by this.
                    If you need assistance, please contact us on <a href="https://eeharbor.com/">EEHarbor.com</a>.
                </div>
            </div>
            <div class="license_status_disabled" style="display:none;">
                <div class="license_status">
                    <h4>Add-on Disabled</h4>
                    <p>This add-on has been disabled until a valid license is entered.</p>
                    <p>The unlicensed use of this add-on on production websites is a violation of the Add-on License Agreement.</p>
                    <p>
                        <b>To renable this add-on:</b><br />
                        <ol>
                            <li>Enter a valid license</li>
                            <li>Enter your production domain for this license on your account page on EEHarbor.com</li>
                        </ol>
                    </p>

                    <p>If you need assistance, please contact us on <a href="https://eeharbor.com/">EEHarbor.com</a>.</p>
                </div>
            </div>
        </div>
    </fieldset>

    <fieldset class="col-group last">
        <div class="setting-txt col w-8">
            <h3>License Agreement</h3>
        </div>
        <div class="setting-txt col w-8 last">
            <em>By using this software, you agree to the <b><a href="https://eeharbor.com/license">Add-on License Agreement</a></b>.</em>
        </div>
    </fieldset>

    <fieldset class="form-submit form-ctrls">
        <input class="btn submit" type="submit" value="Save License Key" />
    </fieldset>

    <?php echo form_close(); ?>
</div>