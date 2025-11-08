<?php

// ui
$ui['name']='CLAVE-⚙-MATIC';
$ui['name_simple']='claveomatic';
$ui['logo']=$ui['name_simple'].'.png';
$ui['title']=$ui['name'];
$ui['site_title']=str_replace('⚙-','<span class="title_cog">⚙</span>-',$ui['name']).'<span class="dotcom">.com</span>';
$ui['donate']='mailto:yo@joeltron.com?subject=I want to give you money';
//$ui['source']='/?source';
$ui['source']='https://github.com/joeltrondotcom/claveomatic';
$ui['foot']='&copy;'.date('Y').'&nbsp;<a href="mailto:yo@joeltron.com?subject='.$ui['name'].' sucks">joeltron dot com</a><span class="right"><a href="'.$ui['source'].'" target="_BLANK">'.lang('open sorce').'</a> &amp; <a href="'.$ui['donate'].'">'.lang('free').'&nbsp;&lt;3</a></span>';
$ui['about']='What is <b>'.$ui['name'].'</b>?<ul>
	<li>Digital autoclave log recording</li>
	<li>Designed for the body art industry</li>
	<li>Seamlessly attach photos</li>
	<li>Supports multiple autoclaves</li>
	<li><a href="'.$ui['donate'].'">Free</a></u> to use and <a href="'.$ui['source'].'">open source</a></li>
	</ul><a href="'.$ui['donate'].'">'.icon('donate').'&nbsp;Donate</a> to keep it running!';
$ui['page']=null;
$ui['page_bottom']=null;
$ui['notifications']=array();
$ui['initial-scale']=1;
$ui['maximum-scale']=1.5;
$ui['user-scale']='yes';

// settings
$settings=array(
	'site_name'				=> $ui['name'],
	'site_url'				=> 'https://www.claveomatic.com',
	'site_email'				=> 'yo@joeltron.com',
	'log_photo_folder'			=> 'photos/',
	'log_photo_thumb_prefix'		=> 'thumbs/',
	'log_photo_image_width'			=> '1000',
	'log_photo_image_height'		=> '1000',
	'log_photo_image_file_extension'	=> 'jpeg',
	'log_photo_thumb_width'			=> '100',
	'log_photo_thumb_height'		=> '100',
	'log_refresh'				=> 300000,
	'log_refresh_options'			=> array(
		//1000	=>	'1 sec refresh',
		15000	=>	'15 sec refresh',
		30000	=>	'30 sec refresh',
		60000	=>	'1 min refresh',
		300000	=>	'5 min refresh',
		9000000	=>	'15 min refresh',
		false	=>	'Do not refresh',
	),
	'magic_autoclave_cycle_no'		=> true,
	'magic_autoclave_cycle_type'		=> true,
	'magic_autoclave_cycle_temp'		=> true,
	'magic_autoclave_cycle_duration'	=> true,
	'magic_autoclave_operator'		=> true,
	'magic_autoclave_status'		=> true,
	'magic_autoclave_desc'			=> false,
	'pagination_per_page'			=> 10,
	'help_no_logs'				=> '<b>'.lang("Nothing to see here...").'</b><br /><br />'.lang("Hit the big ol' orange").' <span onclick="add_log();">'.icon('add').'</span> '.lang("to create a log"),
	'help_no_results'			=> lang("No results"),
	'help_download'				=> lang("Create a a backup your autoclave results."),
	'help_colours'				=> '
	<div class="help_colours">
	<table><th class="no_content"></th><td>'.lang("Missing Information").'</td></th></table>
	<table><th class="status_running"></th><td>'.lang("Status: Running").'</td></th></table>
	<table><th class="status_passed"></th><td>'.lang("Status: Passed").'</td></th></table>
	<table><th class="status_failed"></th><td>'.lang("Status: Failed").'</td></th></table>
	</div>',
'help_autoclaves'			=> 'Your autoclaves',
	'help_magic_fill'			=> 'What options to auto fill when adding a new cycle, based on the last cycle.',
	'help_operators'			=> 'The names of your technitians.',
	'help_refresh'				=> 'How often to refresh the logs. (Refreshing is paused while adding/editing a log)',
	'help_autoclaves'			=> 'Add and modify your models of autoclaves.',
	'help_account'				=> 'Change your account details',
	'logs_download_link'			=> true,
	'download_file_formats'			=> array(
							'CSV'=>'CSV (Linked images)',
							'ZIP'=>'ZIP (CSV and images)',
							'PDF'=>'PDF (Embedded images)',
						),
	'download_default_format'		=> 'CSV',
	'download_fileprefix'			=> $ui['name_simple'],
	'statuses'				=> array(
							'Running',
							'Passed',
							'Failed',
	),
	'pdf_photos_per_row'			=> 3,
	'pdf_photos_max_rows'			=> 1,
	'pdf_photos_max_width'			=> '22em',
	'pdf_photos_max_height'			=> '22em',
);


// autoclaves
$scican_cycles=array(
	array(
		'name'=>'Unwrapped',
		'temp'=>'135',
		'time'=>'3.5',
	),
	array(
		'name'=>'Wrapped',
		'temp'=>'135',
		'time'=>'10',
	),
	array(
		'name'=>'Rubber/Plastic',
		'temp'=>'121',
		'time'=>'20',
	),
	array(
		'name'=>'Dry',
		'temp'=>'',
		'time'=>'',
	),
);

$models=array(
	'Scican'=>array(
		'Statim 2000'		=> $scican_cycles,
		'Statim 2000 G4'	=> $scican_cycles,
		'Statim 5000'		=> $scican_cycles,
		'Statim 5000 G4',
	),
);

// Languages
$languages=array(
	'English',
	'nEhglsi',
);
?>
