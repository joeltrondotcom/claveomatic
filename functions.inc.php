<?php

function is_logged_in() {
	if(isset($_SESSION['username']) && isset($_SESSION['password']))
		return true;
	return false;
}

function createThumbnail($sourceFile, $destinationFile, $newWidth) {
	// Get original image dimensions
	list($originalWidth, $originalHeight) = getimagesize($sourceFile);

	// Calculate new height to maintain aspect ratio
	$newHeight = floor($originalHeight * ($newWidth / $originalWidth));

	// Create a new true-color image for the thumbnail
	$thumbnailImage = imagecreatetruecolor($newWidth, $newHeight);

	// Create an image resource from the source file (assuming JPEG)
	$sourceImage = imagecreatefromjpeg($sourceFile);

	// Resample and copy the source image onto the thumbnail image
	imagecopyresampled($thumbnailImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

	// Save the thumbnail image as a JPEG
	imagejpeg($thumbnailImage, $destinationFile, 90); // 90 is the quality (0-100)

	// Free up memory
	imagedestroy($sourceImage);
	imagedestroy($thumbnailImage);
}


function remove_image($link, $settings, $id, $image) {
	// log id to folder ID
	$folder_id=get_log($link, $id, 'photo_id');
	if(!$folder_id)
		return false;

	$thumb=$settings['log_photo_folder'].$folder_id.'/'.$settings['log_photo_thumb_prefix'].$image.'.'.$settings['log_photo_image_file_extension'];

	if(file_exists($thumb))
		unlink($thumb);
}

function generateRandomString($length = 10) {
	return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

function upload_image($link, $settings, $id, $imageData) {
	// log id to folder ID
	$folder_id=get_log($link, $id, 'photo_id');
	if(!$folder_id) {
		$folder_id=generateRandomString();
		save_log($link, $id, 'photo_id', $folder_id);
	}

	// folders
	$folder=$settings['log_photo_folder'].$folder_id.'/';
	$thumbs_folder=$settings['log_photo_folder'].$folder_id.'/'.$settings['log_photo_thumb_prefix'];
	if (!file_exists($folder))		mkdir($folder, 0777, true);
	if (!file_exists($thumbs_folder))	mkdir($thumbs_folder, 0777, true);

	// decode image
	list($type, $data) = explode(';', $imageData);
	list(, $data)      = explode(',', $data);
	$decodedData = base64_decode($data);

	// file
	$filename=uniqid().'.'.$settings['log_photo_image_file_extension'];
	if(file_put_contents($folder.$filename, $decodedData))
		if(createThumbnail($folder.$filename, $thumbs_folder.$filename, $settings['log_photo_thumb_width']))
			return true;

	return false;
}

function starts_with($haystack, $needle) {
	if (substr($haystack, 0, strlen($needle)) === $needle)
		return true;
	return false;
}

function automatic_latest_log($link, $settings, $autoclave=false, $not_this=false) {
	// grab latest
	$q="SELECT `enabled`";	

	$pre_key="automatic_autoclave_";
	foreach($settings as $key=>$value) {
		if(starts_with($key, $pre_key)) {
			$name=str_replace($pre_key,'',$key);

			if($value==true)
				$q.=", `".$name."`";
		}
	}

	$q.=" from `logs` WHERE `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."'";
	if($autoclave!=false)
		$q.=" AND `autoclave` = '".mysqli_real_escape_string($link, $autoclave)."'";
	$q.=" AND `enabled` = '1' AND `cycle_no` > 0";
	if($not_this!=false)
		$q.=" AND `id` != '".mysqli_real_escape_string($link, $not_this)."'";
	$q.=" ORDER BY `id` DESC LIMIT 1";

	$res=mysqli_query($link, $q);
	$row=mysqli_fetch_assoc($res);

	if(isset($row['cycle_no']))
		$row['cycle_no']++;

	// only needed for verification
	unset($row['enabled']);

	return $row;
}

function automatic_log($link, $settings, $autoclave, $not_this=false) {
	$row=automatic_latest_log($link, $settings, $autoclave, $not_this);
	// jsonify it
	echo json_encode($row);
}

function logout() {
	unset($_SESSION['id']);
	unset($_SESSION['username']);
	unset($_SESSION['password']);
	unset($_SESSION['language']);

	header('Location: /');
}

function settings_checkbox($id, $checked=false) {
	$string='
	<label class="switch">
		<input type="checkbox" data-name="'.$id.'" name="'.$id.'"'.($checked?' checked':'').' />
		<span class="slider"></span>
	</label>';

	return $string;
}

function display_settings($link, $settings, $autoclaves, $models, $languages) {
?>

<!-- Operators -->
<div class="setting settings_operators">
	<h3><?=lang('Operators')?></h3>
	<div class="settings_help"><?=lang($settings['help_operators'])?></div>
	<table class="table strings">
		<thead>
			<th class="add" onclick="add_string(this);"><?=icon('add')?></th>
			<th><?=lang('Name')?></th>
			<th></th>
		</thead>
		<tbody data-id="operators"><?php
	foreach($settings['operators'] as $o) {
		echo '<tr>';
		display_string('operators', $o);
		echo '</tr>';
	}
?>
		</tbody>
	</table>
</div>


<!-- Autoclaves -->
<div class="setting settings_autoclaves">
	<h3><?=lang('Autoclaves')?></h3>
	<div class="settings_help"><?=lang($settings['help_autoclaves'])?></div>
	<table class="table autoclaves">
		<thead>
			<th class="add" onclick="add_autoclaves();"><?=icon('add')?></th>
			<th><?=lang('Model')?></th>
			<th><?=lang('Nickname')?></th>
			<th><?=lang('Cycles')?></th>
			<!--<th><?=lang('IP Address')?></th>-->
		</thead>
		<tbody class="tbody"><?php

	foreach($autoclaves as $a)
		display_autoclave($a, $models);
?>
		</tbody>
	</table>
</div>


<!-- Verification -->
<div class="setting settings_verification">
	<h3><?=lang('Cycle Verification')?></h3>
	<div class="settings_help"><?=lang($settings['help_verification'])?></div>
	<table class="table strings">
		<thead>
			<th class="add" onclick="add_string(this);"><?=icon('add')?></th>
			<th><?=lang('Name')?></th>
			<th></th>
		</thead>
		<tbody data-id="log_verification"><?php
	foreach($settings['log_verification'] as $o) {
		echo '<tr>';
		display_string('log_verification', $o);
		echo '</tr>';
	}
?>
		</tbody>
	</table>
</div>


<!-- Statuses -->
<div class="setting settings_statuses">
	<h3><?=lang('Cycle Statuses')?></h3>
	<div class="settings_help"><?=lang($settings['help_statuses'])?></div>
	<table class="table strings">
		<thead>
			<th class="add" onclick="add_string(this);"><?=icon('add')?></th>
			<th><?=lang('Name')?></th>
			<th></th>
		</thead>
		<tbody data-id="statuses"><?php
	foreach($settings['statuses'] as $o) {
		echo '<tr>';
		display_string('statuses', $o);
		echo '</tr>';
	}
?>
		</tbody>
	</table>
</div>


<!-- Contents -->
<div class="setting settings_contents">
	<h3><?=lang('Cycle Contents')?></h3>
	<div class="settings_help"><?=lang($settings['help_contents'])?></div>
	<table class="table strings">
		<thead>
			<th class="add" onclick="add_string(this);"><?=icon('add')?></th>
			<th><?=lang('Name')?></th>
			<th></th>
		</thead>
		<tbody data-id="log_contents"><?php
	foreach($settings['log_contents'] as $o) {
		echo '<tr>';
		display_string('log_contents', $o);
		echo '</tr>';
	}
?>
		</tbody>
	</table>
</div>





<!-- Automatic stuff -->
<div class="setting settings_automatic">
	<h3><?=lang('Automatic Fill')?> <?=icon('automatic')?></h3>
	<div class="settings_help"><?=lang($settings['help_automatic_fill'])?></div>
	<table class="table auto">
		<tbody>
<?php
	$pre_key="automatic_autoclave_";
	foreach($settings as $key=>$value) {
		if(starts_with($key, $pre_key)) {
			// name
			$name=str_replace($pre_key,'',$key);
			$name=ucwords(str_replace('_',' ',$name));
			$name=lang($name);

			// setting
			echo '<tr>';
			echo '<th>'.$name.'</th>';
			echo '<td>'.settings_checkbox($key,$value).'</td>';
			echo '</tr>';
		}
	}
?>
		</tbody>
	</table>
</div>


<!-- Refresh
<div class="setting settings_refresh">
	<h3><?=lang('Automatic Refresh')?></h3>
	<div class="settings_help"><?=lang($settings['help_refresh'])?></div>
		<?=array_to_dropdown($settings['log_refresh_options'], 'log_refresh_option', $settings['log_refresh'], "save_setting('log_refresh', $(this).val()); startRefreshTable($(this).val());")?></th>
</div>
-->

<!-- PWA -->
<div class="setting settings_pwa">
	<h3><?=lang('Install to device')?></h3>
	<div class="settings_help"><?=lang($settings['help_pwa'])?></div>
	<?=$settings['help_pwa_instructions']?>
</div>
<script language="javascript">
	if (isPwaStandalone())
		$('.settings_pwa').hide();
</script>

<!-- Account -->
<div class="setting settings_account">
	<h3><?=lang('Account')?></h3>
	<div class="settings_help"><?=lang($settings['help_account'])?></div>
	<table class="table auto"><tbody>
		<tr>
			<th>
				<?=lang('Username');?>
				<br />
				<a class="logout" href="?logout"><?=lang('Logout')?></a>
			</th>
			<td class="change_username">
				<a href="#" onclick="edit_account(this);"><?=icon('edit')?></a>
				<a href="#" onclick="done_account(this);"><?=icon('done')?></a>
				<input disabled="disabled" onchange="change_username(this);" value="<?=$_SESSION['username']?>" />
			</td>
		</tr>
		<tr>
			<th><?=lang('Password');?></th>	
			<td class="change_password">
				<a href="#" onclick="edit_account(this);"><?=icon('edit')?></a>
				<a href="#" onclick="done_account(this);"><?=icon('done')?></a>
				<div class="fake_password"><?=trim(str_repeat('* ', 12))?></div>
				<div class="fake_password_change"><?=lang('Change Password')?></div>
				<div class="fake_password_saved hidden"><?=lang('Password Saved')?></div>
				<table class="hidden_fields">
					<tr><td>
						<?=lang('Current')?>
					</td><td>
						<input autocomplete="off" data-name="password_current" type="password" value="" />
					</td></tr>
					<tr><td>
						<?=lang('New')?>
					</td><td>
					<input autocomplete="off" data-name="password_change" type="password" value="" />
					</td></tr>
					<tr><td>
						<?=lang('New again')?>
					</td><td>
					<input autocomplete="off" data-name="password_again" type="password" value="" />
					</td></tr>
					<tr><td></td><td>
					<input type="submit" onclick="change_password(this);" value="<?=lang('Change')?>" />
					</td></tr>
				</table>
			</td>
		</tr>
		<tr>
			<th><?=lang('Language');?></th>
			<td>
				<a href="#" onclick="edit_account(this);"><?=icon('edit')?></a>
				<a href="#" onclick="done_account(this);"><?=icon('done')?></a>
				<?=array_to_dropdown($languages, 'language" disabled="disabled' /*hack to disable*/, $_SESSION['language'], "change_language(this);", false /*disable blank*/);?>
			</td>
		</tr>
	</table>
</div>


<!-- Script -->
	<script>
	$( function() {
		// string options
		$('.strings tbody').sortable({
			handle: '.handle',
			update: function( ) {
				$(this).find('input:first').change();
			}
		});

		// autoclaves
		$('.autoclaves tbody').sortable({
			handle: '.handle',
			tolerance: 'pointer',
			update: function( ) {
				save_autoclaves_order(this);
			}
		});

		// checkbox change
		$('.table.auto input[type="checkbox"]').on('change', function() {
			// get values
			id=$(this).attr('data-name');
			val=false;
			if ($(this).is(':checked'))
				val=true;

			// save
			$.ajax({
			type: "POST",
				url: "?save_setting",
				data: {
				key: id,
					value: val,
				}
				});
		});
	});

	</script>


<?php
}

function download_logs($link, $settings, $file_type, $autoclaves, $search=array(), $filename=null) {
	$logs=get_logs($link, $pageNumber=null, $pageSize=null, $search);

	// remove
	foreach($logs as $k=>$log) {
		// autoclave
		if(isset($autoclaves[$logs[$k]['autoclave']])) {
			$autoclave = $autoclaves[$logs[$k]['autoclave']];
			$string = '';
			if($autoclave['nickname'])
				$string .= $autoclave['nickname'];

			if($autoclave['nickname'] && $autoclave['model'])
				$string .= " ";

			if($autoclave['model'])
				$string .= '('.$autoclave['model'].')';

			$logs[$k]['autoclave'] = $string;
		}

		// photos
		$logs[$k]['photos']='';

		// get photo files (reversed order)
		$files=array();
		if($log['photo_id']) {
			$files = glob('photos/'.$log['photo_id'].'/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
			$files = array_reverse($files);
		}

		// photos depending on file type
		switch($file_type) {
		case 'ZIP':
			foreach($files as $file)
				$logs[$k]['photos'].=$file.PHP_EOL;
			break;
		default:
			foreach($files as $file)
				$logs[$k]['photos'].=$settings['site_url'].'/'.$file.PHP_EOL;
		}
		$logs[$k]['photos']=trim($logs[$k]['photos']);

		// remove unwated
		unset($logs[$k]['id']);
		unset($logs[$k]['enabled']);
		unset($logs[$k]['owner']);
		unset($logs[$k]['photo_id']);
	}

	// get headers
	$headers=array();
	foreach($logs[0] as $k=>$v)
		array_push($headers, lang(ucwords(str_replace('_', ' ',$k))));

	// filename
	if(!$filename) {
		$filename=$settings['download_fileprefix'];
		$filename.='_'.date('Y').'_'.date('m').'_'.date('d');
	}

	// file type
	switch($file_type) {
	case 'PDF':
		download_logs_pdf($headers, $logs, $settings, $filename);
		break;
	case 'ZIP':
		download_logs_zip($headers, $logs, $settings, $filename);
		break;
	default:
	case 'CSV':
		$filename.='.csv';
		// start output
		header("Content-Type:application/csv"); 
		header("Content-Disposition:attachment;filename=".$filename); 

		download_logs_to_csv($headers, $logs);
		break;
	}
}

function download_logs_to_csv($headers, $logs, $file="php://output") {
	$output = fopen($file,'w') or die("Can't open ".$file);

	// put into CSV
	fputcsv($output, $headers);
	foreach($logs as $log)
		fputcsv($output, $log);

	return $output;
}

function download_logs_zip($headers, $logs, $settings, $filename=null) {
	// filename
	if(!$filename)
		$filename = $settings['download_fileprefix'];

	$zip = new ZipArchive();
	$tmpfile=tempnam("tmp", "zip");
	$zip->open($tmpfile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

	// csv
	$csv=download_logs_to_csv($headers, $logs, "php://temp");
	rewind($csv);	// return to the start of the stream
	$zip->addFromString($filename.'.csv', stream_get_contents($csv));

	// images
	foreach($logs as $log) {
		if(strlen($log['photos'])) {
			$photos=explode(PHP_EOL, $log['photos']);
			foreach($photos as $photo)
				$zip->addFile($_SERVER['DOCUMENT_ROOT'].'/'.$photo, $photo);
		}
	}

	$zip->close();

	header('Content-Type: application/zip');
	header('Content-Length: ' . filesize($tmpfile));
	header('Content-Disposition: attachment; filename="'.$filename.'.zip"');
	readfile($tmpfile);
	unlink($tmpfile);
	die();
}

function download_logs_pdf($headers, $logs, $settings, $filename=null) {
	// filename
	if(!$filename)
		$filename = $settings['download_fileprefix'];

	// load dompdf
	require 'vendor/autoload.php';

	// start dompdf
	define("DOMPDF_ENABLE_HTML5PARSER", true);
	$options = new Dompdf\Options();
	$options->set('isHtml5ParserEnabled', true);
	$options->setIsRemoteEnabled(true);
	$dompdf = new Dompdf\Dompdf($options);
	$dompdf->setPaper('A4', 'landscape');

	// css
	$html='<style>
		body, html {
		padding: 0;
		margin: 0.5em;
}
table {
clear: both;
}
table.logs {
border-collapse: collapse;
border: 3px solid #000;
table-layout: fixed; 
}
table.logs th,
	table.logs td {
	border: 1px solid #000;
	padding: 0.25em 0.5em;
}
table.logs th {
white-space: nowrap;
background: #ccc;
}
table.logs .log td {
border-top: 3px solid #000;
}
tr.photos td {
padding: 0;
border: 0;	
}
table .odd td {
background: #eee;
}
img {
max-width: '.$settings['pdf_photos_max_width'].';
max-height: '.$settings['pdf_photos_max_height'].';
}
</style>';

	$html.='<table class="logs"><thead><tr>';		
	foreach($headers as $h)
		if($h !== "Photos")
			$html.='<th>'.$h.'</th>';
	$html.='</tr></thead>';
	$html.='<tbody>';
	$logs_count=0;
	foreach($logs as $log) {
		$html.='<tr class="log'.($logs_count%2?' odd':'').'">';
		$index=0;
		foreach($log as $l) {
			if($headers[$index]=="Photos") {
				if(strlen($l)) {
					$html.='</tr><tr class="photos'.($logs_count%2?' odd':'').'"><td colspan="'.(count($headers)-1).'">';

					$html.="<table><tr>";
					$photos=explode(PHP_EOL,$l);	

					/* dompdf doesn't like pics that cross pages*/
					$pic_count=$row_count=0;
					foreach($photos as $p) {
						if($pic_count>=$settings['pdf_photos_per_row']) {
							$row_count++;
							$html.='</tr>';
							if($row_count>=$settings['pdf_photos_max_rows']) {
								$html.='</table></tr><tr class="photos"><td colspan="'.(count($headers)-1).'"><table><tr>';
								$html.="</table><table>";
								$row_count=0;
							}
							$html.='<tr>';
							$pic_count=0;
						}
						$html.='<td><a href="'.$p.'"><img src="'.$p.'" /></a></td>';
						$pic_count++;
					}

					$html.="</tr></table>";
					$html.='</td>';
				}
			} else {
				$html.='<td>';
				$html.=$l;
				$html.='</td>';
			}
			$index++;
		}
		$html.='</tr>';
		$logs_count++;
	}
	$html.='</tbody></table>';
	//	echo $html; die;

	// render and output
	$dompdf->loadHtml($html);
	$dompdf->render();
	$dompdf->stream($filename.'.pdf');
}

function get_setting($settings, $key) {
	if(isset($settings[$key]))
		echo $settings[$key];
	return false;
}

function display_string($key, $o=false) {
?>
	<td class="edits">
		<div class="handle"><?=icon('move')?></div>
		<div class="blank"><?=icon('blank')?></div>
		<div class="edit" onclick="edit_string(this);"><?=icon('edit')?></div>
		<div class="save" onclick="done_string(this);"><?=icon('done')?></div>
</td>
<td>
<input onchange="save_strings(this);" <?=$o!==false?'value="'.$o.'" disabled="disabled"':''?>  placeholder="-">

</td>
<td class="actions">
		<?=confirm_delete('delete_string(this);')?>
</td>

<?php
}

function create_autoclave($link, $models) {
	// insert new log
	$q="INSERT into `autoclaves` SET `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."'";
	$res=mysqli_query($link, $q);

	// grab the last inserted and display
	$last_id = mysqli_insert_id($link);


	$a=array(
		'id'		=> $last_id,
		'cycles'	=> array(),
	);

	display_autoclave($a, $models, $edit=true);
}

function autoclave_default_cycles($models, $value) {
	foreach($models as $brand=>$model)
		foreach($model as $m=>$c)
			if($value==$m)
				return json_encode($c);
	return false;
}

function display_autoclave($a=null, $models, $edit=false) {
?>
	<tr data-id="<?=$a['id']?>"<?=$edit?' class="editing"':''?>>
		<td class="edits">
			<div class="handle ui-sortable-handle"><?=icon('move')?></div>
			<div class="blank"><?=icon('blank')?></div>
			<div class="edit" onclick="edit_autoclaves(this);"><?=icon('edit')?></div>
			<div class="save" onclick="done_autoclaves(this);"><?=icon('done')?></div>

			<div class="remove_wrapper">
			<?=confirm_delete('delete_autoclave(this);')?>
			</div>
		<td>
		<select data-name="model" <?=$edit?'':'disabled="disabled" '?>onchange="save_autoclaves(this); display_autoclaves_cycles(this)">
			<option><?=str_repeat("&nbsp;",14)?>-</option>
<?php
	/* each model */
	$found=$selected=false;
	foreach($models as $brand=>$model) {
		echo '<optgroup label="'.$brand.'">';
		foreach($model as $m=>$c) {
			// no cycles, its ok - we forgive you
			if(is_int($m))	$m=$c;

			if($m==$a['model'])
				$selected=$found=true;
			echo '<option value="'.$m.'"'.($selected?' selected="selected"':'').'>'.$m.'</option>';
			$selected=false;
		}
		echo '</optgroup>';
	}

	/* custom */
	echo '<optgroup class="custom" label="'.lang('Custom').'">';
	if(!$found && !$edit && strlen($a['model']))
		echo '<option value="'.$a['model'].'" selected="selected">'.$a['model'].'</option>';
	echo '<option data-id="other">'.lang("_ _ _ _ _ _").'</option>';
	echo '</optgroup>';
?>
			</select>
		</td>
		<td><input class="nickname" data-name="nickname" <?=$edit?'':'disabled="disabled" '?>onchange="save_autoclaves(this);" value="<?=$a['nickname']?>" placeholder="-" /><span onclick="random_name(this);"><?=icon('random');?></span></td>
		<td>
			<div class="add_autoclaves_cycles" onclick="add_autoclaves_cycles(this);"><?=icon('add')?></div>
			<div class="cycles">
<?php
	foreach($a['cycles'] as $cycle)
		edit_autoclave_cycle($cycle);
?>
			</div>
		</td>
		<!--<td>
			<div class="ip_address<?=$edit?' hidden':''?>">
				<input data-name="ip_address" <?=$edit?'':'disabled="disabled"'?>onchange="save_autoclaves(this);" value="<?=$a['ip_address']?>" />
				<div class="actions">
					<?=icon('test');?>
					<?=icon('load');?>
				</div>
			</div>
		</td>-->
		</tr><?php
}

function edit_autoclave_cycle($cycle=array()) {
?>
	<div class="cycle">
		<?=confirm_delete('remove_autoclaves_cycles(this);');?>
		<table>
<?php
	foreach(array('name','temp','time','pressure') as $e) {
?>
			<tr>
				<td class="label"><?=lang(ucwords($e))?></td>
				<td><input onchange="save_autoclaves(this);" class="cycle_<?=$e?>" data-name="cycle_<?=$e?>" value="<?=isset($cycle['cycle_'.$e])?$cycle['cycle_'.$e]:''?>"<?=!count($cycle)?'':' disabled="disabled"'?>  placeholder="-" /></td>
			</tr>
<?php
	}
?>
		</table>
	</div>
<?php
}

function delete_autoclave($link, $id) {
	// query
	$q="UPDATE `autoclaves` SET `enabled` = '0' WHERE `id` = '".mysqli_real_escape_string($link, $id)."' AND `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' LIMIT 1";
	$res=mysqli_query($link, $q);
}

function ui_element($id=null, $text=null) {
	echo '<div class="'.$id.'">'.$text.'</div>';
}

function change_password($link, $current, $change) {
	$q='UPDATE `users` SET `md5password` = MD5("'.mysqli_real_escape_string($link, $change).'") WHERE ( md5password = "'.mysqli_real_escape_string($link, $current).'" OR md5password = MD5("'.mysqli_real_escape_string($link, $current).'")) AND `id` = "'.mysqli_real_escape_string($link, $_SESSION['id']).'" LIMIT 1';

	$res=mysqli_query($link, $q);

	if(mysqli_error($link))
		echo "error"; echo mysqli_error($link);

	if(mysqli_affected_rows($link)>0) {
		$_SESSION['password']=$change;
		echo "saved";
	}
}

function change_username($link, $un) {
	$error = false;

	// check email
	if(!filter_var($un, FILTER_VALIDATE_EMAIL)) {
		echo 'error';	//lang("Invalid email format");
		return false;
	}

	if(change_user($link, 'username', $un) !== false)
		$_SESSION['username']=$un;
}

function change_user($link, $key, $val) {
	// sanitise value
	$val=mysqli_real_escape_string($link, $val);

	$q="UPDATE `users` SET `".mysqli_real_escape_string($link, $key)."` = '".$val."' WHERE `id` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' LIMIT 1";
	$res=mysqli_query($link, $q);

	if(mysqli_error($link))
		echo mysqli_error($link);
	else {
		if(isset($_SESSION[$Key]))
			$_SESSION[$key]=$val;
		echo "saved";
		return;
	}

	return false;
}

function signup_form($link, $settings) {
	//	echo 'Currently this website is invite only. Email <a href="mailto:yo@joeltron.com?Sign me up, scottie!">yo@joeltron.com</a> to get early access and help beta test.';

	$error=null;
	if(isset($_POST['signup']) && isset($_POST['username']) && isset($_POST['password'])) {
		$un = $_POST['username'];
		if(!filter_var($un, FILTER_VALIDATE_EMAIL))
			$error = lang("Invalid email format");
		else 
			if(user_exists($link, $un))
				$error = lang("Account already exists for")." <b>".$un."</b>";
			else
				if(!user_create($link, $un, $_POST['password']))
					$error = lang("Error creating account");

		if($error===null) {
			login($link, $un=null, $pw=null);
			header("Location: /");
			//return ui_element('note', lang("Your account has been created. Yay!"));
		}
	}

	// show error
	if($error !== null)
		ui_element('note error', lang("Error").": ".lang($error));
?>
	<form class="login" action="" method="POST">
		<label for="username"><?=lang('E-Mail')?></label>
		<input <?=$error?'class="notsaved" ':''?>type="email" name="username" id="username" value="<?=$un?>" />
		<label for="password"><?=lang('Password')?></label>
		<input <?=$error?'class="notsaved" ':''?>type="password" name="password" id="password" value="<?=$pw?>" />
		<input name="signup" id="signup" type="submit" value="<?=lang('Sign Up')?>" />
	</form>
<?php
}

function forgot_form($link, $settings) {
	if(isset($_POST['reset']) && isset($_POST['username'])) {
		send_reset_password_email($link, $settings, $_POST['username']);
		ui_element('note', lang("Your temporary password has been sent to your email."));
		return true;
	}
?>
	<form class="login" action="" method="POST">
		<label for="username"><?=lang('E-Mail')?></label>
		<input <?=$error?'class="notsaved" ':''?>type="email" name="username" id="username" value="<?=$un?>" />
		<input name="reset" id="reset" type="submit" value="<?=lang('Reset Password')?>" />
	</form>
<?php
}

function login_form() {
	$error=false;
	$un='';
	$pw='';

	// start form
	echo '<form class="login" action="" method="POST">';

	// fill in un/pw
	if(isset($_POST['username']) && isset($_POST['password'])) {
		$error=true;
		$un=$_POST['username'];
		$pw=$_POST['password'];

		echo '<div id="login_error" class="error">'.lang('Incorrect username/password.').'</div>';
	}
?>
		<label for="username"><?=lang('E-Mail')?></label>
		<input <?=$error?'class="notsaved" ':''?>type="email" name="username" id="username" value="<?=$un?>" />
		<label for="password"><?=lang('Password')?></label>
		<input <?=$error?'class="notsaved" ':''?>type="password" name="password" id="password" value="<?=$pw?>" />
		<input name="submit" id="submit" type="submit" value="<?=lang('Login')?>" />
		<a class="forgot" href="?forgot"><?=lang('Forgot')?></a>
		<a class="signup" href="?signup"><?=lang('Sign Up')?></a>
	</form>
<?php
}

function db_connect($sql_details) {
	$link = mysqli_connect($sql_details['host'], $sql_details['user'], $sql_details['pass']);

	if(!$link) { die('No Database... plz hold...'); }

	mysqli_select_db($link, $sql_details['db']);

	$cookie_path = "/";

	$cookie_timeout = 60 * 30 * 30; // in seconds
	$garbage_timeout = $cookie_timeout + 600; // in seconds
	session_set_cookie_params($cookie_timeout, $cookie_path);
	ini_set('session.gc_maxlifetime', $garbage_timeout);

	$sessdir = ini_get('session.save_path')."/my_sessions";
	if (!is_dir($sessdir)) { mkdir($sessdir, 0777); }
	ini_set('session.save_path', $sessdir);

	session_start();

	return $link;
}

function send_reset_password_email($link, $settings, $un) {
	if($password=get_password($link, $un)) {
		$to = $un;
		$subject = lang($settings['site_name'])." ".lang("Password Reset");
		$message = lang("Your temporary password is:")." ".$password;
		$headers = "From: ".$settings['site_email']."\r\n";
		$headers .= "Reply-To: ".$settings['site_email']."\r\n";

		if (mail($to, $subject, $message, $headers))
			return true;
	}
	return false;
}

function get_password($link, $un) {
	$res=mysqli_query($link, $q='SELECT `md5password` from `users` WHERE `username` = "'.mysqli_real_escape_string($link, $un).'" LIMIT 1');
	echo mysqli_error($link);

	if(mysqli_num_rows($res)>0) {
		$row=mysqli_fetch_assoc($res);
		return $row['md5password'];
	}
	return false;
}

function user_create($link, $un, $pw) {

	$q="INSERT into `users` SET `username` = '".mysqli_real_escape_string($link, $un)."', `md5password` = MD5('".mysqli_real_escape_string($link, $pw)."')";

	$res=mysqli_query($link, $q);
	echo mysqli_error($link);

	if(mysqli_affected_rows($link)>0)
		return true;

	return false;
}

function user_exists($link, $un) {
	$res=mysqli_query($link, $q='SELECT `username` from `users` WHERE `username` = "'.mysqli_real_escape_string($link, $un).'" LIMIT 1');

	echo mysqli_error($link);
	if(mysqli_num_rows($res)>0)
		return true;
	return false;
}

function login($link, $un=null, $pw=null) {
	$redirect = false;

	// already logged in
	if(isset($_SESSION['username']) && isset($_SESSION['password'])) {
		$un=$_SESSION['username'];
		$pw=$_SESSION['password'];
	}

	// logging in
	if(isset($_POST['username']) && isset($_POST['password'])) {
		$un=$_POST['username'];
		$pw=$_POST['password'];
		$redirect = true;
	}

	// check MD5ed and non MD5ed password
	$q='SELECT * from users WHERE username = "'.mysqli_real_escape_string($link, $un).'" AND ( md5password = "'.mysqli_real_escape_string($link, $pw).'" OR md5password = MD5("'.mysqli_real_escape_string($link, $pw).'")) LIMIT 1';

	$res=mysqli_query($link, $q);
	echo mysqli_error($link);
	if(mysqli_num_rows($res)>0) {
		$row=mysqli_fetch_assoc($res);

		setcookie("id",         $row['id'],             time()+360000);
		setcookie("username",   $row['username'],       time()+360000);
		setcookie("password",   $row['md5password'],    time()+360000);
		setcookie("language",   $row['language'],    time()+360000);

		$_SESSION['id']=$row['id'];
		$_SESSION['username']=$row['username'];
		$_SESSION['password']=$row['md5password'];
		$_SESSION['language']=$row['language'];

		if($redirect)
			header('Location: /');

		return true;
	}

	return false;
}

function lang($in, $lang='english') {
	if($_SESSION['language']=="nEhglsi") {
		$in=str_shuffle($in);
	}

	return $in;
}

function debug($in) {
	echo '<pre class="debug">';
	var_dump($in);
	echo '</pre>';
}

function load_settings($link, $settings) {
	$should_be_array=array(
		'operators',
		'statuses',
		'log_verification',
		'log_contents',
	);

	$id=isset($_SESSION['id'])?$_SESSION['id']:false;

	$q="SELECT `key`, `value` from `settings` WHERE `owner` = '".mysqli_real_escape_string($link, $id)."'";
	$res=mysqli_query($link, $q);
	echo mysqli_error($link);
	if(mysqli_num_rows($res)>0)
		while($row=mysqli_fetch_assoc($res)) {
			$value=$row['value'];
			if($value=="false")
				$value=false;
			if($value=="true")
				$value=true;
			if(in_array($row['key'], $should_be_array))
				if(strlen($value))
					$value=explode(PHP_EOL,$value);
				else
					$value=array();

			// set value
			$settings[$row['key']] = $value;
		}
	return $settings;
}

function get_unlisted_operators($link, $operators) {
	$id=isset($_SESSION['id'])?$_SESSION['id']:false;
	$unlisted_operators=array();

	// query
	$q="SELECT DISTINCT `operator` from `logs` WHERE `owner` = '".mysqli_real_escape_string($link, $id)."'";
	$res=mysqli_query($link, $q);
	echo mysqli_error($link);

	while($row=mysqli_fetch_assoc($res))
		if($row['operator'])
			array_push($unlisted_operators, $row['operator']);

	// find unique
	$unlisted_operators=array_diff($unlisted_operators, $operators);
	return $unlisted_operators;
}

function get_autoclave($link, $id) {
	$q="SELECT * from `autoclaves` WHERE `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' AND `id` = '".mysqli_real_escape_string($link, $id)."' LIMIT 1";
	$res=mysqli_query($link, $q);
	if(mysqli_num_rows($res)>0) 
		return mysqli_fetch_assoc($res);
	return false;
}

function get_autoclaves($link) {
	$autoclaves=array();

	// query
	$q="SELECT * from `autoclaves` WHERE `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' AND `enabled` = '1' ORDER BY `order` ASC";
	$res=mysqli_query($link, $q);
	echo mysqli_error($link);
	if(mysqli_num_rows($res)>0)
		while($row=mysqli_fetch_assoc($res))
			$autoclaves[$row['id']]=$row;

	// cycles from json object to array
	foreach($autoclaves as $id=>$row) {
		$autoclaves[$id]['cycles'] = (array) json_decode($row['cycles']);

		// arrayify
		foreach($autoclaves[$id]['cycles'] as $index=>$cycle)
			$autoclaves[$id]['cycles'][$index] = (array) $cycle;

		// don't need this
		if(isset($autoclaves[$id]['cycles']['length']))
			unset($autoclaves[$id]['cycles']['length']);

	}

	return $autoclaves;
}

function get_log($link, $id, $to_get=null) {
	$logs=array();

	// what to get
	$get='*';
	if($to_get!==null)
		$get="`".mysqli_real_escape_string($link, $to_get)."`";

	// query
	$q="SELECT ".$get." from `logs` WHERE `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' AND `id` = '".mysqli_real_escape_string($link, $id)."' AND `enabled` = '1' LIMIT 1";
	$res=mysqli_query($link, $q);

	if(mysqli_num_rows($res)>0) {
		$row=mysqli_fetch_assoc($res);
		if($to_get===null)
			return $row;
		else
			return $row[$to_get];
	}
	return false;
}

function get_logs_count($link, $search=array()) {
	$logs_count=get_logs($link, $pageNumber=null, $pageSize=null, $search, $order='id', $count=true);

	return $logs_count;
}

function get_logs($link, $pageNumber=null, $pageSize=null, $search=array(), $order='id', $count=false) {
	$logs=array();

	// query
	$q="SELECT ".($count?"count(id) ":"* ");
	$q.="from `logs` WHERE `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' AND `enabled` = '1'";

	// search
	foreach($search as $k=>$a) {
		$q.=" AND (";

		// key
		$key=$k;
		switch($k) {
		case "date_from":
		case "date_to":
			$key="datetime";
			break;
		case "cycle_from":
		case "cycle_to":
			$key="cycle_no";
			break;
		case 'photos':
			$key="photo_id";
			break;
		}

		// ensure array
		if(!is_array($a))
			$a=array($a);

		foreach($a as $v) {
			$q.="`".mysqli_escape_string($link, $key)."`";
			$v=mysqli_escape_string($link, $v);

			// value
			switch($k) {
			case "contents":
				$q.=" LIKE '%".$v."%'";
				break;
			case "cycle_from":
			case "date_from":
				$q.=" >= '".$v."'";
				break;
			case "cycle_to":
			case "date_to":
				$q.=" <= '".$v."'";
				break;
			case "photos":
				$q.=" IS".($v?" NOT":"")." NULL";
				break;
			default:
				// empty
				if($v=="-")
					$q.=" IS NULL";
				else	
					$q.=" = '".$v."'";
				break;
			}
			$q.=" OR ";
		}
		$q.='FALSE )';
	}

	// order
	$q.=" ORDER BY `".mysqli_real_escape_string($link, $order)."` DESC";

	// debug
	//echo $q; die;

	// pageinator
	if($pageNumber) {
		$q.=" LIMIT ".$pageSize;
		if($pageSize)
			$q.=" OFFSET ".$pageSize*($pageNumber-1);
	}

	$res=mysqli_query($link, $q);
	echo mysqli_error($link);
	if(mysqli_num_rows($res)>0)
		while($row=mysqli_fetch_assoc($res))
			array_push($logs, $row);

	// only give count
	if($count)
		return $logs[0]["count(id)"];

	return $logs;
}

function refresh_interval($settings) {
	echo '<div class="refresh_interval">';
	echo array_to_dropdown($settings['log_refresh_options'], 'log_refresh_option', $settings['log_refresh'], "save_setting('log_refresh', $(this).val()); startRefreshTable($(this).val());", $blank_first_option=false);
	echo '</div>';
}

function download_link($settings) {
	echo '<div class="download_link">';
	echo array_to_dropdown(array_merge(array(lang('Backup')),$settings['download_file_formats']), 'download_link', lang('Backup'), "download_logs(this);", $blank_first_option=false);
	echo '</div>';
}

function array_to_dropdown($array, $id, $val=null, $onchange=null, $blank_first_option=true, $allow_other=false) {
	if($allow_other)
		$onchange.=" dropdown_other(this);";

	$html='<select '.($onchange==null?'':'onchange="'.$onchange.'"').'data-name="'.$id.'" name="'.$id.'">';

	if($blank_first_option)
		$html.='<option></option>';

	if($allow_other)
		$html.='<optgroup label="'.lang('Select').'">';

	$found=$found_this=false;
	$count=0;
	foreach($array as $k=>$v) {
		if(is_array($v)) {
			$html.='<optgroup class="custom" label="'.lang($k).'">';
			foreach($v as $vv) {
				$html.='<option value="'.$vv.'">'.$vv.'</option>';

			}
			$html.='</optgroup>';
		} else {
			// if no index, use the value
			if(is_int($k) && $k==$count)	$k=$v;

			// look for val
			if($val!==null && strtolower($k)==strtolower($val))
				$found=$found_this=true;

			$html.='<option value="'.$k.'"'.($found_this?' selected="selected"':'').'>'.$v.'</option>';
			$found_this=false;

		}
		$count++;
	}

	if($allow_other)
		$html.='</optgroup>';

	// allow other or not in list
	if($allow_other || ($val!==null && $val!=="" && !$found)) {
		$html.='<optgroup class="custom" label="'.lang('Other').'">';

		if(strlen($val))
			$html.='<option value="'.$val.'" selected="selected">'.$val.'</option>';

		if($allow_other)
			$html.='<option value="" data-id="other">'.lang('_ _ _ _ _ _').'</option>';

		$html.='</optgroup>';
	}

	$html.='</select>';

	return $html;
}

function autoclave_names_from_list($autoclaves, $prefix='&nbsp;(',$postfix=')') {
	$autoclave_names=array();
	foreach($autoclaves as $k=>$v)
		$autoclave_names[$k]=(strlen($v['nickname'])?$v['nickname']:'').(strlen($v['model'])?$prefix.$v['model'].$postfix:'');

	return $autoclave_names;
}

function display_logs($link, $logs, $settings, $autoclaves=array()) {

	// datetime
	$search['datetime']='<input data-name="date_from" class="date" placeholder="'.lang('From').'" /><span class="divider">-</span><input data-name="date_to" class="date" placeholder="'.lang("To").'" />';

	// autoclaves to names
	$autoclave_names=autoclave_names_from_list($autoclaves);
	$autoclave_names['-']='-';		// empty
	$search['autoclaves']=array_to_dropdown($autoclave_names, 'autoclave');

	// cycles
	$search['cycle']='<input data-name="cycle_from" class="cycle_no" placeholder="'.lang('From').'" /><span class="divider">-</span><input data-name="cycle_to" class="cycle_no" placeholder="'.lang("To").'" />';

	// operators
	if(isset($settings['operators'])) {
		$operators_names=$settings['operators'];
		foreach($operators_names as $id=>$name)
			if(!strlen($name))
				$operators_names[$id]=str_repeat("&nbsp;",14).'-';
		$operators_names['-']='-';		// empty

		$unlisted_operators=get_unlisted_operators($link, $settings['operators']);
		if(count($unlisted_operators))
			$operators_names['Removed']=$unlisted_operators;

		$search['operators']=array_to_dropdown($operators_names, 'operator');
	}

	// status
	$search['status']=array_to_dropdown(array_merge($settings['statuses'],array('-'=>'-')), 'status');

	// contentsription
	$search['contents']='<input data-name="contents" />';

	// photos
	$search['photos']=array_to_dropdown(
		array(
			1=>lang('Yes'),
			0=>lang('No'),
		), 'photos');

	// image helper
	echo '<div class="fileInputHelper">';
	echo '<input type="hidden" id="fileInputID" />';
	echo '<input type="hidden" id="fileInputMaxWidth" value="'.$settings['log_photo_image_width'].'" />';
	echo '<input type="hidden" id="fileInputMaxHeight" value="'.$settings['log_photo_image_height'].'" />';
	echo '<input type="file" id="fileInput" accept="image/*;capture=camera" capture="environment" />';
	echo '<canvas id="fileInputCanvas"></canvas>';
	echo '</div>';

	// display table
	echo '
	<table class="table logs" id="logs">
		<thead>
			<tr class="search">
				<th class="add" rowspan="2">'.icon('add').'</th>
				<th>'.$search['datetime'].'</th>';

	if(count($autoclaves))
		echo '		<th>'.$search['autoclaves'].'</th>';

	echo '			<th>'.$search['cycle'].'</th>';

	if(count($settings['operators']))
		echo '		<th>'.$search['operators'].'</th>';

	echo '			<th>'.$search['status'].'</th>
				<th>'.$search['contents'].'</th>
				<th class="photos">'.$search['photos'].'</th>';

	echo '		</tr>
			<tr class="headers">
				<th>'.lang('Date Time').'</th>';

	if(count($autoclaves))
		echo '		<th>'.lang('Autoclave').'</th>';

	echo '			<th>'.lang('Cycle').'</th>';

	if(count($settings['operators']))
		echo '<th>'.lang('Operator').'</th>';

	echo '			<th>'.lang('Status').'</th>
				<th>'.lang('Contents').'</th>
				<th class="photos">'.lang('Photos').'</th>';
	echo '		</tr>
		</thead>
		<tbody id="logs_content">';
	echo '</tbody>';
	echo '</table>';
}

function json_logs_content($link, $settings, $autoclaves=array(), $pageNumber=0, $pageSize=20, $search=array()) {
	header('Content-Type: application/json; charset=utf-8');
	$logs=get_logs($link, $pageNumber, $pageSize, $search);

	$logs_array=array();
	foreach($logs as $log) {
		// display log and put it into ajax
		ob_start();
		display_log_content($link, $log, $settings, $autoclaves);
		$html=ob_get_contents();
		array_push($logs_array, $html);
		ob_end_clean();
	}

	// no content, show message
	if(!count($logs_array)) {
		if(count($search))
			array_push($logs_array, '<td colspan="8" class="no_logs">'.$settings['help_no_results'].'</td>');
		else
			array_push($logs_array, '<td colspan="8" class="no_logs">'.$settings['help_no_logs'].'</td>');
	}

	echo json_encode($logs_array);
}

function display_log_content($link, $log, $settings, $autoclaves=array()) {
	$class='';

	// autoclave
	if(!$log['autoclave'])
		$class.=' no_autoclave';

	// cycle
	if(!$log['cycle_no'] ||
		!$log['cycle_name'] || 
		!$log['cycle_temp'] || 
		!$log['cycle_time']
	)
	$class.=' no_cycle';

	// time
	if(!$log['datetime'])
		$class.=' no_time';

	// operator
	if(!$log['operator'])
		$class.=' no_operator';

	// status
	if($log['status'])
		$class.=' status_'.strtolower($log['status']);
	else
		$class.=' no_status';

	// contents
	if(!$log['contents'])
		$class.=' no_contents';

	// photos
	$thumb_folder=$settings['log_photo_folder'].$log['photo_id'].'/'.$settings['log_photo_thumb_prefix'];
	if(file_exists($thumb_folder)) {
		$fi = new FilesystemIterator($thumb_folder, FilesystemIterator::SKIP_DOTS);
		$thumb_count = iterator_count($fi);
		if($thumb_count==0)
			$class.=' no_photos';
	} else
		$class.=' no_photos';

	// show row
	echo '<tr data-id="'.$log['id'].'"'.(strlen($class)?' class="'.trim($class).'"':'').'>';
	display_log($link, $log, $autoclaves, $settings);
	echo '</tr>';
}

function time_elapsed_string($diff) {

	echo $diff; return $diff.


		$diff->w = floor($diff->d / 7);
	$diff->d -= $diff->w * 7;

	$string = array(
		'y' => 'year',
		'm' => 'month',
		'w' => 'week',
		'd' => 'day',
		'h' => 'hour',
		'i' => 'minute',
		's' => 'second',
	);
	foreach ($string as $k => &$v) {
		if ($diff->$k) {
			$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
		} else {
			unset($string[$k]);
		}
	}

	if (!$full) $string = array_slice($string, 0, 1);
	return $string ? implode(', ', $string) . ' '.lang('ago') : lang('just now');
}

function display_log($link, $log, $autoclaves=array(), $settings) {
	echo '<td class="edit" onclick="edit_log(this);">'.icon('edit').'</td>';

	echo '<td class="time">';
	echo '<a target="_BLANK" href="/?log_download&id='.$log['id'].'">';
	echo '<time class="time">';
	$date = new DateTimeImmutable($log['datetime']);
	echo $date->format('Y-m-d H:i');
	echo '</time>';
	echo '<span>';
	echo icon('print', null /*text*/, true /*just text*/);
	echo '</span>';
	echo '</a>';
	echo '<time class="timeago" data-time="'.$date->format('Y-m-d H:i').'"></time>';
?>
	<script language="javascript">
	timelocalformat($('.logs tr[data-id="<?=$log['id']?>"] time.time'));
	timeago($('.logs tr[data-id="<?=$log['id']?>"] .timeago'));
	</script>
<?php
	echo '</td>';

	if(count($autoclaves)) {
		echo '<td class="autoclave">';
		if(isset($autoclaves[$log['autoclave']])) {
			echo strlen($autoclaves[$log['autoclave']]['nickname'])?$autoclaves[$log['autoclave']]['nickname']:'&nbsp;';
			echo '<span class="model">'.$autoclaves[$log['autoclave']]['model'].'</span>';
		} else {
			if($log['autoclave']) {
				$name='[ '.lang("Removed").' ]';
				$model='[ id:'.$log['autoclave'].' ]';
				// removed
				if($removed = get_autoclave($link, $log['autoclave'])) {
					$name=$removed['nickname'].' '.$name;
					$model=$removed['model'].' '.$model;
				}

				echo '<span class="removed">'.$name.'</span>';
				echo '<span class="model">'.$model.'</span>';
			} else {
				// no autoclave
				echo '-';
			}
		}
		echo '</td>';
	}

	echo '<td class="cycle">';
	if($log['cycle_no'])
		echo '#'.$log['cycle_no'];
	else
		echo '-';
	echo '<ul>';

	foreach(array('name', 'temp', 'time', 'pressure') as $i) {
		if($log['cycle_'.$i]) {
			echo '<li>'.$log['cycle_'.$i];
			if(isset($settings['log_cycle_symbols'][$i]))
				echo '<span class="symbol">'.$settings['log_cycle_symbols'][$i].'<span>';
			echo '</li>';
		}
	}
	echo '</ul>';
	echo '</td>';

	if(count($settings['operators']))
		echo '<td class="operator">'.($log['operator']?$log['operator']:'-').'</td>';

	echo '<td class="status">';
	if($log['status'])
		echo $log['status'];
	else
		echo '-';

	if(strlen($log['status_note']))
		echo '<span class="status_note">'.$log['status_note'].'</span>';
	echo '</td>';

	echo '<td class="contents">';
	echo '<div>'.nl2br($log['contents'].'</div>');
	if($log['verification'])
		echo '<span class="verification">'.$log['verification'].'</span>';
	echo '</td>';

	echo '<td class="photos">';
		display_log_photos($log['photo_id'], $log, $autoclaves, $edit=true);
	echo '</td>';
}

function display_log_photos($photo_id, $log, $autoclaves, $edit=false) {

	// craft header
	$header='';
	if($log['autoclave']) {
		$header.='<b>';
		if(isset($autoclaves[$log['autoclave']]['nickname']))
			$header.=$autoclaves[$log['autoclave']]['nickname'];
		else
			$header.=lang('Removed');
		$header.='</b> ';
	}
	if($log['cycle_no'])
		$header.='('.lang('Cycle')."# ".$log['cycle_no'].') ';
	$header=trim($header);

	// get files (reversed order)
	$files = glob('photos/'.$photo_id.'/thumbs/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
	$files = array_reverse($files);

	foreach($files as $file) {
		// file strings
		if($file) {
			$file_id=explode('/',$file);
			$file_id=end($file_id);
			$file_id=explode('.',$file_id)[0];
			$full=str_replace('/thumbs','', $file);

			echo '<div class="logs_image" data-id="'.$file_id.'">';
			echo '<img onclick="show_image(\''.$full.'\', \''.$header.'\', this);" src="'.$file.'" />';
			if($edit)
				confirm_delete('remove_logs_image(this);');

			echo '</div>';
		}
	}
}

function save_setting($link, $key, $value) {
	// no key, gone gittttt
	if(!strlen($key))	return false;

	// array of values
	if(is_array($value)) {
		$new_value="";
		foreach($value as $v)
			$new_value.=$v.PHP_EOL;
		$value=trim($new_value);
	}

	// first remove
	$q="DELETE from `settings` WHERE `key` = '".mysqli_real_escape_string($link, $key)."' AND `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' LIMIT 1";
	$res=mysqli_query($link, $q);

	// add
	$q="INSERT into `settings` SET `key` = '".mysqli_real_escape_string($link, $key)."', `value` = '".mysqli_real_escape_string($link, $value)."', `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."'";
	$res=mysqli_query($link, $q);
	if(mysqli_affected_rows($link)>0)
		echo "saved";

	if(mysqli_error($link))
		echo "error";

	die;
}

function save_autoclaves($link, $id, $key, $value) {
	$q="UPDATE `autoclaves` SET `".mysqli_real_escape_string($link, $key)."` = '".mysqli_real_escape_string($link, $value)."' WHERE `id` = '".mysqli_real_escape_string($link, $id)."' AND `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' LIMIT 1";
	$res=mysqli_query($link, $q);
	if(mysqli_affected_rows($link)>0)
		echo "saved";

	if(mysqli_error($link))
		echo "error";

	die;
}

function save_log($link, $id, $key, $value) {
	// sanitise to array
	if(!is_array($key))	$key=array($key);
	if(!is_array($value))	$value=array($value);

	// null
	if($value<=0)	$value="NULL";

	$q="UPDATE `logs` SET ";
	for($x=0; $x<count($key); $x++) {
		$q.="`".mysqli_real_escape_string($link, $key[$x])."`";
		$q.=" = ";
		if(isset($value[$x]) && strlen($value[$x]))
			$q.="'".mysqli_real_escape_string($link, $value[$x])."'";
		else
			$q.="NULL";
		if($x<(count($key)-1))
			$q.=",";
		$q.=" ";
	}

	$q.="WHERE `id` = '".mysqli_real_escape_string($link, $id)."' AND `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' LIMIT 1";

	//echo $q; die;

	$res=mysqli_query($link, $q);
	if(mysqli_affected_rows($link)>0)
		echo "saved";

	if(mysqli_error($link))
		echo "error";
}

function delete_log($link, $id) {
	// disable
	$q="UPDATE `logs` SET `enabled` = '0' WHERE `id` = '".mysqli_real_escape_string($link, $id)."' AND `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' LIMIT 1";

	$res=mysqli_query($link, $q);
	if(mysqli_affected_rows($link)>0)
		echo "deleted";

	// delete
	/*$q="DELETE FROM `logs` WHERE `id` = '".mysqli_real_escape_string($link, $id)."' AND `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."' LIMIT 1";

	$res=mysqli_query($link, $q);
	if(mysqli_affected_rows($link)>0)
		echo "deleted";
	 */
}

function get_cycle_names($link, $autoclave) {
	$cycle_names=array(
		'woo',
		'uh hmn',
		'yasss',
	);

	return $cycle_names;

}

function display_autoclave_types($link, $autoclave, $val=false) { 
	$cycle_names=get_cycle_names($link, $autoclave);
	echo array_to_dropdown($cycle_names, 'cycle_name', $val, "save_log(this);");
}

function confirm_delete($onclick="return false;") {
?>
	<div class="confirmation">
		<div class="delete" onclick="confirmation(this);">
			<?=icon('delete')?>
		</div>
		<div class="confirm">
			<a href="#" onclick="<?=$onclick?>">🗑︎</a>
			<span class="divide">|</span>
			<a href="#" onclick="confirmation_close(this);">X</a>
		</div>
	</div>
<?php
}

function edit_log($link, $id, $autoclaves=array(), $settings) {
	// save some time
	$numbersonly='onkeyup="this.value = this.value.replace(/[^0-9\.]/g,\'\');"';

	// get log
	$log=get_log($link, $id);
?>
	<td class="view">
		<div class="save" onclick="view_log(this);"><?=icon('done')?></div>
		<?=confirm_delete('delete_log(this);')?>
	</td>
	<td>
<?php
	$date = new DateTimeImmutable($log['datetime']);
	$datetime=$date->format('Y-m-d H:i');
?>
		<input class="datetime" onchange="save_log(this);" data-name="datetime" value="<?=$datetime?>">
	</td>
<?php
	if(count($autoclaves)) {
		echo ' <td>';
		$autoclave_list=array();
		foreach($autoclaves as $a)
			$autoclave_list[$a['id']]=(strlen($a['nickname'])?$a['nickname']:str_repeat("&nbsp;",18)."-");

		echo array_to_dropdown($autoclave_list, 'autoclave', $log['autoclave'], "save_log(this); display_log_cycle(this); automatic_log_cycle(this);");
		echo '</td>';
	}
?>
	<td>
		<div class="cycle">
<?php
	// if the autoclave isn't selected, don't allow cycle info
	$disabled=false;
	if(count($autoclaves) && !strlen($log['autoclave']))
		$disabled=true;
?>
			<label for="">#</label><input<?=$disabled?' disabled="disabled"':''?> type="number" <?=$numbersonly?> onchange="save_log(this);" data-name="cycle_no" value="<?=$log['cycle_no']?>">

			<label for=""><?=lang("Name")?></label>
			<div class="cycle_name">
<?php
	if(count($autoclaves)) {
		$found=false;
		echo '<select'.($disabled?' disabled="disabled"':'').' data-name="cycle_name" onchange="save_log(this); display_log_cycle_info(this);">';
		echo '<option></option>';

		echo '<optgroup class="cycles" label="'.lang('Cycles').'">';

		if(isset($log['autoclave']) && isset($autoclaves[$log['autoclave']]))
			foreach($autoclaves[$log['autoclave']]['cycles'] as $cycle) {
				$found_this=false;
				if($cycle['cycle_name']==$log['cycle_name'])
					$found=$found_this=true;
				echo '<option data-name="'.$cycle['cycle_name'].'" data-temp="'.$cycle['cycle_temp'].'" data-time="'.$cycle['cycle_time'].'" data-pressure="'.$cycle['cycle_pressure'].'"'.($found_this?' selected="selected"':'').'>'.$cycle['cycle_name'].'</option>';
			}
		echo '</optgroup>';
		//	}

		// custom cycles
		echo '<optgroup class="custom" label="'.lang('Custom').'">';
		echo '<option data-id="other">'.lang("_ _ _ _ _ _").'</option>';
		if(!$found && strlen($log['cycle_name']))
			echo '<option data-name="'.$log['cycle_name'].'" selected="selected">'.$log['cycle_name'].'</option>';
		echo '</optgroup>';
		echo '</select>';
	} else
		echo '<input data-name="cycle_name" onchange="save_log(this);" value="'.$log['cycle_name'].'" />';

?>
			</div>

			<label for=""><?=lang("Temp")?></label><input<?=$disabled?' disabled="disabled"':''?> type="number" <?=$numbersonly?> onchange="save_log(this);" data-name="cycle_temp" value="<?=$log['cycle_temp']?>">
			<label for=""><?=lang("Time")?></label><input<?=$disabled?' disabled="disabled"':''?>  type="number" <?=$numbersonly?> onchange="save_log(this);" data-name="cycle_time" value="<?=$log['cycle_time']?>">
			<label for=""><?=lang("Pressure")?></label><input<?=$disabled?' disabled="disabled"':''?>  type="number" <?=$numbersonly?> onchange="save_log(this);" data-name="cycle_pressure" value="<?=$log['cycle_pressure']?>">
		</div>
	</td>
<?php
	if(count($settings['operators'])) {
		echo '<td class="operator">';
		echo array_to_dropdown($settings['operators'], 'operator', $log['operator'], "save_log(this);", true /*blank first */, true /*allow other*/);

		// remember me
		$checked=isset($_SESSION['rememberme']) && $_SESSION['rememberme']==$log['operator'];

		echo '<input type="checkbox" onchange="rememberme(this);"'.($checked?' checked':''). '/><label>'.lang('Remember me').'</label>';
		echo '</td>';
	}
?>
	<td>
		<?=array_to_dropdown($settings['statuses'], 'status', $log['status'], "save_log(this);")?>
		<label><?=lang("Note")?></label>
		<input class="status_note" onchange="save_log(this);" data-name="status_note" value="<?=$log['status_note']?>">

	</td>
	<td class="contents">
		<div>
			<label><?=lang('Contents')?></label>
<?php
		if(count($settings['log_contents']))
			echo array_to_dropdown($settings['log_contents'],'contents', '' /*value */, "log_content(this);", true /*blank first*/, false /*allow other*/);

		echo '<textarea onchange="save_log(this);" data-name="contents" rows="4" cols="50">'.$log['contents'].'</textarea>';

?>
		<div>
<?php
	if(count($settings['log_verification'])) {
		echo '<div>';
		echo '<label>'.lang('Verification').'</label>';
		echo array_to_dropdown($settings['log_verification'], 'verification', $log['verification'], "save_log(this);");
		echo '</div>';
	}

?>
	</td>
	<td class="photos">
		<div class="add" onclick="save_log_add_photo(this);"><?=icon('add')?></div>
<?php
		display_log_photos($log['photo_id'], $log, $autoclaves, $edit=true);
?>
	</td>
<?php
}

function rememberme($operator) {
}

function add_log($link, $settings, $time=null, $autoclaves=array()) {
	$add_log=array();

	// get last log
	if(!count($autoclaves))
		$add_log=automatic_latest_log($link, $settings);

	// remember me
	if(isset($_SESSION['rememberme']) && strlen($_SESSION['rememberme']))
		$add_log['operator']=$_SESSION['rememberme'];

	// insert new log
	$q="INSERT into `logs` SET `owner` = '".mysqli_real_escape_string($link, $_SESSION['id'])."'"; //, `photo_id` = UUID_SHORT()";

	// include time
	if($time!==null)
		$q.=", `datetime` = '".mysqli_real_escape_string($link, $time)."'";

	foreach($add_log as $k=>$v)
		$q.=", `".mysqli_real_escape_string($link, $k)."` = '".mysqli_real_escape_string($link, $v)."'";

	$res=mysqli_query($link, $q);

	// grab the last inserted and display
	$last_id = mysqli_insert_id($link);
	$log = get_log($link, $last_id);

	// show editable row
	echo '<tr class="editing new_log" data-id="'.$last_id.'">';
	edit_log($link, $last_id, $autoclaves, $settings);
	echo '</tr>';
?>
	<script language="javascript">
	datetime($('tr[data-id="<?=$last_id?>"] .datetime'));
	</script>
<?php
}

function random_name() {
	$names=array(
		'Joe',
		'Mclovin',
		'Jeff',
		'Sarah',
		'Phelony',
		'Jammy',
		'Bacardi',
		'Lasagna',
		'Moon Unit',
		'Lightning McSteam',
		'Velveeta',
		'Beberly',
		'Taylor-Tots',
		'Boomquifa',
		'Princess',
		'Steri-mc-steam-face',
		'Tu Morrow',
		'River',
		'Adolf',
		'Anous',
		'Cletus',
		'Lucifer',
		'Fillion',
		'Sadman',
		'Glorified Pressurecooker',
		'Easy Bake Oven',
		'Stormy',
		'Spartacus',
		'Elizabreath',
		'Sabastian',
	);

	echo $names[array_rand($names)];
}

function icon($name, $text=null, $only_icon=false) {
	// unicode http://xahlee.info/comp/unicode_index.html?q=
	$icon=$string="";

	switch($name) {
	case 'blank': $icon="&nbsp;"; break;
	case 'edit': $icon="✎"; break;
	case 'refresh': $icon="߷"; break;
	case 'done': $icon="✓"; break;
	case 'add': $icon="+"; break;
	case 'lizard': $icon="🦎"; break;
	case 'logs': $icon="🪵"; break;
	case 'settings': $icon="⚙"; break;
	case 'user': $icon="👽"; break;
	case 'delete': $icon="🗑︎"; break;
	case 'yes': $icon="✓"; break;
	case 'no': $icon="x"; break;
	case 'move': $icon="↕"; break;
	case 'test': $icon="⌖"; break;
	case 'load': $icon="🔗"; break;
	case 'automatic': $icon="🪄"; break;
	case 'donate': $icon="💸" ; break;
	case 'random': $icon="🎲"; break;
	case 'download': $icon="💾"; break;
	case 'print': $icon='⎙'; break;
	default: $icon=$name; break;
	}

	if($only_icon)
		return $icon;

	$string='<span class="icon icon_'.$name.'">'.$icon.'</span>';

	if($text)
		$string.='<span class="icontext">'.$text.'</span>';
	return $string;
}

?>
