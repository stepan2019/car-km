<?php
/*
	*	
	* OxyClassifieds.com : PHP Classifieds (http://www.oxyclassifieds.com)
	* version 9
	* (c) 2017 OxyClassifieds.com (office@oxyclassifieds.com).
	*
*/

// ---------------------- ADMIN PANEL -------------------
$lng['settings']['analytics_code'] = 'Google Analytics tracking code';

// ----------------- locations --------------------
$lng['settings']['locations'] = 'Location filter';
$lng['settings']['enable_locations'] = 'Enable location filter';
$lng['settings']['info']['enable_locations'] = 'Allows you to configure one or more fields that can be chosen and will be <br/>
remembered as current location. All ads displayed will reflect that chosen <br/>
location every time you open the browser, until you change it again';
$lng['settings']['location_fields'] = 'Location fields';
$lng['settings']['info']['location_fields'] = 'Choose the fields you want to represent location. Ex: country, region, city';
$lng['settings']['enable_subdomains'] = 'Enable location subdomains';
$lng['settings']['info']['enable_subdomains'] = 'Allows you to configure one field to be used as a subdomain every time you select it as a location.<br/> Ex: http://paris.yoursite.com will display all ads from Paris.<br/> You are also required to choose the Subdomain <i>field</i> below.<br/>';
$lng['settings']['important']['enable_subdomains'] = '<b>Important!</b> Enabling this option requires extra configurations on your server. Please read carefully<br/> the documentation before enabling this option!';

$lng['settings']['subdomain_field'] = 'Subdomain field';
$lng['settings']['info']['subdomain_field'] = 'Choose the field to be used as subdomain. Ex: city.<br/>';
$lng['settings']['important']['search_location_fields'] = '<b>Important!</b> Choose only fields related to location like <i>country</i>, <i>region</i>, <i>city</i>.<br/> Do not choose fields like <i>title</i> or <i>description</i> that don\'t relate to location.';

// --------------- 7.06 --------------------
$lng['bulk_emails']['test'] = 'Send test email';
$lng['bulk_emails']['send'] = 'Send';
$lng['bulk_emails']['subject_missing'] = 'Please enter a subject!';
$lng['bulk_emails']['content_missing'] = 'Please enter a content for your email!';
$lng['bulk_emails']['test_mail_sent'] = 'The test email was sent to your administrator email address!';
$lng['order_history']['credits_purchase'] = 'Credits purchase';
$lng['credits']['credits'] = 'Credits';
$lng['credits']['enable_credits'] = 'Enable payment with credits';
$lng['credits']['one_credit_equals'] = 'One credit equals';
$lng['credits']['enable_for_groups'] = 'Enable for groups';
$lng['credits']['no_credit_packages'] = 'No credit packages';
$lng['credits']['create_package'] = 'Add credit package';
$lng['credits']['edit_package'] = 'Edit credit package';
$lng['credits']['no_credits'] = 'Number of credits';
$lng['credits']['confirm_delete'] = 'Are you sure you want to delete the credits package?';

$lng['credits']['errors']['pkg_name_missing'] = 'Please insert a name for the package!';
$lng['credits']['errors']['invalid_price'] = 'Invalid price!';
$lng['credits']['errors']['invalid_no_credits'] = 'Invalid credits number!';
// --------------- end 7.06 --------------------

// --------------- 7.07 --------------------
$lng_ratings['user'] = 'Placed by';

// --------------- 7.08 --------------------
$lng['settings']['mobile_settings'] = 'Mobile settings';
$lng['settings']['enable_mobile_templates'] = 'Enable mobile templates';
$lng['settings']['enable_mobile_subdomain'] = 'Enable mobile subdomain';
$lng['settings']['info1']['enable_mobile_subdomain'] = 'When browsing with a mobile device the site will redirect to the <b>m</b> subdomain <i>Eg: http://<b>m</b>.yourdomain.com</i>.';
$lng['settings']['info2']['enable_mobile_subdomain'] = '<b>Important!</b> Before enabling this option make sure you add the subdomain <b>m</b> for your domain<br/> and make it point to the same folder as the main domain. Please see the documentation for more details!';

$lng['settings']['date_time_ago_format'] = 'Enable <i>Time ago</i> format for date';
$lng['settings']['info']['date_time_ago_format'] = 'Show date in the format <i>X hours ago, X days ago</i>.';
$lng['settings']['date_time_ago_days'] = 'For ads not more than ';
$lng['settings']['days_old'] = ' days old ( 0 = unlimited )';
$lng['templates']['full_size_templates'] = 'Full size templates';
$lng['templates']['mobile_templates'] = 'Mobile templates';


// --------------- 8.01 --------------------
$lng['tools']['full_size_images'] = 'Full size images';
$lng['tools']['mobile_images'] = 'Mobile images';


// payfast
$lng['settings']['payfast_merchant_id'] = "Merchant Id";
$lng['settings']['payfast_merchant_key'] = "Merchant key";
$lng['settings']['payfast_item_name'] = "Item name";
$lng['settings']['payfast_demo'] = "Test mode";

$lng['settings']['errors']['required_payfast_merchant_id'] = "Please fill in the PayFast merchant id!";
$lng['settings']['errors']['required_payfast_merchant_key'] = "Please fill in the PayFast merchant key!";
$lng['settings']['errors']['required_payfast_item_name'] = "Please fill in the PayFast item name!";

$lng['settings']['hipay_member_account'] = 'Member account';
$lng['settings']['hipay_merchant_password'] = 'Merchant password';
$lng['settings']['hipay_website_id'] = 'Website ID';
$lng['settings']['hipay_notification_email'] = 'Notification email';
$lng['settings']['hipay_category'] = 'Category';
$lng['settings']['hipay_locale'] = 'Locale';
$lng['settings']['hipay_currency'] = 'Currency';

$lng['credits']['credits_packages'] = 'Credits packages';


$lng['fields']['title'] = 'Title';
$lng['fields']['description'] = 'Description';
$lng['fields']['currency'] = 'Currency';


$lng['settings']['enable_recaptcha'] = 'Use Recaptcha for image verification';
$lng['settings']['recaptcha_public_key'] = 'Recaptcha Public Key';
$lng['settings']['recaptcha_private_key'] = 'Recaptcha Private Key';
$lng['settings']['info']['enable_recaptcha'] = 'Enable Google Recaptcha image verification. Requires Public Key generated.';
$lng['settings']['info']['recaptcha_key'] = 'Create the keys using this link: <a href="https://www.google.com/recaptcha/admin/create" target="_blank">https://www.google.com/recaptcha/admin/create</a>';
$lng['settings']['errors']['recaptcha_enter_keys'] = 'Please enter public and private keys for ReCaptcha!';

$lng['custom_fields']['minimum_selections'] = 'Minimum elements selected';
$lng['custom_fields']['maximum_selections'] = 'Maximum elements selected';


$lng['settings']['contact_messages_pending'] = 'Messages wait admin approval';
$lng['settings']['info']['contact_messages_pending'] = 'Emails sent to listing owners using the contact form will not be sent immediately  but wait for administrator approval in Users / Messages section. ';
$lng['settings']['request_removal'] = 'Request account removal';
$lng['messages']['send_message'] = 'Approve pending message';
$lng['messages']['send_all_messages'] = 'Send all selected message';
$lng['messages']['confirm_send_all'] = 'Are you sure you want to send all selected messages?';
$lng['stats']['pending_messages'] = 'Pending messages';

$lng['languages']['direction'] = 'Text direction';
$lng['languages']['ltr'] = 'Left to Right';
$lng['languages']['rtl'] = 'Right to Left';


$lng['crt_mod'] = 'You are currently logged in as';
$lng['panel']['moderators_list'] = 'Moderators';
$lng['users']['moderator'] = 'Moderator';
$lng['users']['info']['moderator'] = 'If enabled, this user will have moderator rights. Select below the sections you want to allow rights to and type of actions you want to allow.';
$lng['general']['modify'] = 'Modify';

$lng['settings']['paypal_lc'] = 'Paypal Language Code';


$lng['ie']['scheduled_imports'] = 'Scheduled imports';
$lng['ie']['create_scheduled_import'] = 'Create scheduled import';
$lng['ie']['edit_scheduled_import'] = 'Edit scheduled import';
$lng['ie']['access_type'] = 'Import from';
$lng['ie']['url'] = 'File URL';
$lng['ie']['ftp'] = 'FTP';
$lng['ie']['ftp_server'] = 'FTP server';
$lng['ie']['ftp_login'] = 'FTP login';
$lng['ie']['ftp_password'] = 'FTP password';
$lng['ie']['ftp_filename'] = 'Filename';
$lng['ie']['use_id_as_unique_field'] = 'Use ID as unique field';
$lng['ie']['info']['use_id_as_unique_field'] = 'The <b>id</b> field will be used not as the internal id value of the ads, but as a unique id of ads that are imported. When ads are imported, if an ad with the same unique id already exists in the system, the ad will be modified instead of adding a new one.';
$lng['ie']['delete_inexisting'] = 'Delete inexisting ads';
$lng['ie']['info']['delete_inexisting'] = 'When <i>Use ID as unique field</i> option is selected, when an import is made, ads which are not contained in the current import (based on the unique id field) will be deleted.';
$lng['ie']['only_download_inexisting'] = 'Only download inexisting ads';
$lng['ie']['info']['only_download_inexisting'] = 'When <i>Use ID as unique field</i> option is selected, when an import is made, only ads which were not previously imported (based on the unique id value) are imported.';
$lng['ie']['default_category'] = 'Default category';
$lng['ie']['default_plan'] = 'Default plan';
$lng['ie']['name_missing'] = 'Please enter a name for the import!';
$lng['ie']['url_missing'] = 'Please enter the url of the import file!';
$lng['ie']['ftp_info_missing'] = 'Please enter FTP login information: server name, FTP login and password and path to the import file!';
$lng['ie']['user_missing'] = 'Please choose to which user account will ads be imported to.';

$lng['ie']['cron_config'] = 'Cron configuration command';

$lng['settings']['confirm_delete'] = 'Are you sure you want to delete the selected item?';
$lng['settings']['confirm_delete_all'] = 'Are you sure you want to delete the selected items?';


$lng['settings']['tax'] = 'Tax';
$lng['settings']['percent_tax'] = 'Percent tax';
$lng['settings']['fixed_tax'] = 'Fixed tax';


$lng['bcc_mails']['subject'] = 'Copy email: ';
$lng['bcc_mails']['from'] = 'From: ';
$lng['bcc_mails']['to'] = 'To: ';

$lng['alerts']['category'] = ' category';
$lng['settings']['contact_name_field'] = 'Contact name field';
$lng['settings']['change_contact_name_field'] = 'Change contact name field';
$lng['settings']['info']['change_contact_name_field'] = 'You are trying to delete or disable the field which is configured as a user contact  field. <br/>In order to do this, it you must first select a replacement field which will be used as contact name field for the users!';


$lng['settings']['random_priorities'] = 'Show priority ads randomly';
$lng['settings']['info']['random_priorities'] = 'In search pages don\'t show prioritized ads ordered by date, but instead show them randomly.  Prioritized ads will still show on top of other ads, but in a random order.';

$lng['settings']['affiliate_accounts'] = 'Affiliate accounts';
$lng['settings']['enable_affiliate_accounts'] = 'Enable affiliate accounts';
$lng['settings']['affiliates_cookie_availability'] = 'Affiliates cookie availability';
$lng['settings']['info']['affiliates_cookie_availability'] = 'When a guest reaches your site using an affiliate link, the affiliate id is remembered in a cookie.<br/> Any payment made while this cookie is active will add revenue to the affiliate. Set here for how<br/> many days you want this cookie to remain active.';

$lng['settings']['affiliates_percentage'] = 'Affiliate percentage';
$lng['settings']['info']['affiliates_percentage'] = 'The percentage from a sale which will be refered to the affiliate which brought the customer to your site .';

$lng['settings']['affiliates_payment_cycle'] = 'Affiliate payment cycle';
$lng['settings']['info']['affiliates_payment_cycle'] = 'The number of days until the payment towards an affiliate is made.';

$lng['packages']['info']['subscription_based'] = 'Pay once and post multiple ads for the price';
$lng['packages']['info']['ad_based'] = 'Pay for each ad';

$lng['users']['view_moderators'] = "Moderators";
$lng['users']['affiliates'] = "Affiliates";
$lng['users']['affiliate_paypal_email'] = 'Affiliate Paypal email';
$lng['affiliates']['id'] = 'Affiliate id';
$lng['affiliates']['link'] = 'Affiliate link';
$lng['affiliates']['revenues'] = 'Revenues';
$lng['affiliates']['payments_history'] = 'Payments';
$lng['affiliates']['released'] = 'Released';
$lng['affiliates']['not_released'] = 'Not released';
$lng['affiliates']['paid'] = 'Paid';
$lng['affiliates']['not_paid'] = 'Not paid';
$lng['affiliates']['paid_to'] = 'Paid to';
$lng['affiliates']['add'] = 'Add affiliate';
$lng['affiliates']['edit'] = 'Edit affiliate';
$lng['affiliates']['pay'] = 'Pay';
$lng['affiliates']['mark_paid'] = 'Mark paid';
$lng['affiliates']['unmark_paid'] = 'Unmark paid';
$lng['users']['affiliate_paypal_email'] = 'Affiliate PayPal account';
$lng['users']['errors']['affiliate_paypal_email'] = 'Please enter affiliate PayPal account!';


$lng['settings']['colorscheme'] = 'Colorscheme';
$lng['settings']['noindex'] = "Noindex, nofollow";
$lng['settings']['info']['noindex'] = "<b>Attention!</b> If you enable this, search engine crawlers will not index this page!";


// --------------- end 8.01 --------------------

// --------------- 8.2 --------------------

$lng['panel']['maintenance'] = 'Maintenance';
$lng['maintenance']['clear_cache'] = 'Clear cache';
$lng['maintenance']['clear_cache_now'] = 'Clear cache now!';
$lng['maintenance']['info']['cache_cleared'] = 'The cache was cleared!';

// --------------- end 8.2 --------------------


// --------------- 8.4 --------------------
$lng['panel']['main_page'] = 'Main page';
$lng['custom_fields']['edit_values'] = 'Edit Values';
$lng['banners']['title'] = 'Title';
$lng['settings']['pending_edited'] = 'Pending edited listings';
$lng['settings']['info']['pending_edited'] = 'After a listing is modified, the changes remain pending until are reviewed by administrator.&lt;br/&gt; This only applies to listings which are pending when posted!';
$lng['listings']['pending_edited'] = 'Pending edited';
$lng['listings']['pe'] = 'PE';
$lng['listings']['review_pending_edited'] = 'Review pending edited';
$lng['listings']['crt_value'] = 'Current value';
$lng['listings']['new_value'] = 'New value';
$lng['listings']['accept_changes'] = 'Accept changes';
$lng['listings']['deny_changes'] = 'Deny changes';
$lng['listings']['accepted'] = 'Accepted';
$lng['listings']['denied'] = 'Denied';


$lng['modules']['install'] = 'Install';


$lng['settings']['auctions'] = 'Auctions';
$lng['settings']['enable_auctions'] = 'Enable auctions';
$lng['settings']['auctions_groups'] = 'Groups allowed to create auctions';
$lng['settings']['notify_when_new_bid'] = 'Notify the owner when a new bid is placed';


$lng['auction']['auction'] = 'Auction';
$lng['auction']['auction_start_price'] = 'Start price';
$lng['auction']['no_bids'] = 'No bids';
$lng['auction']['max_bid'] = 'Max bid';
$lng['auction']['placed_on'] = 'Placed on';

// --------------- end  8.4 --------------------


// ---------------  8.5 --------------------
$lng['settings']['partial_search'] = 'Partial search ( search terms are considered strings, not words. Ex: the search <b>car</b> will also find ads containing the word <b>carpet</b> )';
$lng['users']['info_user'] = 'User info';
$lng['users']['edit_info'] = 'Edit user info';
$lng['users']['add_info'] = 'Add user info';

$lng['settings']['prioritize_featured'] = 'Prioritize Featured Ads';
$lng['settings']['info']['prioritize_featured'] = 'If this option is enabled, in search result pages, instead showing on top listings with Priority option, <br/>Featured ads will display first. IF YOU USE THIS FEATURE YOU SHOULD NOT USE PRIORITY RANKING!';

$lng['custom_fields']['phone'] = 'Phone';
$lng['custom_fields']['user_choice'] = 'User choice';
$lng['custom_fields']['info']['public'] = 'Select <b>Yes</b> for this field to be visible by all in the user profile, <b>No</b> to be visible only from administrator<br/> interface and <b>User choice</b> if you want to allow the user to choose if the field should be visible or not.';

$lng['panel']['security_settings'] = 'Security Settings';
$lng['panel']['failed_login_attempts'] = 'Limit login attempts';
$lng['settings']['block_admin_attempts'] = 'Block admin login attempts';
$lng['general']['after'] = 'after';
$lng['settings']['failed_attempts'] = 'failed attempts during 1 hour';
$lng['general']['hour_s'] = 'hour(s)';
$lng['settings']['block_user_attempts'] = 'Block user login attempts';

$lng['security']['blocked'] = 'Blocked';
$lng['security']['blocked_for'] = 'Blocked for (hours)';
$lng['security']['block_expires'] = 'Block expires';
$lng['security']['temporary'] = 'Temporary';
$lng['security']['permanent'] = 'Permanent';
$lng['panel']['blocked_ips'] = 'IP Blacklist / Whitelist';
$lng['panel']['ip_whitelist'] = 'IP Whitelist';
$lng['panel']['blocked_emails'] = 'Email Blacklist / Whitelist';

$lng['security']['add_to_whitelist'] = 'Add to whitelist';
$lng['security']['confirm_add_whitelist'] = 'Are you sure you want to add this element to the whitelist?';

$lng['settings']['security_cron_job'] = '<b>Imporant!!!</b> This feature needs an additional configuration in your server control panel: add a cron job at an interval of 1 hour with the command: <u>"php&nbsp;::CONFIG_ABS_PATH::/security_periodic.php"</u>'; // do not remove the ::CONFIG_ABS_PATH:: tag

$lng['security']['blocked_emails'] = 'Blocked Emails';
$lng['security']['email_whitelist'] = 'Email Whitelist';
$lng['security']['email_list'] = 'Email list';
$lng['general']['unblock'] = 'Unblock';

$lng['packages']['info']['allow_usage'] = 'One user will be able to use this listing plan for only this number of times.<br/> Once it has been used this many times it will no longer appear in the list of available plans.<br/> Use 0 if you want to allow it unlimited number of times.';

$lng['rss']['show_fields'] = 'Show fields';

$lng['settings']['small_img_header'] = 'Small size top logo';
$lng['settings']['info']['small_img_header'] = 'For small site widths like in case of browsing using a mobile or a small tablet, this is the logo which will be used on the top of your site.';


// --------------- end  8.5 --------------------

// --------------- 8.6 --------------------

$lng['panel']['news'] = 'News';
$lng['panel']['multicurrency'] = 'Multicurrency';
$lng['maintenance']['maintenance_mode'] = 'Maintenance mode';
$lng['maintenance']['info']['maintenance_mode'] = 'Put your site in <em>Maintenance mode</em> while you make an update to avoid your site looking broken until the update is complete.';
$lng['maintenance']['set_maintenance_mode'] = 'Set maintenance mode Now!';
$lng['maintenance']['remove_maintenance_mode'] = 'Set your site online Now!';
$lng['maintenance']['info']['site_in_maintenance_mode'] = 'Your site is Maintenance mode now! Don\'t forget to set it Online after you finish working!';
$lng['maintenance']['maintenance_ips'] = 'Maintenance IPs';
$lng['maintenance']['info']['maintenance_ips'] = 'Enter a list of IPs which will see the site online even when in maintenance mode.<br/> Enter your own IP address if you need to make changes to the site while in maintenance mode.';

$lng['maintenance']['info']['add_maintenance_ips'] = 'Add an IP address or a list of IP addresses separated by comma';


$lng['banners']['disable_unused'] = 'Disable unused positions';
$lng['banners']['disable_impressions_count'] = 'Disable impressions count';
$lng['banners']['enable_impressions_count'] = 'Enable impressions count';

$lng['rss']['logo_field'] = 'Logo_field';

// --------------- end 8.6 --------------------

// --------------- 8.7 --------------------

$lng['groups']['via_email'] = 'Via Email';
$lng['groups']['via_sms'] = 'Via SMS';
$lng['groups']['no_activation'] = 'No activation required';
$lng['groups']['info']['sms_activate_account'] = 'Accounts in this group will need to activate the account with a code received via SMS.<br/><em>Important!</em> This particular setting needs a SMS gateway enabled and a <em>Phone</em> type field mandatory for this user group and with <em>Validation and International format</em> option enabled.';
$lng['groups']['errors']['no_required_intl_phone'] = 'To enable SMS verification you need to have a required Phone type field in your User Custom Fields section for this particular user group, and enable <em>Validation and International format</em> option for this field!';
$lng['groups']['errors']['no_default_sms_or_not_configured'] = 'Please make sure you have chosen a default SMS gateway and configured its settings!';
$lng['panel']['sms_gateways'] = 'SMS gateways';
$lng['settings']['sms_gateway'] = 'SMS gateway';
$lng['settings']['confirm_set_default'] = 'Are you sure you want to set this as default SMS gateway?';
$lng['settings']['errors']['incomplete_smsg_settings'] = 'Incomplete settings for ::PROCESSOR::! Please configure it before setting it as default SMS gateway!';
$lng['settings']['api_id'] = 'API ID';
$lng['settings']['errors']['clickatell_api_id_missing'] = 'Please enter Clickatell API ID!';
$lng['messages']['edit_message'] = 'Edit message';
$lng['messages']['error']['add_message'] = 'Please enter a message content!';
$lng['messages']['approve_and_send'] = 'Approve and send';

$lng['nologin']['info']['activate_account'] = 'After placing a listing without a user account, the owner will need to activate the listing using a link sent via email.';
$lng['nologin']['info']['sms_activate_account'] = 'After placing a listing without a user account, the owner will need to activate the listing using a code received via SMS.<br/><em>Important!</em> This particular setting needs a SMS gateway enabled and a <em>Phone</em> type field mandatory for this user group <br/>and with <em>Validation and International format</em> option enabled.';
$lng['nologin']['errors']['no_required_intl_phone'] = 'To enable SMS verification you need to have a required Phone type field in your User Custom Fields section for "Not logged in guests only", and enable <em>Validation and International format</em> option for this field!';

$lng['rules']['info']['allowed'] = 'For one or more selected values for first field, allow only some values for second field.';
$lng['rules']['info']['required'] = 'For one or more selected values for first field, a value is required for the second field.';
$lng['rules']['info']['required_gr'] = 'Only allow selected values for users belonging to the chosen group.';

$lng['custom_fields']['unique'] = 'Unique';
$lng['custom_fields']['info']['unique'] = 'Don\'t accept duplicate values for this field.';
$lng['custom_fields']['unique_error_message'] = 'Unique field error message';
$lng['custom_fields']['errors']['unique_error_message_required'] = 'Please enter a Unique field error message!';

$lng['custom_fields']['international_format'] = 'Validation and International format';
$lng['custom_fields']['info']['international_format'] = 'Adds international prefixes and validation for international numbers to the field.';
$lng['custom_fields']['only_countries'] = 'Show prefixes only for countries';
$lng['custom_fields']['info']['only_countries'] = 'If you don\'t want all world countries prefixes to be used, enter a list of countries separated by comma.';
$lng['custom_fields']['info']['only_countries2'] = 'Add comma separated 2 letters country codes <a target="_blank" href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements">from HERE</a>';

// --------------- end 8.7 --------------------

// --------------- 8.8 --------------------

$lng['listings']['errors']['invalid_phone'] = 'Invalid phone number!';
$lng['categories']['info']['deactivated_ads_moved'] = 'The category was disabled and the ads moved to the selected category!';
$lng['sms_gateway']['failed_to_send'] = 'Verification SMS failed to be sent!';
$lng['sms_gateway']['successfully_sent'] = 'Verification SMS successfully sent!';
$lng['sms_gateway']['view_response'] = 'View response';
$lng['sms_gateway']['send_again'] = 'Send again';

// --------------- end 8.8 --------------------

// --------------- 8.9 --------------------
$lng['settings']['google_maps_api_key'] = 'Google maps API key';
$lng['settings']['info']['google_maps_api_key'] = 'You can generate an API key using this page: <a href="https://console.developers.google.com/flows/enableapi?apiid=maps_backend,geocoding_backend,directions_backend,distance_matrix_backend,elevation_backend,places_backend&keyType=CLIENT_SIDE&reusekey=true" target="_blank">https://console.developers.google.com</a>';
// --------------- end 8.9 --------------------


// --------------- 8.10 --------------------
$lng['settings']['payment_processors'] = 'Payment Processors';
$lng['settings']['invoice_settings'] = 'Invoice Settings';
$lng['settings']['enable_invoices'] = 'Enable invoices';
$lng['settings']['invoices'] = 'Invoices';
$lng['settings']['seller_details'] = 'Seller details';
$lng['settings']['info']['seller_details'] = 'Enter your business or personal name and address. Will appear in the left corner of the invoice.';
$lng['settings']['invoice_logo'] = 'Invoice logo';
$lng['settings']['info']['invoice_logo'] = 'The logo will appear in the top right side of the invoice';
$lng['settings']['invoice_user_fields'] = 'User fields';
$lng['settings']['info']['invoice_user_fields'] = 'Choose the user fields which will be used to place on the invoice the information about the buyer.';
$lng['settings']['custom_text'] = 'Custom text';
$lng['settings']['info']['custom_text'] = 'If you enter a text here it will appear at the lower side of the invoice.';
$lng['settings']['errors']['invoice_logo'] = 'Invoice logo: ';
$lng['settings']['invoice_filename'] = 'Invoice filename';
$lng['settings']['info']['invoice_filename'] = 'The invoice will be downloaded with this name, followed by invoice number and pdf extension.';

$lng['security']['blocked_phones'] = 'Blocked Phones';
$lng['security']['phone_whitelist'] = 'Phone Whitelist';
$lng['security']['phone_list'] = 'Phone list';
$lng['panel']['blocked_phones'] = 'Phone Blacklist/Whitelist';
$lng['users']['block'] = 'Block';
$lng['users']['block_unblock'] = 'Block / Unblock';
$lng['users']['block_email'] = 'Block email';
$lng['users']['info']['items_blocked'] = 'The selected items were blocked!';
$lng['users']['info']['items_unblocked'] = 'The selected items were unblocked!';
$lng['users']['info']['items_whitelisted'] = 'The selected items were whitelisted!';
$lng['users']['whitelisted'] = 'Whitelisted';
$lng['users']['whitelist'] = 'Whitelist';

$lng['invoice']['invoice'] = 'Invoice';
$lng['invoice']['invoice_no'] = 'Invoice no';
$lng['invoice']['bill_to'] = 'Bill to';
$lng['invoice']['qty'] = 'Qty';
$lng['invoice']['unit_price'] = 'Unit price';
$lng['invoice']['subtotal'] = 'Subtotal';
$lng['invoice']['sale_tax'] = 'Sale tax';
$lng['invoice']['new_listing'] = 'New listing';
$lng['invoice']['renew_listing'] = 'Listing renewal';
$lng['invoice']['featured_eo'] = 'Featured extra option for listing';
$lng['invoice']['highlited_eo'] = 'Highlited extra option for listing';
$lng['invoice']['priority_eo'] = 'Priority extra option for listing';
$lng['invoice']['video_eo'] = 'Video extra option for listing';
$lng['invoice']['new_credits_pkg'] = 'New credits plan purchase';
$lng['invoice']['store'] = 'Dealer page purchase';
$lng['invoice']['new_subscription'] = 'New subscription purchase';
$lng['invoice']['renew_subscription'] = 'Subscription renewal';

$lng['settings']['errors']['experttexting_api_key_missing'] = 'Please enter Experttexting API ID!';
$lng['settings']['errors']['experttexting_from_missing'] = 'Please enter Experttexting your number or sender id!';
$lng['settings']['experttexting_api_key'] = 'API Key';
$lng['settings']['experttexting_from'] = 'Your number or sender id';

// --------------- end 8.10 --------------------

// --------------- 9.1 --------------------

$lng['custom_pages']['visible'] = 'Visible by';
$lng['custom_pages']['info']['visible'] = 'You can choose to hide this page for all except logged in users from selected user groups.';
$lng['packages']['info']['ads_disabled'] = 'The ads posted with this listing plan were disabled!';
$lng['packages']['disable_all_ads'] = 'Disable all ads';
$lng['packages']['info']['disabled_and_ads_moved'] = 'The plan was disabled and the ads moved to the selected plan!';
$lng['custom_fields']['whatsapp'] = 'WhatsApp';
$lng['custom_fields']['section'] = 'Section';
$lng['seo_settings']['tags'] = 'Tags which can be used';

$lng['bulk_emails']['mails_sent'] = 'A total of ##NO_EMAILS## were attempted to be sent. ##SENT## emails were sent and ##FAILED## failed.';

$lng['custom_fields']['depending_required5'] = 'Field no 5 required';

$lng['settings']['search_by_account_type'] = 'Search by account type';
$lng['settings']['info']['search_by_account_type'] = 'User account types will appear above the search results and people will be able to select to only see results posted with that type of account';
$lng['settings']['medium_thumb_dimmensions'] = 'Medium thumbnail dimensions';
$lng['settings']['default_medium_image'] = 'Default medium image';
$lng['settings']['errors']['medium_nopic_image'] = 'Default medium image: ';
$lng['settings']['info']['default_medium_image'] = 'The image that will replace image medium thumbnail<br /> when there is no image uploaded with the ad.';
$lng['imgtools']['for_med_thumbnails'] = 'For medium thumbnails';
$lng['groups']['default_credits'] = 'Default credits';
$lng['groups']['info']['default_credits'] = 'When a user account for this group is created, it will be assigned this number of credits.';
$lng['listings']['enable_auction'] = 'Enable auction';

$lng['settings']['store_subdomain'] = 'Dealer page subdomain';
$lng['settings']['info']['store_subdomain'] = 'The dealer page will appear in the form http://dealer-name.domain.com.<br/>Make sure you use this feature only if you enable search engine friendly URLs!';

$lng['settings']['prof_groups'] = 'Professionals user groups';
$lng['settings']['info']['prof_groups'] = 'Groups which are considered professionals, the rest of users will be listed as private';
$lng['settings']['errors']['med_nopic_image'] = 'Default medium image: ';
$lng['stats']['last_available_bugfix'] = 'Last available bugfix';
// --------------- end 9.1 --------------------

// --------------- 9.2 --------------------
$lng['settings']['when_listing_posted'] = 'When listing is posted';
$lng['sitemap']['extra_entries'] = 'Extra entries';
$lng['sitemap']['info']['extra_entries'] = 'Add extra sitemap entries using the Google sitemap format.';
$lng['packages']['photo_mandatory'] = 'At least one photo mandatory';

$lng['settings']['secret_key'] = 'Secret key';
$lng['settings']['publishable_key'] = 'Publishable key';
$lng['settings']['errors']['required_secret_key'] = 'Please fill in the secret key!';
$lng['settings']['errors']['required_publishable_key'] = 'Please fill in the publishable key';
$lng['settings']['errors']['stripe_settings_missing'] = 'If you wish to enable Stripe payment, please edit Stripe settings';
$lng['custom_fields']['all'] = 'Add <em>All</em> value';
$lng['custom_fields']['info']['all'] = 'The value <em>All</em> will appear on top of selection list. Selecting this value will ensure that when searching for a particular<br/> value of that field, listings which have the value <em>All</em> selected will be always found.';

$lng_tag_cloud['split_tags'] = 'Split search expressions in multiple tags';

$lng['custom_fields']['errors']['dep_name5_name_missing'] = 'Please fill in fifth field name!';
$lng['custom_fields']['errors']['dep_field5_name_missing'] = 'Please fill in fifth field name!';
$lng['custom_fields']['errors']['dep_field5_error_missing'] = 'Please fill in error message for fifth field!';
$lng['listings']['error']['photo_mandatory'] = 'Please upload at least one picture with your ad!';

// --------------- end 9.2 --------------------

// --------------- 9.3 --------------------
$lng['settings']['enable_distance_search'] = 'Enable distance search';
$lng['settings']['info']['enable_distance_search'] = 'Allows the option to search on a distance around the selected location using the Quick search top page form.';
$lng['settings']['ds_measuring_unit'] = 'Distance search measuring unit';
$lng['settings']['ds_distances_list'] = 'Distances list';
$lng['settings']['info']['ds_distances_list'] = 'The list of distances which will appear to search around a location when <i>Distance search</i> option is enabled.';
$lng['settings']['enable_location_autosuggest'] = 'Enable location autosuggest';
$lng['settings']['info']['enable_location_autosuggest'] = 'Suggests locations when someone fills in the Location field of Quick search form using Google Geocoding Service.';
$lng['settings']['limit_location_autosuggest'] = 'Limit the suggest to country';
$lng['settings']['info']['limit_location_autosuggest'] = 'If configured limits location autosuggest to a selected country.';
$lng['settings']['info2']['limit_location_autosuggest'] = 'Use comma separated <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2" target="_blank">ISO 3166-1</a> Alpha-2 compatible 2 letter country codes.';

$lng['settings']['gm_api_lang'] = 'Language used for location autosuggest';
$lng['settings']['info']['gm_api_lang'] = 'It changes the language of the suggested places to the configured language. If nothing is configured, the browser language is used by default.';
$lng['settings']['info2']['gm_api_lang'] = 'Use 2 letter language codes for the languages supported for Google API: <br/><a href="https://developers.google.com/maps/faq#languagesupport" target="_blank">https://developers.google.com/maps/faq#languagesupport</a>.';


$lng['settings']['address_components'] = 'Location components';
$lng['settings']['info']['address_components'] = 'Assign each type of location components provided by Google Geolocation Service to your own fields. This way the search for a location formatted by Google will be able to show correct results.';
$lng['settings']['ac']['country'] = 'Country';
$lng['settings']['ac']['administrative_area_level_1'] = 'Administrative area level 1';
$lng['settings']['ac']['administrative_area_level_2'] = 'Administrative area level 2';
$lng['settings']['ac']['locality'] = 'City';
$lng['settings']['ac']['postal_code'] = 'Postal_code';
$lng['settings']['ac']['short_name'] = 'Short name';
$lng['settings']['ac']['long_name'] = 'Long name';
$lng['settings']['ac']['field'] = 'Field';
$lng['settings']['ac']['type'] = 'Type';

$lng['settings']['errors']['google_maps_field_required'] = 'To use <em>Distance search</em> function you need to have a <em>Google Maps</em> field added to <em>Settings / Listing Custom Fields</em> list, and that field needs to be mandatory!';
// --------------- end 9.3 --------------------


// --------------- 9.4 --------------------
$lng['banners']['open_new'] = 'Open in new window';
$lng['custom_fields']['twitter'] = 'Twitter';
$lng['seo_settings']['slug'] = 'URL Slug';
$lng['seo_settings']['info']['category_slug'] = 'The representation in the URLs of the category name';
$lng['seo_settings']['info']['custom_page_slug'] = 'The representation in the URLs of the custom page title';
$lng['seo_settings']['error']['slug_exists'] = 'The URL Slug already exists!';
$lng['seo_settings']['info']['slug_ok'] = 'URL Slug ok!';
$lng['seo_settings']['sef_legacy_mode'] = 'Search engine friendly URLs legacy mode';
$lng['seo_settings']['info']['sef_legacy_mode'] = 'Enable to use the old style URL structure';
$lng['seo_settings']['edit_links_structure'] = 'Edit links structure';
$lng['seo_settings']['error']['links_structure'] = 'Please fill in all fields for links structure!';
$lng['seo_settings']['regenerate_slugs'] = 'Regenerate listings and users URL Slugs!';
$lng['seo_settings']['info']['links_structure'] = 'If you made changes to the links structure, then click the link below to view and copy <br/>
		the new links structure. Replace the old .htaccess rules contained between <br/>
		"# start SEF links structure" and "# end SEF links structure" comments<br/>
		with the <a href="javascript:;" class="blue" id="show_current_links_structure">current links structure</a>.';

$lng['seo_settings']['maximum_slug_width'] = 'Maximum URL Slug width';
$lng['seo_settings']['info']['maximum_slug_width'] = 'The URL Slug is the URL formatted title or name used to compose search friendly URLs.<br/> Set here the maximum number of characters used for it.';
// --------------- end 9.4 --------------------

// --------------- 9.5 --------------------
$lng['seo_settings']['regenerate_category_slugs'] = 'Regenerate Slugs';
$lng['groups']['post_ads'] = 'Post ads';
$lng['groups']['info']['post_ads'] = 'Allow users registered to this group to post ads';
$lng['groups']['affiliates'] = 'Affiliate accounts';
$lng['groups']['info']['affiliates'] = 'Users registered with this group will be affiliate accounts';
$lng['custom_fields']['double_verification'] = 'Use double verification';
$lng['custom_fields']['info']['double_verification'] = 'Ask the user to enter the value 2 times, to avoid mistakes.';
$lng['custom_fields']['error']['double_verification'] = 'Please fill in ::FIELDNAME:: verification!';

$lng['users']['repeat'] = 'Repeat';
$lng['users']['errors']['emails_dont_match'] = 'Email addresses don\'t match!';
$lng['listings']['pending_bump'] = 'Pending bump';
$lng['listings']['bump'] = 'Bump listing';
$lng['listings']['bumped'] = 'Bumped';

// --------------- end 9.5 --------------------

// --------------- 9.6 --------------------

$lng['listings']['listings_with_auctions'] = 'Listings with auctions';
$lng['settings']['info']['enable_affiliate_accounts'] = 'You can mark user groups as Affiliates groups from Users / User Settings section.';
$lng['groups']['showcase'] = 'Showcase';
$lng['groups']['info']['showcase'] = 'Allow users belonging to this group to make a selection of listings which will appear on their user page or dealer page in a special box above all listings.';
$lng['custom_fields']['video'] = 'Video';
$lng['custom_fields']['audio'] = 'Audio';
$lng['users']['user_listings_page'] = 'User listings page';
$lng['invoice']['show_vat'] = 'Show included VAT';
$lng['invoice']['price_includes_vat'] = 'The total price includes';
$lng['invoice']['vat'] = 'VAT';
$lng['seo_settings']['regenerate_listing_slugs'] = 'Regenerate listing URL Slugs!';
$lng['seo_settings']['regenerate_user_slugs'] = 'Regenerate user URL Slugs!';
$lng['listings']['make_public'] = 'Make public';
$lng['general']['minutes'] = 'Minutes';
$lng['packages']['no_videos'] = 'Number of videos';

$lng['custom_fields']['country_autodetect'] = 'Country autodetect';
$lng['custom_fields']['info']['country_autodetect'] = 'Autodetect the current country and select the proper flag.';
$lng['settings']['count_refine_results'] = 'Count results in refine search';
$lng['settings']['info']['count_refine_results'] = 'Display the number of search results for each category and custom fields value in refine search box.';
// --------------- end 9.6 --------------------

// --------------- 9.7 --------------------
$lng['seo_settings']['when_no_listings'] = 'Only for pages with no search results';
$lng['settings']['send_using_admin_email'] = 'Send emails with administrator email as sender';
$lng['settings']['info']['send_using_admin_email'] = 'Some email servers will only allow sending messages which are sent from emails configured on that server.<br/> If that is your case, then by enabling this option, the emails will all be sent with the Administrator Email as sender.<br/> To assure the possibility of reaching back the right person, the user email will be used as the Reply-To field.';
$lng['settings']['encryption'] = 'Encryption';
$lng['settings']['info']['encryption'] = 'Make sure you check your server settings and configure the right option. Configuring a wrong option will result in mails not being sent!';
$lng['settings']['until_ad_expires'] = '(0 = until listing expires)';
$lng['packages']['urgent'] = 'Urgent';
$lng['packages']['info']['urgent'] = 'Listings from this plan are marked Urgent or not. Note that listings will remain marked Urgent until they expire.';
$lng['packages']['url'] = 'Website link';
$lng['packages']['info']['url'] = 'Listings from this plan allow a Website Link or not. Note that listings will have this option until they expire.';
$lng['settings']['enable_urgent'] = 'Enable Urgent';
$lng['settings']['info']['enable_urgent'] = 'Use Urgent Ads as an option for ad extra visibility. Urgent ads will be marked with an URGENT sign.';
$lng['settings']['urgent_expires'] = 'Urgent tag availability';
$lng['settings']['info']['urgent_expires'] = 'The time in days an ad will remain marked Urgent after the user purchased the Urgent option.<br> If you use 0, then the ad will remain marked Urgent until it expires.';
$lng['settings']['urgent_price'] = 'Urgent Price';
$lng['settings']['info']['urgent_price'] = 'The price the user will pay to mark a listing Urgent';
$lng['settings']['enable_url'] = 'Enable Website Link';
$lng['settings']['info']['enable_url'] = 'Allow adding a link towards a website in exchange for a fee. This URL will be visible in the listing details page.';
$lng['settings']['url_expires'] = 'Website Link availability';
$lng['settings']['info']['url_expires'] = 'The time in days the added link will be listed after the user purchased the Website Link option.<br> If you use 0, then link will remain active until the ad it expires.';
$lng['settings']['url_price'] = 'Website Link Price';
$lng['settings']['info']['url_price'] = 'The price the user will pay to add a Website Link to an ad';
$lng['listings']['urgent_listings'] = 'Urgent Listings';
$lng['listings']['url_listings'] = 'Website Link Listings';
$lng['listings']['site_url'] = 'Website URL';
$lng['listings']['make_urgent'] = 'Mark Urgent';
$lng['listings']['add_site_link'] = 'Add Website Link';
$lng['listings']['until_expires'] = 'Until expires';
$lng['stats']['urgent_listings'] = 'Urgent Listings';
$lng['stats']['url_listings'] = 'Website Link Listings';
$lng['listings']['error']['invalid_url'] = 'Invalid Website Link';
$lng['order_history']['urgent'] = 'Make ad Urgent';
$lng['order_history']['url'] = 'Website Link';
$lng['listings']['pending_urgent'] = 'Pending Urgent';
$lng['listings']['pending_url'] = 'Pending Website Link';
$lng['settings']['featured_plans'] = 'Featured Plans';
$lng['settings']['featured_plans_info'] = 'You can define multiple Featured ads plans differentiated by number of days and price.';
$lng['settings']['edit_featured_plan'] = 'Edit Featured Plan';
$lng['days'] = 'days';
$lng['packages']['no_featured'] = 'No Featured Plan';
$lng['settings']['example_image'] = 'Example image';
$lng['settings']['description'] = 'Description';
$lng['settings']['errors']['example'] = 'image example: ';
$lng['packages']['priorities'] = 'Priorities';
$lng['settings']['info']['option_description'] = 'The description which will appear next to this extra option to explain how it enhances the visibility.';
$lng['settings']['info']['option_example'] = 'An image which will demonstrate how this extra option will look on site.';
$lng['custom_fields']['logo'] = 'Logo';
// --------------- end 9.7 --------------------
?>
