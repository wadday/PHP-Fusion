<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: contact.php
| Author: Nick Jones (Digitanium)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
require_once "maincore.php";
require_once THEMES."templates/header.php";

include LOCALE.LOCALESET."contact.php";

add_to_title($locale['global_200'].$locale['400']);

$input = array(
	'mailname'	=> '',
	'email'		=> '',
	'subject'	=> '',
	'message'	=> '',
	'captcha_code' => '',
	);

if (isset($_POST['sendmessage'])) {

	foreach ($input as $key => $value) {
		if (isset($_POST[$key])) {
			// Subject needs 'special' treatment
			if ($key == 'subject') {
				$input['subject'] = substr(str_replace(array("\r", "\n", "@"), "", descript(stripslash(trim($_POST['subject'])))), 0, 128); // most unique in the entire CMS. keep.
				$input['subject'] = form_sanitizer($input['subject'], $input[$key], $key);
			// Others don't
			} else {
				$input[$key] = form_sanitizer($_POST[$key], $input[$key], $key);
			}
		// Input not posted, fallback to the default
		} else {
			$input[$key] = form_sanitizer($input[$key], $input[$key], $key);
		}
	}

	$_CAPTCHA_IS_VALID = FALSE;
	include INCLUDES."captchas/".$settings['captcha']."/captcha_check.php"; // Dynamics need to develop Captcha. Before that, use method 2.
	if ($_CAPTCHA_IS_VALID == FALSE) {
		$defender->stop();
		addNotice('warning', $locale['424']);
	}
	if (!defined('FUSION_NULL')) {
		require_once INCLUDES."sendmail_include.php";
		$template_result = dbquery("
			SELECT template_key, template_active, template_sender_name, template_sender_email
			FROM ".DB_EMAIL_TEMPLATES."
			WHERE template_key='CONTACT'
			LIMIT 1");
		if (dbrows($template_result)) {
			$template_data = dbarray($template_result);
			if ($template_data['template_active'] == "1") {
				if (!sendemail_template("CONTACT", $input['subject'], $input['message'], "", $template_data['template_sender_name'], "", $template_data['template_sender_email'], $input['mailname'], $input['email'])) {
					$defender->stop();
					addNotice('warning', $locale['425']);
				}
			} else {
				if (!sendemail($settings['siteusername'], $settings['siteemail'], $input['mailname'], $input['email'], $input['subject'], $input['message'])) {
					$defender->stop();
					addNotice('warning', $locale['425']);
				}
			}
		} else {
			if (!sendemail($settings['siteusername'], $settings['siteemail'], $input['mailname'], $input['email'], $input['subject'], $input['message'])) {
				$defender->stop();
				addNotice('warning', $locale['425']);
			}
		}
		opentable($locale['400']);
		echo "<div class='alert alert-success' style='text-align:center'><br />\n".$locale['440']."<br /><br />\n".$locale['441']."</div><br />\n";
		closetable();
	}
}
opentable($locale['400']);
echo $locale['401']."<br /><br />\n";
echo openform('contactform', 'post', FUSION_SELF, array('max_tokens' => 1));
echo "<div class='panel panel-default tbl-border'>\n";
echo "<div class='panel-body'>\n";

echo form_text('mailname', $locale['402'], $input['mailname'], array('required' => 1, 'error_text' => $locale['420'], 'max_length' => 64));
echo form_text('email', $locale['403'], $input['email'], array('required' => 1, 'error_text' => $locale['421'], 'type' => 'email', 'max_length' => 64));
echo form_text('subject', $locale['404'], $input['subject'], array('required' => 1, 'error_text' => $locale['422'], 'max_length' => 64));
// TODO: Add character count
echo form_textarea('message', $locale['405'], $input['message'], array('required' => 1, 'error_text' => $locale['423'], 'max_length' => 128));

echo "<div class='panel panel-default tbl-border'>\n";
echo "<div class='panel-body clearfix'>\n";
echo "<div class='row m-0'>\n<div class='col-xs-12 col-sm-12 col-md-6 col-lg-6 p-b-20'>\n";
include INCLUDES."captchas/".$settings['captcha']."/captcha_display.php";
echo "</div>\n<div class='col-xs-12 col-sm-12 col-md-6 col-lg-6'>\n";
if (!isset($_CAPTCHA_HIDE_INPUT) || (isset($_CAPTCHA_HIDE_INPUT) && !$_CAPTCHA_HIDE_INPUT)) {
	echo form_text('captcha_code', $locale['408'], '', array('required' => 1, 'autocomplete_off' => 1));
}
echo "</div>\n</div>\n";
echo "</div>\n</div>\n";
echo form_button('sendmessage', $locale['406'], $locale['406'], array('class' => 'btn-primary m-t-10'));
echo "</div>\n</div>\n";
echo closeform();
closetable();
require_once THEMES."templates/footer.php";
