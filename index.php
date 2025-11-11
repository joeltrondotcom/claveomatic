<?php
$nerd="
  ▗▄▄▖▗▖    ▗▄▖ ▗▖  ▗▖▗▄▄▄▖       ▗▄▖       ▗▖  ▗▖ ▗▄▖▗▄▄▄▖▗▄▄▄▖ ▗▄▄▖
 ▐▌   ▐▌   ▐▌ ▐▌▐▌  ▐▌▐▌         ▐▌ ▐▌      ▐▛▚▞▜▌▐▌ ▐▌ █    █  ▐▌   
 ▐▌   ▐▌   ▐▛▀▜▌▐▌  ▐▌▐▛▀▀▘  ▀▀▘ ▐▌ ▐▌  ▀▀▘ ▐▌  ▐▌▐▛▀▜▌ █    █  ▐▌   
 ▝▚▄▄▖▐▙▄▄▖▐▌ ▐▌ ▝▚▞▘ ▐▙▄▄▖      ▝▚▄▞▘      ▐▌  ▐▌▐▌ ▐▌ █  ▗▄█▄▖▝▚▄▄▖
                                                     ▄ ▄▖▄▖  ▄▖▄▖▖  ▖
  ▌      ▘    ▜ ▗                                    ▌▌▌▌▐   ▌ ▌▌▛▖▞▌
  ▛▌▌▌   ▌▛▌█▌▐ ▜▘▛▘▛▌▛▌                             ▙▘▙▌▐   ▙▖▙▌▌▝ ▌
  ▙▌▙▌   ▌▙▌▙▖▐▖▐▖▌ ▙▌▌▌
    ▄▌  ▙▌
";
/* 
// general
 - statim IP look up new cycles
 - help page for port forwarding statims

// bugs

// known bugs (not going to fix)
 - Photo search returns true if all photos are deleted
 - Empty accounts show "Showing 1-0 of 0"

*/

// includes
include_once('functions.inc.php');		// functions
include_once('db_creds.inc.php');		// secrets

// show source code
if(isset($_GET['source'])) {
	echo '<h1>'.$ui['name'].'</h1>';
	$files=array(
		'index.php',
		'ui.inc.php',
		'settings.inc.php',
		'functions.inc.php',
		'script.js',
		'database_strucutre.sql',
	);

	foreach($files as $file) {
		echo '<h2 style="border-bottom: 2px solid #000;">'.$file.'</h2>';
		show_source($file);
	}
	die;
}

// database
if(!$link = db_connect($sql_details))
	die('No Database... plz hold...');

// login
$logged_in=login($link);
include_once('settings.inc.php');		// config

// update settings
$settings=load_settings($link, $settings);

// starttttttt
ob_start();

// login check
if(isset($_GET['is_logged_in']))
	die(is_logged_in());

if($logged_in) {
	// logged in
	$autoclaves=get_autoclaves($link);

	// download
	if(isset($_GET['logs_download']))
		die(download_logs($link, $settings, (isset($_GET['file_type'])?$_GET['file_type']:$settings['download_default_format']),$autoclaves, (isset($_GET['search'])?$_GET['search']:array())));

	if(isset($_GET['log_download']) && $_GET['id'])
		die(download_logs($link, $settings, 'PDF', $autoclaves, array('id'=>floor($_GET['id'])), $settings['download_fileprefix'].'_'.floor($_GET['id'])));

	// add cycle
	if(isset($_GET['add_autoclave_cycle']))
		die(edit_autoclave_cycle());

	// remember me
	if(isset($_GET['rememberme']) && isset($_GET['operator']))
		die($_SESSION['rememberme']=$_GET['operator']);

	// cycle types
	if(isset($_GET['cycle_names']))
		die(json_encode($autoclaves[$_GET['cycle_names']]['cycles']));

	// logs count
	if(isset($_GET['logs_count']))
		die(get_logs_count($link, (isset($_GET['search'])?$_GET['search']:array())));

	// logs and logs count
	if(isset($_GET['logs']) || isset($_GET['logs_count'])) {
		die(json_logs_content($link, $settings, $autoclaves,(isset($_GET['pageNumber'])?$_GET['pageNumber']:null), (isset($_GET['pageSize'])?$_GET['pageSize']:null), (isset($_GET['search'])?$_GET['search']:array())));
	}
	// add log
	if(isset($_GET['add_log'])) 
		die(add_log($link, $settings, (isset($_GET['time'])?$_GET['time']:null),$autoclaves));

	// edit log
	if(isset($_GET['edit_log'])) 
		die(edit_log($link, $_GET['edit_log'], $autoclaves, $settings));

	// random name
	if(isset($_GET['random_name']))
		die(random_name());

	// view log
	if(isset($_GET['view_log'])) 
		die(display_log_content($link, get_log($link, $_GET['view_log']), $settings, $autoclaves));

	// save log
	if(isset($_GET['save']) && isset($_POST['id']) && isset($_POST['value'])) 
		die(save_log($link, $_POST['id'], $_POST['key'], $_POST['value']));

	// delete log
	if(isset($_GET['delete']) && isset($_POST['id']))
		die(delete_log($link, $_POST['id']));

	// upload image
	if(isset($_GET['upload_image']) && isset($_POST['image']) && isset($_POST['id']))
		die(upload_image($link, $settings, $_POST['id'], $_POST['image'], $settings['log_photo_image_file_extension']));

	// remove image
	if(isset($_GET['remove_image']) && isset($_POST['image']) && isset($_POST['id']))
		die(remove_image($link, $settings, $_POST['id'], $_POST['image']));

	// last log
	if(isset($_GET['automatic_log']) && isset($_POST['autoclave']))
		die(automatic_log($link, $settings, $_POST['autoclave'], isset($_POST['not_this'])?$_POST['not_this']:false));

	// add strings
	if(isset($_GET['add_string']) && isset($_GET['key']))
		die(display_string($_GET['key']));

	// add autoclave
	if(isset($_GET['add_autoclaves']))
		die(create_autoclave($link, $models));

	// remove autoclave
	if(isset($_GET['remove_autoclave']) && isset($_POST['id']))
		die(delete_autoclave($link, $_POST['id']));

	// save autoclave
	if(isset($_GET['save_autoclaves']) && isset($_POST['id']) && isset($_POST['key']) && isset($_POST['value'])) 
		die(save_autoclaves($link, $_POST['id'], $_POST['key'], $_POST['value']));

	// save autoclave order
	if(isset($_GET['save_autoclaves_order']) && isset($_POST['id']) && isset($_POST['order']))
		die(save_autoclaves_order($link, $_POST['id'], $_POST['order']));

	// save log
	if(isset($_GET['save_autoclave']) && isset($_POST['id']) && isset($_POST['value'])) 
		die(save_autoclave($link, $_POST['id'], $_POST['key'], $_POST['value']));

	// delete log
	if(isset($_GET['delete_autoclave']) && isset($_POST['id']))
		die(delete_autoclave($link, $_POST['id']));

	// save setting
	if(isset($_GET['save_setting']) && isset($_POST['key']) && isset($_POST['value'])) 
		die(save_setting($link, $_POST['key'], $_POST['value']));

	// get setting
	if(isset($_GET['get_setting']) && isset($_POST['key']))
		die(get_setting($settings, $_POST['key']));

	// autoclaves cycles
	if(isset($_GET['autoclaves_cycles']) && isset($_GET['model']))
		die(autoclave_default_cycles($models, $_GET['model']));

	// settings
	if(isset($_GET['settings'])) 
		die(display_settings($link, $settings, $autoclaves, $models, $languages));

	// change password
	if(isset($_GET['change_password']) && isset($_GET['password'])) 
		die(change_password($link, $_GET['password']));

	// change username
	if(isset($_GET['change_username']) && isset($_POST['value'])) 
		die(change_username($link, $_POST['value']));

	// change password
	if(isset($_GET['change_password']) && isset($_POST['current']) && isset($_POST['change'])) 
		die(change_password($link, $_POST['current'], $_POST['change']));

	// change language
	if(isset($_GET['change_language']) && isset($_POST['value']))
		die(change_user($link, 'language', $_POST['value']));



	// logout
	if(isset($_GET['logout']))
		die(logout());

	// nav
	$nav=array(
		'settings',
	);
	$ui['nav']='<ul>';
	foreach($nav as $n)
		$ui['nav'].='<li id="'.$n.'">'.icon($n, lang("Settings")).'</li>';

	// show stuff
	$ui['page']='logs';
	display_logs($link, $logs=array(), $settings, $autoclaves);
	refresh_interval($settings);
	download_link($settings);
	$ui['page_bottom']=$settings['help_colours'];

} else {
	// not logged in
	if(isset($_GET['signup']))
		signup_form($link, $settings);
	elseif(isset($_GET['forgot']))
		forgot_form($link, $settings);
	else {
		ui_element('note right', $ui['about']);
		login_form();
	}
}
$ui['main']=ob_get_contents();
ob_end_clean();

include_once('ui.inc.php');
?>
