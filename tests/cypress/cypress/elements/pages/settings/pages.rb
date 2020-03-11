class PagesSettings < ControlPanelPage

  set_url 'admin.php?/cp/addons/settings/pages/settings'
  elements :homepage_display, 'input[name="homepage_display"]'
  elements :default_channel, 'input[name="default_channel"]'
  elements :channel_default_template, '.field-inputs select'

}
