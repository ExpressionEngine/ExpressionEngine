<?php

$lang = array(

/* ----------------------------------------
/*  Required for MODULES page
/* ----------------------------------------*/
'simple_commerce_module_name' =>
'Simple Commerce',

'simple_commerce_module_description' =>
'Simple, Flexible Commerce Module',

/*----------------------------------------*/
'simple_commerce_home' =>
'Control Panel',

'subscription_frequency' =>
'Subscription Frequency',

'subscription_frequency_unit' =>
'Subscription Frequency Unit',

'invalid_subscription_frequency' =>
'Invalid Subscription Frequency',

'recurs_every' =>
'Recurs every',

'days' =>
'Days',

'screen_name' =>
'Screen name',

'weeks' =>
'Weeks',

'months' =>
'Months',

'years' =>
'Years',

'day' =>
'Day',

'week' =>
'Week',

'month' =>
'Month',

'year' =>
'Year',

'ipn_url' =>
'Instant Payment Notification (IPN) URL',

'ipn_details' =>
'Instant Payment Notification is a PayPal service that allows the processing of
transactions on their site and then notifying ExpressionEngine when the transaction is complete
and accepted.  This allows purchases to not be stored and recorded in the Simple Commerce module
until the transaction is finalized.  ExpressionEngine\'s Simple Commerce module automates all of this
behind the scenes so all you have to do is activate Instant Payment Notification on the PayPal site
and provide them with the URL below.<br /><br />
For more details:  <a href="https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_admin_IPNIntro" rel="external">https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_admin_IPNIntro</a>',

'items' =>
'Items',

'purchases' =>
'Purchases',

'email_templates' =>
'Email Templates',

'store_items' =>
'Store Items',

'add_item' =>
'Add Item',

'item_added' =>
'Items Added',

'purchase_date' =>
'Purchase date',

'recurring' =>
'Recurring',

'recurring_purchase_warning' =>
"Recurring purchases must be set up through PayPal for full functionality.",

'current_subscriptions' =>
'Current Subscriptions',

'add_items' =>
'Add Items',

'edit_items' =>
'Edit Items',

'export_items' =>
'Export Items',

'store_purchases' =>
'Store Purchases',

'edit_purchases' =>
'Edit Purchases',

'add_purchase' =>
'Add Purchase',

'export_purchases' =>
'Export Purchases',

'choose_entry_for_item' =>
'Choose Entry for Store Item',

'invalid_entries' =>
'No Valid Entries',

'invalid_emails' =>
'No Valid Emails',

'invalid_purchases' =>
'No Valid Purchases',

'use_sale_price' => 
'Use Sale Price?',

'entry_title' =>
'Entry Title',

'update_item' =>
'Update Item',

'update_items' =>
'Update Items',

'sale_price' =>
'Sale Price',

'regular_price' =>
'Regular Price',

'item_enabled' =>
'Item Enabled',

'purchase_actions' =>
'Purchase Actions',

'store_emails' =>
'Store Emails',

'add_email_template' =>
'Add Email Template',

'template_name' =>
'Template Name',

'edit_email_templates' =>
'Edit Email Templates',

'sales_information' =>
'Sales Information',

'no_email_templates' =>
'No Email Templates',

'no_purchases' =>
'No Purchases',

'perform_item_actions' =>
'Perform Actions for Item?',

'email_name' =>
'Email Template Name',

'edit_emails' =>
'Edit Emails',

'monthly_sales' =>
'Monthly Sales',

'customer_email' =>
'Customer Email Template',

'on_subscribe' =>
'On Subscribe',

'on_unsubscribe' =>
'On Unsubscribe',

'customer_email_subscribe' =>
'Customer Email Subscribe Template',

'customer_email_unsubscribe' =>
'Customer Email Unsubscribe Template',

'admin_email_address' =>
'Admin Email Address',

'invalid_price' =>
'Invalid Price!',

'select_admin_template' =>
'Must Select Admin Email Template if Email Address is Filled Out',

'admin_email_template' =>
'Admin Email Template',

'member_group' =>
'New Member Group',

'admin_email_template_unsubscribe' =>
'Admin Email Unsubscribe Template',

'member_group_subscribe' =>
'Subscribe Member Group',

'member_group_unsubscribe' =>
'Unsubscribe Member Group',


'send_no_email' =>
'Do Not Send Email',

'no_change' =>
'No Change',

'no_store_items' =>
'No Store Items',

'item_purchases' =>
'Purchases',

'updated' =>
'Updated',

'choose_item' =>
'Choose Item',

'invalid_emails' =>
'Invalid Emails',

'invalid_purchases' =>
'Invalid Purchases',

'fields_left_blank' =>
'You Left Some Required Fields Blank',

'add_emails' =>
'Add Email Templates',

'add_email' =>
'Add Email Template',

'update_email' =>
'Update Email Template',

'update_purchase' =>
'Update Purchase',

'update_emails' =>
'Update Email Templates',

'update_purchases' =>
'Update Purchases',

'purchases_deleted' =>
'Purchases Deleted',

'email_instructions' =>
'Email Instructions',

'email_name' =>
'Email Template Name',

'email_subject' =>
'Email Subject',

'email_body' =>
'Email Body',

'add_email_instructions' =>
'When the purchase of an item is confirmed, an email can be sent out to one or many email addresses for that item.  These emails can contain certain variables sent back from PayPal, which are as follows: ',

'edit_selected' =>
'Edit Selected',

'delete_selected' =>
'Delete Selected',

'delete_items_confirm' =>
'Delete Items Confirmation',

'items_deleted' =>
'Items Deleted',

'delete_emails_confirm' =>
'Delete Emails Confirmation',

'delete_purchases_confirm' =>
'Delete Purchases Confirmation',

'emails_deleted' =>
'Email Templates Deleted!',

'member_not_found' =>
'Member Not Found',

'purchaser_screen_name' =>
'Purchaser\'s Screen Name',

'txn_id' =>
'Purchase Identification Number (TXN ID)',

'date_purchased' =>
'Date Purchased',

'subscription_end_date' =>
'Subscription End Date',

'subscription_end_date_subtext' =>
'Enter \'0\' for no subscription end',

'item_purchased' =>
'Item Purchased',

'item_cost' =>
'Item Cost',

'choose_item' =>
'Choose Item',

'invalid_date_formatting' =>
'Invalid Date Formatting',

'invalid_amount' =>
'Invalid Monetary Amount',

'encryption' =>
'Encryption',

'encrypt_buttons_links' =>
'Encrypt PayPal Buttons and Links?',

'public_certificate' =>
'Public Certificate Path',

'certificate_id' =>
'ID Given to Public Certificate by PayPal',

'private_key' =>
'Private Key Path',

'paypal_certificate' =>
'PayPal Certificate Path',

'temp_path' =>
'Temporary Encrypted Files Path',

'settings' =>
'Settings',

'settings_updated' =>
'Settings Updated',

'file_does_not_exist' =>
'File Does Not Exist for \'%pref%\'.  Make sure to use a full server path.',

'temporary_directory_unwritable' =>
'The Directory specified for writing the temporary files is not writable.',

'paypal_account' =>
'PayPal Account',

'no_entries_matching_that_criteria' => 
'There are no entries matching the criteria you selected',

''=>''
);

/* End of file simple_commerce_lang.php */
/* Location: ./system/expressionengine/language/english/simple_commerce_lang.php */
