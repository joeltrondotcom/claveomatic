<!--<?=$nerd?>-->

<html>
	<head>
		<title><?=$ui['title']?></title>

		<meta name="viewport" content="initial-scale=<?=$ui['initial-scale']?>, maximum-scale=<?=$ui['maximum-scale']?>, user-scalable=<?=$ui['user-scale']?>" />
		<meta name="HandheldFriendly" content="true"/>
		<meta name="MobileOptimized" content="width" />
		<meta name="mobile-web-app-capable" content="yes">

		<link rel="apple-touch-icon" sizes="180x180" href="claveomatic_180.png">
		<link rel="shortcut_icon" type="image/png" sizes="32x32" href="claveomatic_32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="claveomatic_16.png">
		<link rel="manifest" href="manifest.json">

		<link rel="stylesheet" href="styles.css?rand=<?=rand()?>">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.3.7/jquery.datetimepicker.min.css" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.css">
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
		<script type="text/javascript" src="https://htmlguyllc.github.io/jConfirm/jConfirm.js"></script>

		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.4/jquery-ui.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.js"></script>
		<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/filereader@0.10.3/FileReader.min.js"></script>
		<script type="text/javascript" src="https://pagination.js.org/dist/2.6.0/pagination.min.js"></script>
<script type="text/javascript" src="script.js?rand=<?=rand()?>"></script>
	</head>
	<body<?=$logged_in?' class="logged_in"':''?>>
		<div class="head">
			<a href="/"><img class="logo" src="<?=$ui['logo']?>" alt="<?=$ui['title']?>" /><?=$ui['site_title']?></a>
			<div class="nav"><?=isset($ui['nav'])?$ui['nav']:''?></div>
		</div>
		<div class="main<?=$ui['page']?' page_'.$ui['page']:''?>"><?=$ui['main']?></div>
		<div class="page_bottom"><?=$ui['page_bottom']?></div>
		<div class="foot"><?=$ui['foot']?></div>

		<div class="notifications">
<?php
// notification floaties
foreach($ui['notifications'] as $name=>$note) {
	if(!isset($settings['notification_hide_'.$name]))
		echo '<div class="notification notification_'.$name.'" data-name="'.$name.'"><div onclick="notification_close(this);" class="notification_x">X</div><span>'.ucfirst(lang($name)).'</span>'.lang($note).'</div>';
}
?>
		</div>

		<div class="popup">
			<div class="popup_box">
				<div onclick="popup_close();" class="popup_x">X</div>
				<div class="popup_title"></div>
				<div class="popup_content"></div>

				<div class="popup_confirm">
					<div class="popup_y"><?=icon('yes')?></div>
					<div class="popup_n"><?=icon('no')?></div>
				</div>
			</div>
		</div>
	</body>
</html>
