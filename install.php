<?php
/*
=====================================================
 DataLife Engine - by SoftNews Media Group
-----------------------------------------------------
 http://dle-news.ru/
-----------------------------------------------------
 Copyright (c) 2004-2022 SoftNews Media Group
=====================================================
 This code is protected by copyright
=====================================================
 File: install.php
-----------------------------------------------------
 Use: Script installation
=====================================================
*/
session_start();

error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);
ini_set('error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);

header("Content-type: text/html; charset=utf-8");

define('DATALIFEENGINE', true);
define('ROOT_DIR', dirname(__FILE__));
define('ENGINE_DIR', ROOT_DIR . '/engine');

require_once(ENGINE_DIR . '/inc/include/functions.inc.php');

$is_loged_in = false;
$selected_language = 'Russian';

$_REQUEST['action'] = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

$url = explode(basename($_SERVER['PHP_SELF']), $_SERVER['PHP_SELF']);
$url = reset($url);
$_IP = get_ip();

if (isSSL()) $url  = "https://" . $_SERVER['HTTP_HOST'] . $url;
else $url  = "http://" . $_SERVER['HTTP_HOST'] . $url;

if (isset($_POST['selected_language'])) {

	$_POST['selected_language'] = totranslit($_POST['selected_language'], false, false);

	if ($_POST['selected_language'] and @is_dir(ROOT_DIR . '/language/' . $_POST['selected_language'])) {

		$selected_language = $_POST['selected_language'];
		set_cookie("selected_language", $selected_language, 365);
	}
	
} elseif (isset($_COOKIE['selected_language'])) {

	$_COOKIE['selected_language'] = totranslit($_COOKIE['selected_language'], false, false);

	if ($_COOKIE['selected_language'] != "" and @is_dir(ROOT_DIR . '/language/' . $_COOKIE['selected_language'])) {
		$selected_language = $_COOKIE['selected_language'];
	}
	
}

include_once (ROOT_DIR . '/language/' . $selected_language . '/adminpanel.lng');
include_once (ROOT_DIR . '/language/' . $selected_language . '/install.lng');

$skin_header = <<<HTML
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>{$lang['install_1']}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="HandheldFriendly" content="true">
	<meta name="format-detection" content="telephone=no">
	<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=device-width">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<link href="engine/skins/fonts/fontawesome/styles.min.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="engine/skins/stylesheets/application.css" media="screen" rel="stylesheet" type="text/css" />
	<script src="engine/skins/javascripts/application.js"></script>
</head>
<body class="no-theme">
<script>
	var dle_act_lang   = [];
	var cal_language   = '{$lang['wysiwyg_language']}';
	var filedefaulttext= '';
	var filebtntext    = '';
</script>
<div class="navbar navbar-inverse bg-primary-700">
	<div class="navbar-header">
		<a class="navbar-brand" href="#">{$lang['install_1']}</a>
	</div>
</div>
<div class="page-container">
	<div class="page-content">
		<div class="col-md-8 col-md-offset-2 mt-20">
<!--MAIN area-->
HTML;


$skin_footer = <<<HTML
	 <!--MAIN area-->
	</div>
	</div>
</div>
</body>
</html>
HTML;

function msgbox($text, $back = false) {
	global $lang, $skin_header, $skin_footer;

	if ($back) {
		$back = "onclick=\"history.go(-1); return false;\"";
		$lang['install_2'] = $lang['install_3'];
	} else {
		$back = "";
	}

	echo $skin_header;

	echo <<<HTML
<form method="post">
<div class="panel panel-default">
	<div class="panel-heading">
	{$lang['install_4']}
	</div>
	<div class="panel-body">
		{$text}
	</div>
	<div class="panel-footer">
	<button type="submit" {$back} class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-arrow-circle-o-right position-left"></i>{$lang['install_2']}</button>
	</div>
</div>
</form>
HTML;

	echo $skin_footer;

	exit();
}

function GetRandInt($max) {
	
	if (function_exists('openssl_random_pseudo_bytes')) {
		do {
			$result = (int)floor($max * (hexdec(bin2hex(openssl_random_pseudo_bytes(4))) / 0xffffffff));
		} while ($result == $max);
	} else {

		$result = mt_rand(0, $max);
	}

	return $result;
}

function generate_auth_key() {
	
	$arr = array(
		'a', 'b', 'c', 'd', 'e', 'f',
		'g', 'h', 'i', 'j', 'k', 'l',
		'm', 'n', 'o', 'p', 'r', 's',
		't', 'u', 'v', 'x', 'y', 'z',
		'A', 'B', 'C', 'D', 'E', 'F',
		'G', 'H', 'I', 'J', 'K', 'L',
		'M', 'N', 'O', 'P', 'R', 'S',
		'T', 'U', 'V', 'X', 'Y', 'Z',
		'1', '2', '3', '4', '5', '6',
		'7', '8', '9', '0', '.', ',',
		'(', ')', '[', ']', '!', '?',
		'&', '^', '%', '@', '*', ' ',
		'<', '>', '/', '|', '+', '-',
		'{', '}', '`', '~', '#', ';',
		'/', '|', '=', ':', '`'
	);

	$key = "";
	for ($i = 0; $i < 64; $i++) {
		$index = GetRandInt(count($arr) - 1);
		$key .= $arr[$index];
	}
	return $key;
}

function folders_check_chmod($dir,  $bad_files = array()) {
	
	if (!is_writable($dir) OR !is_dir($dir)) {
		$folder = str_replace(ROOT_DIR, "", $dir);
		$bad_files[] = $folder . "/";
	}

	if ($dh = @opendir($dir)) {

		while (false !== ($file = readdir($dh))) {

			if ($file == '.' or $file == '..' or $file == '.svn' or $file == '.DS_store') {
				continue;
			}

			if (is_dir($dir . "/" . $file)) {

				$bad_files = folders_check_chmod($dir . "/" . $file, $bad_files);
			}
		}
	}

	return $bad_files;
}

if ($_REQUEST['action'] and !isset($_SESSION['dle_install'])) {
	msgbox("{$lang['install_5']} <br><br><a href=\"{$url}install.php\">{$url}install.php</a>");
}

if ( file_exists(ENGINE_DIR.'/data/config.php') ) {

	msgbox( $lang['install_6'] );

}

if ($_REQUEST['action'] == "eula") {

	echo $skin_header;

	echo <<<HTML
<form id="check-eula" method="get" action="">
<input type=hidden name=action value="function_check">
<script language='javascript'>
function check_eula(){

	if( document.getElementById( 'eula' ).checked == true )
	{
		return true;
		
	} else {
	
		DLEalert( '{$lang['install_16']}', '{$lang['all_info']}' );
		return false;
	}
};
document.getElementById( 'check-eula' ).onsubmit = check_eula;
</script>
<div class="panel panel-default">
	<div class="panel-heading">
	 {$lang['install_11']}
	</div>
	<div class="panel-body">
		{$lang['install_12']}
		<br><br>
		<div style="height: 300px; border: 1px solid #76774C; background-color: #FDFDD3; padding: 5px; overflow: auto;">{$lang['install_13']}</div>
		<div class="checkbox"><label><input type="checkbox" name="eula" id="eula" class="icheck">{$lang['install_14']}</label></div>
	</div>
	<div class="panel-footer">
	<button type="submit" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-arrow-circle-o-right position-left"></i>{$lang['install_15']}</button>
	</div>
</div>
</form>
HTML;

	echo $skin_footer;
	
} elseif ($_REQUEST['action'] == "function_check") {

	$message = <<<HTML
<form method="get" action="">
<input type=hidden name="action" value="function_check">
<div class="panel panel-default">
	<div class="panel-heading">
	{$lang['install_17']}
	</div>
	<div class="table-responsive">
<table class="table table-striped table-xs">
<thead>
<th width="300">{$lang['install_18']}</th>
<th colspan="2">{$lang['install_19']}</th>
</thead>
HTML;

	$errors = false;

	if (version_compare(phpversion(), '7.2', '<')) {
		$status = '<span class="text-danger"><b>' . $lang['install_20'] . '</b></span>';
		$errors = true;
	} else {
		$status = '<span class="text-success"><b>' . $lang['install_21'] . '</b></span>';
	}

	$message .= "<tr>
		 <td>{$lang['install_22']}</td>
		 <td colspan=2>{$status}</td>
		 </tr>";

	if (function_exists('mysqli_connect')) {
		$status = '<span class="text-success"><b>' . $lang['install_21'] . '</b></span>';
	} else {
		$status = '<span class="text-danger"><b>' . $lang['install_20'] . '</b></span>';
		$errors = true;
	}

	$message .= "<tr>
		 <td>{$lang['install_23']}</td>
		 <td colspan=2>{$status}</td>
		 </tr>";

	if (class_exists('ZipArchive')) {
		$status = '<span class="text-success"><b>' . $lang['install_21'] . '</b></span>';
	} else {
		$status = '<span class="text-danger"><b>' . $lang['install_20'] . '</b></span>';
		$errors = true;
	}

	$message .= "<tr>
		 <td>{$lang['install_24']}</td>
		 <td colspan=2>{$status}</td>
		 </tr>";

	if (function_exists('mb_convert_encoding')) {
		$status = '<span class="text-success"><b>' . $lang['install_21'] . '</b></span>';
	} else {
		$status = '<span class="text-danger"><b>' . $lang['install_20'] . '</b></span>';
		$errors = true;
	}

	$message .= "<tr>
		 <td>{$lang['install_25']}</td>
		 <td colspan=2>{$status}</td>
		 </tr>";

	$message .=  <<<HTML
</table>
	<div class="panel-body">
	{$lang['install_26']}
	</div>
	<div class="panel-footer">
	<button onclick="location.reload(true); return false;" class="btn bg-danger btn-sm btn-raised position-left"><i class="fa fa-refresh position-left"></i>{$lang['install_92']}</button>
	</div>

	</div>
</div></form>
HTML;

	if ($errors) {
		echo $skin_header . $message . $skin_footer;
		die();
	}

	$no_access = folders_check_chmod(ROOT_DIR."/uploads" );
	$no_access = array_merge($no_access, folders_check_chmod(ROOT_DIR."/backup" ) );
	$no_access = array_merge($no_access, folders_check_chmod(ROOT_DIR."/engine/data" ) );
	$no_access = array_merge($no_access, folders_check_chmod(ROOT_DIR."/engine/cache" ) );
	$no_access = array_merge($no_access, folders_check_chmod(ROOT_DIR."/templates" ) );

	if (count($no_access)) {

		$message = <<<HTML
<form method="get" action="">
<input type=hidden name="action" value="function_check">
<div class="panel panel-default">
	<div class="panel-heading">
	{$lang['install_27']}
	</div>
	<div class="panel-body">
HTML;

		$errors = true;

		$message .= <<<HTML
			<div>{$lang['upgr_file_2']}</div>
			<div class="table-responsive pre-scrollable">
			<table class="table table-striped table-xs table-framed"><thead><tr><th>{$lang['upgr_file']}</th><th style="width:150px;">CHMOD</th></thead><tbody>
HTML;
		foreach ($no_access as $file) {
			$message .= "<tr><td>$file</td><td><span class=\"text-danger\">{$lang['upgr_file_1']}</span></td></tr>";
		}

		$message .= <<<HTML
			</tbody></table></div></div>
	<div class="panel-footer">
		<button onclick="location.reload(true); return false;" class="btn bg-danger btn-sm btn-raised position-left"><i class="fa fa-refresh position-left"></i>{$lang['install_51']}</button>
	</div>
</div>
</form>
HTML;
	}

	if ($errors) {
		echo $skin_header . $message . $skin_footer;
		die();
	}


	if (!is_dir(ROOT_DIR . "/uploads/posts")) {
		@mkdir(ROOT_DIR . "/uploads/posts", 0777, true);
		@chmod(ROOT_DIR . "/uploads/posts", 0777);
	}

	if (!is_dir(ROOT_DIR . "/uploads/files")) {
		@mkdir(ROOT_DIR . "/uploads/files", 0777, true);
		@chmod(ROOT_DIR . "/uploads/files", 0777);
	}

	if (!is_dir(ROOT_DIR . "/uploads/fotos")) {
		@mkdir(ROOT_DIR . "/uploads/fotos", 0777, true);
		@chmod(ROOT_DIR . "/uploads/fotos", 0777);
	}

	if (!is_dir(ROOT_DIR . "/uploads/icons")) {
		@mkdir(ROOT_DIR . "/uploads/icons", 0777, true);
		@chmod(ROOT_DIR . "/uploads/icons", 0777);
	}

	if (!is_dir(ROOT_DIR . "/uploads/shared")) {
		@mkdir(ROOT_DIR . "/uploads/shared", 0777, true);
		@chmod(ROOT_DIR . "/uploads/shared", 0777);
	}

	if (!is_dir(ROOT_DIR . "/engine/cache/system")) {
		@mkdir(ROOT_DIR . "/engine/cache/system", 0777);
		@chmod(ROOT_DIR . "/engine/cache/system", 0777);
	}

	header( "Location: ?action=dbconfig" );
	die();

} elseif($_REQUEST['action'] == "userconfig") {

	if (!file_exists(ENGINE_DIR . '/data/dbconfig.php')) {

		header("Location: ?action=dbconfig");
		die();
	}

	echo $skin_header;

	echo <<<HTML
<form method="post" action="?action=doinstall" class="form-horizontal" id="formdata" name="formdata">
<div class="panel panel-default">
	<div class="panel-heading">
	{$lang['install_28']}
	</div>
	<div class="panel-body">
	<div class="form-group">
		<label class="control-label col-md-12"><b>{$lang['install_39']}</b></label>
	</div>
	<div class="form-group">
		<label class="control-label col-md-3">{$lang['install_40']}</label>
		<div class="col-md-9">
		<input type="text" class="classic" style="width:220px;" name="reg_username">
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-md-3">{$lang['install_33']}</label>
		<div class="col-md-9">
		<input type="password" class="classic" style="width:220px;" name="reg_password1">
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-md-3">{$lang['install_41']}</label>
		<div class="col-md-9">
		<input type="password" class="classic" style="width:220px;" name="reg_password2">
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-md-3">E-mail:</label>
		<div class="col-md-9">
		<input type="text" class="classic" style="width:220px;" name="reg_email">
		</div>
	</div>
	</div>
	<div class="panel-footer">
	<button type="submit" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-arrow-circle-o-right position-left"></i>{$lang['install_15']}</button>
	</div>
</div></form>

<script>
	jQuery(function($) {

		$('#formdata').submit(function() {

			if(document.formdata.reg_username.value == '' || document.formdata.reg_password1.value == '' || document.formdata.reg_password2.value == '' || document.formdata.reg_email.value == '' ) {

				DLEalert('{$lang['install_42']}', '{$lang['all_info']}');
				return false;

			} else if (document.formdata.reg_password1.value != document.formdata.reg_password2.value ) {
				DLEalert('{$lang['install_43']}', '{$lang['all_info']}');
				return false;
			} else {
				return true;
			}

		});

	});
</script>

HTML;

	echo $skin_footer;

} elseif($_REQUEST['action'] == "dbconfig") {

	echo $skin_header;

	echo <<<HTML
<form method="post" action="" class="form-horizontal" id="formdata" name="formdata">
<input type=hidden name="action" value="checkdb">
<div class="panel panel-default">
	<div class="panel-heading">
	 {$lang['install_28']}
	</div>
	<div class="panel-body">
	<div class="form-group">
		<label class="control-label col-md-12"><b>{$lang['install_29']}</b></label>
	</div>
	<div class="form-group">
		<label class="control-label col-md-2">{$lang['install_30']}</label>
		<div class="col-md-10">
		<input type="text" class="classic" style="width:220px;" name="dbhost" value="localhost">
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-md-2">{$lang['install_31']}</label>
		<div class="col-md-10">
		<input type="text" class="classic" style="width:220px;" name="dbname">
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-md-2">{$lang['install_32']}</label>
		<div class="col-md-10">
		<input type="text" class="classic" style="width:220px;" name="dbuser">
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-md-2">{$lang['install_33']}</label>
		<div class="col-md-10">
		<input type="text" class="classic" style="width:220px;" name="dbpasswd">
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-md-2">{$lang['install_34']}</label>
		<div class="col-md-10">
		<input type="text" class="classic" style="width:220px;" name="dbprefix" value="dle"> <span class="text-size-small text-muted">{$lang['install_35']}</span>
		</div>
	</div>
	</div>
	<div class="panel-footer">
	<button type="submit" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-arrow-circle-o-right position-left"></i>{$lang['install_15']}</button>
	</div>
</div></form>
<script>
	jQuery(function($) {

$('#formdata').submit(function() {

	if(document.formdata.dbhost.value == '' || document.formdata.dbname.value == '' || document.formdata.dbuser.value == '' ) {

		DLEalert('{$lang['install_36']}', '{$lang['all_info']}');
		return false;

	}

	var formData = new FormData($('#formdata')[0]);

	ShowLoading('');

	$.ajax({
		url: "install.php?action=checkdb",
		data: formData,
		processData: false,
		contentType: false,
		type: 'POST',
		dataType: 'json',
		success: function(data) {
			HideLoading('');

			if (data) {

				if (data.status == "ok") {

					setTimeout("window.location = '?action=userconfig	'", 1000 );

				} else {

					DLEalert(data.text, '{$lang['all_info']}');

				}

			}
		},
		error: function(jqXHR, textStatus, errorThrown ) {
		
				HideLoading('');

				var error_status = '';

				if (jqXHR.status < 200 || jqXHR.status >= 300) {
					error_status = 'HTTP Error: ' + jqXHR.status;
				} else {
					error_status = 'Invalid JSON: ' + jqXHR.responseText;
				}

				DLEalert(error_status, '{$lang['all_info']}');
				
		}
		
	});

	return false;
});

		
	});
</script>

HTML;

	echo $skin_footer;

} elseif($_REQUEST['action'] == "checkdb") {

		$_POST['dbhost'] = explode(":", $_POST['dbhost']);

		if (isset($_POST['dbhost'][1])) {

			$mysqli = @new mysqli($_POST['dbhost'][0], $_POST['dbuser'], $_POST['dbpasswd'], $_POST['dbname'], $_POST['dbhost'][1]);

		} else {

			$mysqli = @new mysqli($_POST['dbhost'][0], $_POST['dbuser'], $_POST['dbpasswd'], $_POST['dbname']);

		}

		if ($mysqli->connect_error) {

			echo json_encode(array('status' => 'error', 'text' => $lang['install_37'].' ' . $mysqli->connect_error), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			die();

		}

		$_POST['dbhost'] = implode(":", $_POST['dbhost']);

		$result = $mysqli->query("SELECT VERSION() AS `version`");
		$row = $result->fetch_assoc();

		if( version_compare($row['version'], '5.5.3', '<') ) {

			echo json_encode(array('status' => 'error', 'text' => $lang['install_38']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			die();

		}

		$dbhost = str_replace ('"', '\"', str_replace ("$", "\\$", $_POST['dbhost']) );
		$dbname = str_replace ('"', '\"', str_replace ("$", "\\$", $_POST['dbname']) );
		$dbuser = str_replace ('"', '\"', str_replace ("$", "\\$", $_POST['dbuser']) );
		$dbpasswd = str_replace ('"', '\"', str_replace ("$", "\\$", $_POST['dbpasswd']) );
		$dbprefix = str_replace ('"', '\"', str_replace ("$", "\\$", $_POST['dbprefix']) );
		$auth_key = generate_auth_key();

$dbconfig = <<<HTML
<?PHP

define ("DBHOST", "{$dbhost}");

define ("DBNAME", "{$dbname}");

define ("DBUSER", "{$dbuser}");

define ("DBPASS", "{$dbpasswd}");

define ("PREFIX", "{$dbprefix}");

define ("USERPREFIX", "{$dbprefix}");

define ("COLLATE", "utf8mb4");

define('SECURE_AUTH_KEY', '{$auth_key}');

\$db = new db;

?>
HTML;

		$con_file = fopen("engine/data/dbconfig.php", "w+");
		fwrite($con_file, $dbconfig);
		fclose($con_file);
		@chmod("engine/data/dbconfig.php", 0666);

		echo json_encode( array('status' => 'ok') );
		die();

} elseif($_REQUEST['action'] == "installtemplate") {

	if (!file_exists(ROOT_DIR . '/templates/default_templates.zip')) {
		echo json_encode(array(
			'status' => 'ok',
			'offset' => 1,
			'total' => 1
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		die();
	}

	include_once(ENGINE_DIR . '/classes/zipextract.class.php');

	$done = 0;

	$fs = new dle_zip_extract(ROOT_DIR . '/templates/default_templates.zip');
	$fs->SetRootZipArchive($selected_language . "/");
	$fs->SetFilesRoot(ROOT_DIR . '/templates/');
	$fs->folder_permission = 0777;
	$fs->file_permission = 0666;
	$total = $fs->zip_numfiles;

	$offset = intval($_POST['offset']);

	$done = $fs->ExtractZipArchive($offset, 30);

	if ($done) {
		$offset = $offset + $done;
	} else {
		$offset = $total;
	}

	echo json_encode(array(
		'status' => 'ok',
		'offset' => $offset,
		'total' => $total
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	die();

} elseif($_REQUEST['action'] == "doinstall") {

	if (!file_exists(ENGINE_DIR . '/data/dbconfig.php')) {

		header("Location: ?action=dbconfig");
		die();
	}

	if (!$_POST['reg_username'] or !$_POST['reg_email'] or !$_POST['reg_password1'] or $_POST['reg_password1'] != $_POST['reg_password2']) {
		msgbox($lang['install_42'], "history.go(-1)");
	}

	if (preg_match("/[\||\'|\<|\>|\[|\]|\"|\!|\?|\$|\@|\#|\/|\\\|\&\~\*\{\+]/", $_POST['reg_username'])) {
		msgbox($lang['install_44'], "history.go(-1)");
	}

	$not_allow_symbol = array("\x22", "\x60", "\t", '\n', '\r', "\n", "\r", '\\', ",", "/", "¬", "#", ";", ":", "~", "[", "]", "{", "}", ")", "(", "*", "^", "%", "$", "<", ">", "?", "!", '"', "'", " ", "&");

	$reg_email = trim(str_replace($not_allow_symbol, '', strip_tags(stripslashes($_POST['reg_email']))));
	$reg_username = $_POST['reg_username'];
	$reg_password = password_hash($_POST['reg_password1'], PASSWORD_DEFAULT);

	$_SESSION['userconfig'] = json_encode(array(
		'username' => $reg_username,
		'userpass' => $reg_password,
		'email' => $reg_email
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	echo $skin_header;

	echo <<<HTML
		<div class="panel panel-default">
			<div class="panel-heading">
			{$lang['install_45']}
			</div>
			<div class="panel-body">
				<div class="progress"><div id="progressbar" class="progress-bar progress-blue" style="width:0%;"><span></span></div></div>
				<div class="text-size-small" id="status"></div>
			</div>
			<div class="panel-body text-muted text-size-small">
			{$lang['install_46']}
			</div>
			<div class="panel-footer">
				<button id="button" type="button" class="btn bg-teal btn-sm btn-raised" disabled><i class="fa fa-forward position-left"></i>{$lang['upgr_next']}</button>
			</div>
		</div>
<script>

	function install_templates(offset)  {

		$.post("?action=installtemplate", { offset: offset },

			function(data){

				if (data) {

					if (data.status == "ok") {

						$('#status').text('{$lang['install_47']} ' + data.offset + ' {$lang['install_48']} ' + data.total );

						var proc = Math.round( (100 * data.offset) / data.total );

						if ( proc > 100 ) proc = 100;

						$('#progressbar').css( "width", proc + '%' );

						if (data.offset >= data.total) {

							$('#status').text('{$lang['install_49']} ' );
							setTimeout("install_db(0)", 300 );

						} else { setTimeout("install_templates(" + data.offset + ")", 300 ); }


					}

				}

			}, "json").fail(function(jqXHR, textStatus, errorThrown ) {

				var error_status = '';

				if (jqXHR.status < 200 || jqXHR.status >= 300) {
					error_status = 'HTTP Error: ' + jqXHR.status;
				} else {
					error_status = 'Invalid JSON: ' + jqXHR.responseText;
				}

				$('#status').append('<div class="alert alert-danger alert-styled-left alert-bordered">' + error_status + '</div>');
				$('#button').attr("disabled", false);

		});

		return false;

	}

	function install_db(offset)  {

		$.post("?action=docreatedb", { offset: offset },

			function(data){

				if (data) {

					if (data.status == "ok") {

						$('#status').text('{$lang['install_49']} ' + data.offset + ' {$lang['install_48']} ' + data.total );

						var proc = Math.round( (100 * data.offset) / data.total );

						if ( proc > 100 ) proc = 100;

						$('#progressbar').css( "width", proc + '%' );

						if (data.offset >= data.total) {

							$('#status').text('{$lang['install_50']} ' );
							setTimeout("save_settings()", 300 );

						} else { setTimeout("install_db(" + data.offset + ")", 300 ); }


					}

				}

			}, "json").fail(function(jqXHR, textStatus, errorThrown ) {

				var error_status = '';

				if (jqXHR.status < 200 || jqXHR.status >= 300) {
					error_status = 'HTTP Error: ' + jqXHR.status;
				} else {
					error_status = 'Invalid JSON: ' + jqXHR.responseText;
				}

				$('#status').append('<div class="alert alert-danger alert-styled-left alert-bordered">' + error_status + '</div>');
				$('#button').attr("disabled", false);

		});

		return false;

	}

	function save_settings()  {

		$.post("?action=dosaveconfig", { action: 'dosaveconfig' },

			function(data){

				if (data) {

					if (data.status == "ok") {
						setTimeout("window.location = '{$url}'", 1000 );
					}

				}

			}, "json").fail(function(jqXHR, textStatus, errorThrown ) {

				var error_status = '';

				if (jqXHR.status < 200 || jqXHR.status >= 300) {
					error_status = 'HTTP Error: ' + jqXHR.status;
				} else {
					error_status = 'Invalid JSON: ' + jqXHR.responseText;
				}

				$('#status').append('<div class="alert alert-danger alert-styled-left alert-bordered">' + error_status + '</div>');
				$('#button').attr("disabled", false);

		});

		return false;

	}

	$(function() {

		$('#status').text('{$lang['install_47']}' );
		setTimeout("install_templates(0)", 300 );
	});
</script>

HTML;

	echo $skin_footer;

} elseif($_REQUEST['action'] == "dosaveconfig") {

	if ( !isset($_SESSION['userconfig']) ) {

		die("SESSION data not found");

	}

	$timezone = date_default_timezone_get();

	$timezones = array('Pacific/Midway','US/Samoa','US/Hawaii','US/Alaska','US/Pacific','America/Tijuana','US/Arizona','US/Mountain','America/Chihuahua','America/Mazatlan','America/Mexico_City','America/Monterrey','US/Central','US/Eastern','US/East-Indiana','America/Lima','America/Caracas','Canada/Atlantic','America/La_Paz','America/Santiago','Canada/Newfoundland','America/Buenos_Aires','America/Godthab','Atlantic/Stanley','Atlantic/Azores','Africa/Casablanca','Europe/Dublin','Europe/Lisbon','Europe/London','Europe/Amsterdam','Europe/Belgrade','Europe/Berlin','Europe/Bratislava','Europe/Brussels','Europe/Budapest','Europe/Copenhagen','Europe/Madrid','Europe/Paris','Europe/Prague','Europe/Rome','Europe/Sarajevo','Europe/Stockholm','Europe/Vienna','Europe/Warsaw','Europe/Zagreb','Europe/Athens','Europe/Bucharest','Europe/Helsinki','Europe/Istanbul','Asia/Jerusalem','Europe/Kiev','Europe/Minsk','Europe/Riga','Europe/Sofia','Europe/Tallinn','Europe/Vilnius','Asia/Baghdad','Asia/Kuwait','Africa/Nairobi','Asia/Tehran','Europe/Kaliningrad','Europe/Moscow','Europe/Volgograd','Europe/Samara','Asia/Baku','Asia/Muscat','Asia/Tbilisi','Asia/Yerevan','Asia/Kabul','Asia/Yekaterinburg','Asia/Tashkent','Asia/Kolkata','Asia/Kathmandu','Asia/Almaty','Asia/Novosibirsk','Asia/Jakarta','Asia/Krasnoyarsk','Asia/Hong_Kong','Asia/Kuala_Lumpur','Asia/Singapore','Asia/Taipei','Asia/Ulaanbaatar','Asia/Urumqi','Asia/Irkutsk','Asia/Seoul','Asia/Tokyo','Australia/Adelaide','Australia/Darwin','Asia/Yakutsk','Australia/Brisbane','Pacific/Port_Moresby','Australia/Sydney','Asia/Vladivostok','Asia/Sakhalin','Asia/Magadan','Pacific/Auckland','Pacific/Fiji');

	if ( !in_array($timezone, $timezones) ) {
		$timezone = "Europe/Moscow";
		date_default_timezone_set ( $timezone );
	}
	
	$allow_alt_url = (stripos(PHP_SAPI, 'apache') !== false ? '1' : '0');

	$userconfig = json_decode($_SESSION['userconfig'], true);

$config = <<<HTML
<?PHP

//System Configurations

\$config = array (

'version_id' => "15.3",

'home_title' => "DataLife Engine",

'http_home_url' => "{$url}",

'charset' => "utf-8",

'admin_mail' => "{$userconfig['email']}",

'description' => "{$lang['install_89']}",

'keywords' => "DataLife, Engine, CMS",

'date_adjust' => "{$timezone}",

'site_offline' => "0",

'allow_alt_url' => "{$allow_alt_url}",

'langs' => "{$selected_language}",

'skin' => "Default",

'allow_gzip' => "0",

'allow_admin_wysiwyg' => "2",

'allow_static_wysiwyg' => "2",

'news_number' => "10",

'smilies' => "bowtie,smile,laughing,blush,smiley,relaxed,smirk,heart_eyes,kissing_heart,kissing_closed_eyes,flushed,relieved,satisfied,grin,wink,stuck_out_tongue_winking_eye,stuck_out_tongue_closed_eyes,grinning,kissing,stuck_out_tongue,sleeping,worried,frowning,anguished,open_mouth,grimacing,confused,hushed,expressionless,unamused,sweat_smile,sweat,disappointed_relieved,weary,pensive,disappointed,confounded,fearful,cold_sweat,persevere,cry,sob,joy,astonished,scream,tired_face,angry,rage,triumph,sleepy,yum,mask,sunglasses,dizzy_face,imp,smiling_imp,neutral_face,no_mouth,innocent",

'timestamp_active' => "j-m-Y, H:i",

'news_sort' => "date",

'news_msort' => "DESC",

'hide_full_link' => "0",

'allow_site_wysiwyg' => "2",

'allow_comments' => "1",

'comm_nummers' => "30",

'comm_msort' => "ASC",

'flood_time' => "30",

'auto_wrap' => "80",

'timestamp_comment' => "j F Y H:i",

'allow_comments_wysiwyg' => "2",

'allow_registration' => "1",

'allow_cache' => "0",

'allow_votes' => "1",

'allow_topnews' => "1",

'allow_read_count' => "1",

'allow_calendar' => "1",

'allow_archives' => "1",

'files_allow' => "1",

'files_count' => "1",

'reg_group' => "4",

'registration_type' => "0",

'allow_sec_code' => "1",

'allow_skin_change' => "1",

'max_users' => "0",

'max_users_day' => "0",

'max_up_size' => "200",

'max_image_days' => "2",

'allow_watermark' => "1",

'max_watermark' => "150",

'max_image' => "200",

'jpeg_quality' => "85",

'files_antileech' => "1",

'allow_banner' => "1",

'log_hash' => "0",

'show_sub_cats' => "1",

'tag_img_width' => "0",

'mail_metod' => "php",

'smtp_host' => "localhost",

'smtp_port' => "25",

'smtp_user' => "",

'smtp_pass' => "",

'mail_bcc' => "0",

'speedbar' => "1",

'image_align' => "center",

'ip_control' => "1",

'cache_count' => "0",

'related_news' => "1",

'no_date' => "1",

'mail_news' => "1",

'mail_comments' => "1",

'admin_path' => "admin.php",

'rss_informer' => "1",

'allow_cmod' => "0",

'max_up_side' => "0",

'short_rating' => "1",

'full_search' => "0",

'allow_multi_category' => "1",

'short_title' => "{$lang['install_90']}",

'allow_rss' => "1",

'rss_mtype' => "0",

'rss_number' => "10",

'rss_format' => "1",

'comments_maxlen' => "3000",

'offline_reason' => "{$lang['install_91']}",

'catalog_sort' => "date",

'catalog_msort' => "DESC",

'related_number' => "5",

'seo_type' => "2",

'max_moderation' => "0",

'allow_quick_wysiwyg' => "2",

'sec_addnews' => "2",

'mail_pm' => "1",

'allow_change_sort' => "1",

'registration_rules' => "1",

'allow_tags' => "1",

'allow_add_tags' => "1",

'allow_fixed' => "1",

'max_file_count' => "0",

'allow_smartphone' => "0",

'allow_smart_images' => "0",

'allow_smart_video' => "0",

'allow_search_print' => "1",

'allow_search_link' => "1",

'allow_smart_format' => "1",

'thumb_dimming' => "0",

'thumb_gallery' => "1",

'max_comments_days' => "0",

'allow_combine' => "1",

'allow_subscribe' => "1",

'parse_links' => "0",

't_seite' => "0",

'comments_minlen' => "10",

'js_min' => "0",

'outlinetype' => "0",

'fast_search' => "1",

'login_log' => "5",

'allow_recaptcha' => "0",

'recaptcha_public_key' => "",

'recaptcha_private_key' => "",

'search_number' => "10",

'news_navigation' => "1",

'smtp_mail' => "",

'seo_control' => "0",

'news_restricted' => "0",

'comments_restricted' => "0",

'auth_metod' => "0",

'comments_ajax' => "0",

'create_catalog' => "0",

'mobile_news' => "10",

'reg_question' => "0",

'news_future' => "0",

'cache_type' => "0",

'memcache_server' => "localhost:11211",

'allow_comments_cache' => "1",

'reg_multi_ip' => "1",

'top_number' => "10",

'tags_number' => "40",

'mail_title' => "",

'o_seite' => "0",

'online_status' => "1",

'avatar_size' => "100",

'auth_domain' => "0",

'start_site' => "1",

'clear_cache' => "0",

'allow_complaint_mail' => "0",

'spam_api_key' => "",

'create_metatags' => '1',

'admin_allowed_ip' => '',

'related_only_cats' => '0',

'allow_links' => '1',

'comments_lazyload' => '0',

'category_separator' => ' / ',

'speedbar_separator' => ' &raquo; ',

'adminlog_maxdays' => '30',

'allow_social' => '0',

'medium_image' => '450',

'login_ban_timeout' => '20',

'watermark_seite' => '4',

'auth_only_social' => '0',

'rating_type' => '0',

'allow_comments_rating' => '1',

'comments_rating_type' => '1',

'tree_comments' => '0',

'tree_comments_level' => '5',

'simple_reply' => '0',

'recaptcha_theme' => "light",

'smtp_secure' => '',

'search_pages' => '5',

'profile_news' => '1',

'fullcache_days' => '30',

'twofactor_auth' => '1',

'category_newscount' => '1',

'max_cache_pages' => '10',

'only_ssl' => '0',

'bbimages_in_wysiwyg' => '0',

'allow_redirects' => '1',

'allow_own_meta' => '1',

'own_404' => '0',

'own_ip' => '',

'disable_frame' => '0',

'allow_plugins' => '1',

'allow_admin_social' => '0',

'image_lazy' => '0',

'search_length_min' => '4',

'min_up_side' => '10x10',

'jquery_version' => '3',

'allow_yandex_dzen' => '1',

'allow_yandex_turbo' => '1',

'emoji' => '1',

'last_viewed' => '0',

'image_tinypng' => '0',

'tinypng_key' => '',

'tinypng_avatar' => '0',

'tinypng_resize' => '0',

'tags_separator' => ', ',

'session_timeout' => '0',

'decline_date' => '0',

'redis_user' => '',

'redis_pass' => '',

'news_noreferrer' => '0',

'comm_noreferrer' => '1',

'user_in_news' => '0',

'image_driver' => '0',

'force_webp' => '0',

'watermark_type' => '1',

'watermark_text' => 'Powered by DataLife Engine ©',

'watermark_font' => '16',

'watermark_color_dark' => '#000000',

'watermark_color_light' => '#ffffff',

'watermark_rotate' => '0',

'watermark_opacity' => '100',

'file_driver' => '0',

'remote_key_id' => '',

'remote_secret_key' => '',

'bucket_name' => '',

'region_name' => '',

'ftp_server' => '',

'ftp_port' => '',

'ftp_username' => '',

'ftp_password' => '',

'ftp_path' => '',

'remote_url' => '',

'local_on_fail' => '1',

'image_remote' => '1',

'comments_remote' => '1',

'static_remote' => '1',

'files_remote' => '1',

'avatar_remote' => '1',

'news_indexnow' => '0',

'schema_org' => '0',

'site_icon' => '',

'site_type' => 'Person',

'pub_name' => '',

'recaptcha_score' => '0.5',

'translit_url' => '0',

'shared_remote' => '1',

'sitemap_limit' => '',

'sitemap_news_priority' => '0.6',

'sitemap_stat_priority' => '0.5',

'sitemap_cat_priority' => '0.7',

'sitemap_news_changefreq' => 'weekly',

'sitemap_stat_changefreq' => 'monthly',

'sitemap_cat_changefreq' => 'daily',

'sitemap_news_per_file' => '40000',

'allow_cat_sort' => '1',

'alert_edit_now' => '1',

'read_count_time' => '5',

'key' => '',

);

?>
HTML;


$video_config = <<<HTML
<?PHP

//Videoplayers Configurations

\$video_config = array (

'width' => "600",

'audio_width' => "600",

'preload' => '1',

'theme' => 'light',

);

?>
HTML;


$social_config = <<<HTML
<?PHP

//Social Configurations

\$social_config = array (

'vk' => '0',

'vkid' => '',

'vksecret' => '',

'od' => '0',

'odid' => '',

'odpublic' => '',

'odsecret' => '',

'fc' => '0',

'fcid' => '',

'fcsecret' => '',

'google' => '0',

'googleid' => '',

'googlesecret' => '',

'mailru' => '0',

'mailruid' => '',

'mailrusecret' => '',

'yandex' => '0',

'yandexid' => '',

'yandexsecret' => '',

);

?>
HTML;

$htaccess = <<<HTML
<FilesMatch ".*">
	 Order allow,deny
	 Deny from all
</FilesMatch>

<FilesMatch "\\.(avi|divx|mp3|mp4|wmv|m4v|m4a|mov|mkv|webm|m3u8)\$|^\$">
	 Order deny,allow
	 Allow from all
</FilesMatch>
HTML;

	$con_file = fopen("engine/data/config.php", "w+");

	if ($con_file !== false) {
		fwrite($con_file, $config);
		fclose($con_file);
	}

	@chmod("engine/data/config.php", 0666);

	$con_file = fopen("engine/data/videoconfig.php", "w+");

	if ($con_file !== false) {
		fwrite($con_file, $video_config);
		fclose($con_file);
	}

	@chmod("engine/data/videoconfig.php", 0666);

	$con_file = fopen("engine/data/socialconfig.php", "w+");

	if ($con_file !== false) {
		fwrite($con_file, $social_config);
		fclose($con_file);
	}

	@chmod("engine/data/socialconfig.php", 0666);

	$con_file = fopen("engine/data/wordfilter.db.php", "w+");

	if ($con_file !== false) {
		fwrite($con_file, '');
		fclose($con_file);
	}

	@chmod("engine/data/wordfilter.db.php", 0666);

	$con_file = fopen("engine/data/xfields.txt", "w+");

	if ($con_file !== false) {
		fwrite($con_file, '');
		fclose($con_file);
	}

	@chmod("engine/data/xfields.txt", 0666);

	$con_file = fopen("engine/data/xprofile.txt", "w+");

	if ($con_file !== false) {
		fwrite($con_file, '');
		fclose($con_file);
	}

	@chmod("engine/data/xprofile.txt", 0666);

	$con_file = @fopen("uploads/files/.htaccess", "w+");

	if ($con_file !== false) {
		fwrite($con_file, $htaccess);
		fclose($con_file);
	}

	@unlink(ENGINE_DIR . '/cache/system/usergroup.php');
	@unlink(ENGINE_DIR . '/cache/system/vote.php');
	@unlink(ENGINE_DIR . '/cache/system/banners.php');
	@unlink(ENGINE_DIR . '/cache/system/category.php');
	@unlink(ENGINE_DIR . '/cache/system/banned.php');
	@unlink(ENGINE_DIR . '/cache/system/cron.php');
	@unlink(ENGINE_DIR . '/cache/system/informers.php');
	@unlink(ENGINE_DIR . '/cache/system/plugins.php');
	@unlink(ENGINE_DIR . '/data/snap.db');

	listdir(ENGINE_DIR . '/cache/system/CSS');
	listdir(ENGINE_DIR . '/cache/system/HTML');
	listdir(ENGINE_DIR . '/cache/system/URI');
	listdir(ENGINE_DIR . '/cache/system/plugins');

	$fdir = opendir(ENGINE_DIR . '/cache');

	while ($file = readdir($fdir)) {
		if ($file != '.htaccess' and !is_dir($file)) {
			@unlink(ENGINE_DIR . '/cache/' . $file);
		}
	}

	unset($_SESSION['userconfig']);

	set_cookie("dle_user_id", 1, 365);
	set_cookie("dle_password", md5($userconfig['userpass']), 365);

	$_SESSION['dle_user_id'] = 1;
	$_SESSION['dle_password'] = md5($userconfig['userpass']);

	echo json_encode(array(
		'status' => 'ok'
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	@unlink(__FILE__);
	@unlink(ROOT_DIR . '/templates/default_templates.zip');
	die();

} elseif($_REQUEST['action'] == "docreatedb") {

	if (!isset($_SESSION['userconfig'])) {
		die("SESSION data not found");
	}

	include ENGINE_DIR.'/classes/mysql.php';
	include ENGINE_DIR.'/data/dbconfig.php';

	$db->connect(DBUSER, DBPASS, DBNAME, DBHOST);

	if( version_compare($db->mysql_version, '5.6.4', '<') ) {
		$storage_engine = "MyISAM";
	} else $storage_engine = "InnoDB";

	$userconfig = json_decode($_SESSION['userconfig'], true);

	$reg_username = $db->safesql( $userconfig['username'] );
	$reg_password = $db->safesql( $userconfig['userpass'] );
	$reg_email = $db->safesql( $userconfig['email'] );

	$tableSchema = array();

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_category";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_category (
						`id` mediumint(9) NOT NULL auto_increment,
						`parentid` mediumint(9) NOT NULL default '0',
						`posi` mediumint(9) NOT NULL default '1',
						`name` varchar(50) NOT NULL default '',
						`alt_name` varchar(50) NOT NULL default '',
						`icon` varchar(200) NOT NULL default '',
						`skin` varchar(50) NOT NULL default '',
						`descr` varchar(300) NOT NULL default '',
						`keywords` text NOT NULL,
						`news_sort` varchar(10) NOT NULL default '',
						`news_msort` varchar(4) NOT NULL default '',
						`news_number` smallint(6) NOT NULL default '0',
						`short_tpl` varchar(40) NOT NULL default '',
						`full_tpl` varchar(40) NOT NULL default '',
						`metatitle` varchar(255) NOT NULL default '',
						`show_sub` tinyint(1) NOT NULL default '0',
						`allow_rss` tinyint(1) NOT NULL default '1',
						`fulldescr` text NOT NULL,
						`disable_search` tinyint(1) NOT NULL default '0',
						`disable_main` tinyint(1) NOT NULL default '0',
						`disable_rating` tinyint(1) NOT NULL default '0',
						`disable_comments` tinyint(1) NOT NULL default '0',
						`enable_dzen` tinyint(1) NOT NULL default '1',
						`enable_turbo` tinyint(1) NOT NULL default '1',
						`active` tinyint(1) NOT NULL default '1',
						`rating_type` tinyint(1) NOT NULL default '-1',
						`schema_org` varchar(50) NOT NULL default '1',
						PRIMARY KEY  (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_comments";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_comments (
						`id` int(11) unsigned NOT NULL auto_increment,
						`post_id` int(11) NOT NULL default '0',
						`user_id` int(11) NOT NULL default '0',
						`date` datetime NOT NULL default '2000-01-01 00:00:00',
						`autor` varchar(40) NOT NULL default '',
						`email` varchar(40) NOT NULL default '',
						`text` text NOT NULL,
						`ip` varchar(46) NOT NULL default '',
						`is_register` tinyint(1) NOT NULL default '0',
						`approve` tinyint(1) NOT NULL default '1',
						`rating` int(11) NOT NULL default '0',
						`vote_num` int(11) NOT NULL default '0',
						`parent` int(11) NOT NULL default '0',
						PRIMARY KEY  (`id`),
						KEY `user_id` (`user_id`),
						KEY `post_id` (`post_id`),
						KEY `approve` (`approve`),
						KEY `parent` (`parent`),
						KEY `rating` (`rating`),
						FULLTEXT KEY `text` (`text`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_email";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_email (
						`id` tinyint(3) unsigned NOT NULL auto_increment,
						`name` varchar(10) NOT NULL default '',
						`template` text NOT NULL,
						`use_html` tinyint(1) NOT NULL default '0',
						PRIMARY KEY  (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";


	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_flood";
	$tableSchema[] = "CREATE TABLE  " . PREFIX . "_flood (
						`f_id` int(11) unsigned NOT NULL auto_increment,
						`ip` varchar(46) NOT NULL default '',
						`id` varchar(20) NOT NULL default '',
						`flag` tinyint(1) NOT NULL default '0',
						PRIMARY KEY  (`f_id`),
						KEY `ip` (`ip`),
						KEY `id` (`id`),
						KEY `flag` (`flag`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_images";

	$tableSchema[] = "CREATE TABLE " . PREFIX . "_images (
						`id` int(11) unsigned NOT NULL auto_increment,
						`images` text NOT NULL,
						`news_id` int(11) NOT NULL default '0',
						`author` varchar(40) NOT NULL default '',
						`date` varchar(15) NOT NULL default '',
						PRIMARY KEY  (`id`),
						KEY `author` (`author`),
						KEY `news_id` (`news_id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_logs";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_logs (
						`id` int(11) unsigned NOT NULL auto_increment,
						`news_id` int(11) NOT NULL default '0',
						`member` varchar(40) NOT NULL default '',
						`ip` varchar(46) NOT NULL default '',
						`rating` tinyint(4) NOT NULL DEFAULT '0',
						PRIMARY KEY  (`id`),
						KEY `news_id` (`news_id`),
						KEY `member` (`member`),
						KEY `ip` (`ip`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_vote";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_vote (
						`id` mediumint(9) NOT NULL auto_increment,
						`category` text NOT NULL,
						`vote_num` mediumint(9) NOT NULL default '0',
						`date` varchar(25) NOT NULL default '0',
						`title` varchar(200) NOT NULL default '',
						`body` text NOT NULL,
						`approve` tinyint(1) NOT NULL default '1',
						`start` varchar(15) NOT NULL default '',
						`end` varchar(15) NOT NULL default '',
						`grouplevel` varchar(250) NOT NULL default 'all',
						PRIMARY KEY  (`id`),
						KEY `approve` (`approve`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_vote_result";

	$tableSchema[] = "CREATE TABLE " . PREFIX . "_vote_result (
						`id` int(11) NOT NULL auto_increment,
						`ip` varchar(46) NOT NULL default '',
						`name` varchar(40) NOT NULL default '',
						`vote_id` mediumint(9) NOT NULL default '0',
						`answer` tinyint(3) NOT NULL default '0',
						PRIMARY KEY  (`id`),
						KEY `answer` (`answer`),
						KEY `vote_id` (`vote_id`),
						KEY `ip` (`ip`),
						KEY `name` (`name`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_lostdb";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_lostdb (
						`id` mediumint(9) NOT NULL auto_increment,
						`lostname` mediumint(9) NOT NULL default '0',
						`lostid` varchar( 40 ) NOT NULL default '',
						PRIMARY KEY  (`id`),
						KEY `lostid` (`lostid`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_pm";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_pm (
						`id` int(11) unsigned NOT NULL auto_increment,
						`subj` varchar(255) NOT NULL default '',
						`text` text NOT NULL,
						`user` mediumint(9) NOT NULL default '0',
						`user_from` varchar(40) NOT NULL default '',
						`date` int(11) unsigned NOT NULL default '0',
						`pm_read` tinyint(1) NOT NULL default '0',
						`folder` varchar(10) NOT NULL default '',
						`reply` tinyint(1) NOT NULL default '0',
						`sendid` int(11) unsigned NOT NULL default '0',
						PRIMARY KEY  (`id`),
						KEY `folder` (`folder`),
						KEY `user` (`user`),
						KEY `user_from` (`user_from`),
						KEY `pm_read` (`pm_read`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_post";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_post (
						`id` int(11) NOT NULL auto_increment,
						`autor` varchar(40) NOT NULL default '',
						`date` datetime NOT NULL default '2000-01-01 00:00:00',
						`short_story` MEDIUMTEXT NOT NULL,
						`full_story` MEDIUMTEXT NOT NULL,
						`xfields` MEDIUMTEXT NOT NULL,
						`title` varchar(255) NOT NULL default '',
						`descr` varchar(300) NOT NULL default '',
						`keywords` text NOT NULL,
						`category` varchar(190) NOT NULL default '0',
						`alt_name` varchar(190) NOT NULL default '',
						`comm_num` mediumint(9) unsigned NOT NULL default '0',
						`allow_comm` tinyint(1) NOT NULL default '1',
						`allow_main` tinyint(1) unsigned NOT NULL default '1',
						`approve` tinyint(1) NOT NULL default '0',
						`fixed` tinyint(1) NOT NULL default '0',
						`allow_br` tinyint(1) NOT NULL default '1',
						`symbol` varchar(3) NOT NULL default '',
						`tags` varchar(255) NOT NULL default '',
						`metatitle` varchar(255) NOT NULL default '',
						PRIMARY KEY  (`id`),
						KEY `autor` (`autor`),
						KEY `alt_name` (`alt_name`),
						KEY `category` (`category`),
						KEY `approve` (`approve`),
						KEY `allow_main` (`allow_main`),
						KEY `date` (`date`),
						KEY `symbol` (`symbol`),
						KEY `comm_num` (`comm_num`),
						KEY `fixed` (`fixed`),
						FULLTEXT KEY `short_story` (`short_story`,`full_story`,`xfields`,`title`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_post_extras";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_post_extras (
						`eid` int(11) NOT NULL AUTO_INCREMENT,
						`news_id` int(11) NOT NULL DEFAULT '0',
						`news_read` int(11) NOT NULL DEFAULT '0',
						`allow_rate` tinyint(1) NOT NULL DEFAULT '1',
						`rating` int(11) NOT NULL DEFAULT '0',
						`vote_num` int(11) NOT NULL DEFAULT '0',
						`votes` tinyint(1) NOT NULL DEFAULT '0',
						`view_edit` tinyint(1) NOT NULL DEFAULT '0',
						`disable_index` tinyint(1) NOT NULL DEFAULT '0',
						`related_ids` varchar(255) NOT NULL DEFAULT '',
						`access` varchar(150) NOT NULL DEFAULT '',
						`editdate` int(11) unsigned NOT NULL DEFAULT '0',
						`editor` varchar(40) NOT NULL DEFAULT '',
						`reason` varchar(255) NOT NULL DEFAULT '',
						`user_id` int(11) NOT NULL DEFAULT '0',
						`disable_search` tinyint(1) NOT NULL DEFAULT '0',
						`need_pass` tinyint(1) NOT NULL DEFAULT '0',
						`allow_rss` tinyint(1) NOT NULL DEFAULT '1',
						`allow_rss_turbo` tinyint(1) NOT NULL DEFAULT '1',
						`allow_rss_dzen` tinyint(1) NOT NULL DEFAULT '1',
						`edited_now` varchar(100) NOT NULL DEFAULT '',
						PRIMARY KEY (`eid`),
						KEY `news_id` (`news_id`),
						KEY `user_id` (`user_id`),
						KEY `rating` (`rating`),
						KEY `disable_search` (`disable_search`),
						KEY `allow_rss` (`allow_rss`),
						KEY `news_read` (`news_read`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_post_extras_cats";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_post_extras_cats (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`news_id` int(11) NOT NULL default '0',
						`cat_id` int(11) NOT NULL default '0',
						PRIMARY KEY  (`id`),
						KEY `news_id` (`news_id`),
						KEY `cat_id` (`cat_id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_static";

	$tableSchema[] = "CREATE TABLE " . PREFIX . "_static (
						`id` mediumint(9) NOT NULL auto_increment,
						`name` varchar(100) NOT NULL default '',
						`descr` varchar(255) NOT NULL default '',
						`template` MEDIUMTEXT NOT NULL,
						`allow_br` tinyint(1) NOT NULL default '0',
						`allow_template` tinyint(1) NOT NULL default '0',
						`grouplevel` varchar(100) NOT NULL default 'all',
						`tpl` varchar(255) NOT NULL default '',
						`metadescr` varchar(300) NOT NULL default '',
						`metakeys` text NOT NULL,
						`views` mediumint(9) NOT NULL default '0',
						`template_folder` varchar(50) NOT NULL default '',
						`date` int(11) unsigned NOT NULL default '0',
						`metatitle` varchar(255) NOT NULL default '',
						`allow_count` tinyint(1) NOT NULL default '1',
						`sitemap` tinyint(1) NOT NULL default '1',
						`disable_index` tinyint(1) NOT NULL default '0',
						`disable_search` tinyint(1) NOT NULL default '0',
						`password` text NOT NULL,
						PRIMARY KEY  (`id`),
						KEY `name` (`name`),
						KEY `disable_search` (`disable_search`),
						FULLTEXT KEY `template` (`template`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_users";

	$tableSchema[] = "CREATE TABLE " . PREFIX . "_users (
						`email` varchar(50) NOT NULL default '',
						`password` varchar(255) NOT NULL default '',
						`name` varchar(40) NOT NULL default '',
						`user_id` int(11) NOT NULL auto_increment,
						`news_num` mediumint(9) NOT NULL default '0',
						`comm_num` mediumint(9) NOT NULL default '0',
						`user_group` smallint(6) NOT NULL default '4',
						`lastdate` varchar(20) NOT NULL default '',
						`reg_date` varchar(20) NOT NULL default '',
						`banned` varchar(5) NOT NULL default '',
						`allow_mail` tinyint(1) NOT NULL default '1',
						`info` text NOT NULL,
						`signature` text NOT NULL,
						`foto` varchar(255) NOT NULL default '',
						`fullname` varchar(100) NOT NULL default '',
						`land` varchar(100) NOT NULL default '',
						`favorites` text NOT NULL,
						`pm_all` smallint(6) NOT NULL default '0',
						`pm_unread` smallint(6) NOT NULL default '0',
						`time_limit` varchar(20) NOT NULL default '',
						`xfields` text NOT NULL,
						`allowed_ip` varchar(255) NOT NULL default '',
						`hash` varchar(32) NOT NULL default '',
						`logged_ip` varchar(46) NOT NULL default '',
						`restricted` tinyint(1) NOT NULL default '0',
						`restricted_days` smallint(4) NOT NULL default '0',
						`restricted_date` varchar(15) NOT NULL default '',
						`timezone` varchar(100) NOT NULL default '',
						`news_subscribe` tinyint(1) NOT NULL default '0',
						`comments_reply_subscribe` tinyint(1) NOT NULL default '0',
						`twofactor_auth` tinyint(1) NOT NULL default '0',
						`cat_add` varchar(500) NOT NULL DEFAULT '',
						`cat_allow_addnews` varchar(500) NOT NULL DEFAULT '',
						PRIMARY KEY  (`user_id`),
						UNIQUE KEY `name` (`name`),
						UNIQUE KEY `email` (`email`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_banned";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_banned (
						`id` smallint(6) NOT NULL auto_increment,
						`users_id` int(11) NOT NULL default '0',
						`descr` text NOT NULL,
						`date` varchar(15) NOT NULL default '',
						`days` smallint(4) NOT NULL default '0',
						`ip` varchar(46) NOT NULL default '',
						PRIMARY KEY  (`id`),
						KEY `user_id` (`users_id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_files";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_files (
						`id` int(11) NOT NULL auto_increment,
						`news_id` int(11) NOT NULL default '0',
						`name` varchar(250) NOT NULL default '',
						`onserver` varchar(250) NOT NULL default '',
						`author` varchar(40) NOT NULL default '',
						`date` varchar(15) NOT NULL default '',
						`dcount` int(11) NOT NULL default '0',
						`size` bigint(20) NOT NULL default '0',
						`checksum` char(32) NOT NULL default '',
						`driver` tinyint(1) NOT NULL DEFAULT '0',
						`is_public` tinyint(1) NOT NULL DEFAULT '0',
						PRIMARY KEY  (`id`),
						KEY `news_id` (`news_id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_usergroups";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_usergroups (
						`id` smallint(6) NOT NULL auto_increment,
						`group_name` varchar(50) NOT NULL default '',
						`allow_cats` text NOT NULL,
						`allow_adds` tinyint(1) NOT NULL default '1',
						`cat_add` text NOT NULL,
						`allow_admin` tinyint(1) NOT NULL default '0',
						`allow_addc` tinyint(1) NOT NULL default '0',
						`allow_editc` tinyint(1) NOT NULL default '0',
						`allow_delc` tinyint(1) NOT NULL default '0',
						`edit_allc` tinyint(1) NOT NULL default '0',
						`del_allc` tinyint(1) NOT NULL default '0',
						`moderation` tinyint(1) NOT NULL default '0',
						`allow_all_edit` tinyint(1) NOT NULL default '0',
						`allow_edit` tinyint(1) NOT NULL default '0',
						`allow_pm` tinyint(1) NOT NULL default '0',
						`max_pm` smallint(6) NOT NULL default '0',
						`max_foto` varchar(10) NOT NULL default '',
						`allow_files` tinyint(1) NOT NULL default '0',
						`allow_hide` tinyint(1) NOT NULL default '1',
						`allow_short` tinyint(1) NOT NULL default '0',
						`time_limit` tinyint(1) NOT NULL default '0',
						`rid` smallint(6) NOT NULL default '0',
						`allow_fixed` tinyint(1) NOT NULL default '0',
						`allow_feed`  tinyint(1) NOT NULL default '1',
						`allow_search`  tinyint(1) NOT NULL default '1',
						`allow_poll`  tinyint(1) NOT NULL default '1',
						`allow_main`  tinyint(1) NOT NULL default '1',
						`captcha`  tinyint(1) NOT NULL default '0',
						`icon` varchar(200) NOT NULL default '',
						`allow_modc`  tinyint(1) NOT NULL default '0',
						`allow_rating` tinyint(1) NOT NULL default '1',
						`allow_offline` tinyint(1) NOT NULL default '0',
						`allow_image_upload` tinyint(1) NOT NULL default '0',
						`allow_file_upload` tinyint(1) NOT NULL default '0',
						`allow_signature` tinyint(1) NOT NULL default '0',
						`allow_url` tinyint(1) NOT NULL default '1',
						`news_sec_code` tinyint(1) NOT NULL default '1',
						`allow_image` tinyint(1) NOT NULL default '0',
						`max_signature` smallint(6) NOT NULL default '0',
						`max_info` smallint(6) NOT NULL default '0',
						`admin_addnews` tinyint(1) NOT NULL default '0',
						`admin_editnews` tinyint(1) NOT NULL default '0',
						`admin_comments` tinyint(1) NOT NULL default '0',
						`admin_categories` tinyint(1) NOT NULL default '0',
						`admin_editusers` tinyint(1) NOT NULL default '0',
						`admin_wordfilter` tinyint(1) NOT NULL default '0',
						`admin_xfields` tinyint(1) NOT NULL default '0',
						`admin_userfields` tinyint(1) NOT NULL default '0',
						`admin_static` tinyint(1) NOT NULL default '0',
						`admin_editvote` tinyint(1) NOT NULL default '0',
						`admin_newsletter` tinyint(1) NOT NULL default '0',
						`admin_blockip` tinyint(1) NOT NULL default '0',
						`admin_banners` tinyint(1) NOT NULL default '0',
						`admin_rss` tinyint(1) NOT NULL default '0',
						`admin_iptools` tinyint(1) NOT NULL default '0',
						`admin_rssinform` tinyint(1) NOT NULL default '0',
						`admin_googlemap` tinyint(1) NOT NULL default '0',
						`allow_html` tinyint(1) NOT NULL default '1',
						`group_prefix` text NOT NULL,
						`group_suffix` text NOT NULL,
						`allow_subscribe` tinyint(1) NOT NULL default '0',
						`allow_image_size` tinyint(1) NOT NULL default '0',
						`cat_allow_addnews` text NOT NULL,
						`flood_news` smallint(6) NOT NULL default '0',
						`max_day_news` smallint(6) NOT NULL default '0',
						`force_leech` tinyint(1) NOT NULL default '0',
						`edit_limit` smallint(6) NOT NULL default '0',
						`captcha_pm` tinyint(1) NOT NULL default '0',
						`max_pm_day` smallint(6) NOT NULL default '0',
						`max_mail_day` smallint(6) NOT NULL default '0',
						`admin_tagscloud` tinyint(1) NOT NULL default '0',
						`allow_vote` tinyint(1) NOT NULL default '0',
						`admin_complaint` tinyint(1) NOT NULL default '0',
						`news_question` tinyint(1) NOT NULL default '0',
						`comments_question` tinyint(1) NOT NULL default '0',
						`max_comment_day` smallint(6) NOT NULL default '0',
						`max_images` smallint(6) NOT NULL default '0',
						`max_files` smallint(6) NOT NULL default '0',
						`disable_news_captcha` smallint(6) NOT NULL default '0',
						`disable_comments_captcha` smallint(6) NOT NULL default '0',
						`pm_question` tinyint(1) NOT NULL default '0',
						`captcha_feedback` tinyint(1) NOT NULL default '1',
						`feedback_question` tinyint(1) NOT NULL default '0',
						`files_type` varchar(255) NOT NULL default '',
						`max_file_size` mediumint(9) NOT NULL default '0',
						`files_max_speed` smallint(6) NOT NULL default '0',
						`spamfilter` tinyint(1) NOT NULL default '2',
						`allow_comments_rating` tinyint(1) NOT NULL default '1',
						`max_edit_days` tinyint(1) NOT NULL default '0',
						`spampmfilter` tinyint(1) NOT NULL default '0',
						`force_reg` tinyint(1) NOT NULL default '0',
						`force_reg_days` mediumint(9) NOT NULL default '0',
						`force_reg_group` smallint(6) NOT NULL default '4',
						`force_news` tinyint(1) NOT NULL default '0',
						`force_news_count` mediumint(9) NOT NULL default '0',
						`force_news_group` smallint(6) NOT NULL default '4',
						`force_comments` tinyint(1) NOT NULL default '0',
						`force_comments_count` mediumint(9) NOT NULL default '0',
						`force_comments_group` smallint(6) NOT NULL default '4',
						`force_rating` tinyint(1) NOT NULL default '0',
						`force_rating_count` mediumint(9) NOT NULL default '0',
						`force_rating_group` smallint(6) NOT NULL default '4',
						`not_allow_cats` text NOT NULL,
						`allow_up_image` tinyint(1) NOT NULL default '0',
						`allow_up_watermark` tinyint(1) NOT NULL default '0',
						`allow_up_thumb` tinyint(1) NOT NULL default '0',
						`up_count_image` smallint(6) NOT NULL default '0',
						`up_image_side` varchar(20) NOT NULL default '',
						`up_image_size` mediumint(9) NOT NULL default '0',
						`up_thumb_size` varchar(20) NOT NULL default '',
						`allow_mail_files` tinyint(1) NOT NULL DEFAULT '0',
						`max_mail_files` smallint(6) NOT NULL DEFAULT '0',
						`max_mail_allfiles` mediumint(9) NOT NULL DEFAULT '0',
						`mail_files_type` varchar(100) NOT NULL DEFAULT '',
						`video_comments` tinyint(1) NOT NULL DEFAULT '0',
						`media_comments` tinyint(1) NOT NULL DEFAULT '0',
						`min_image_side` varchar(20) NOT NULL DEFAULT '',
						`allow_public_file_upload` tinyint(1) NOT NULL default '0',
						`force_comments_rating` tinyint(1) NOT NULL default '0',
						`force_comments_rating_count` mediumint(9) NOT NULL default '0',
						`force_comments_rating_group` smallint(6) NOT NULL DEFAULT '0',
						PRIMARY KEY  (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";


	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_poll";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_poll (
						`id` mediumint(9) unsigned NOT NULL auto_increment,
						`news_id` int(11) unsigned NOT NULL default '0',
						`title` varchar(200) NOT NULL default '',
						`frage` varchar(200) NOT NULL default '',
						`body` text NOT NULL,
						`votes` mediumint(9) NOT NULL default '0',
						`multiple` tinyint(1) NOT NULL default '0',
						`answer` text NOT NULL,
						PRIMARY KEY  (`id`),
						KEY `news_id` (`news_id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_poll_log";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_poll_log (
						`id` int(11) unsigned NOT NULL auto_increment,
						`news_id` int(11) unsigned NOT NULL default '0',
						`member` varchar(40) NOT NULL default '',
						PRIMARY KEY  (`id`),
						KEY `news_id` (`news_id`),
						KEY `member` (`member`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_banners";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_banners (
						`id` smallint(6) NOT NULL auto_increment,
						`banner_tag` varchar(40) NOT NULL default '',
						`descr` varchar(200) NOT NULL default '',
						`code` text NOT NULL,
						`approve` tinyint(1) NOT NULL default '0',
						`short_place` tinyint(1) NOT NULL default '0',
						`bstick` tinyint(1) NOT NULL default '0',
						`main` tinyint(1) NOT NULL default '0',
						`category` varchar(255) NOT NULL default '',
						`grouplevel` varchar(100) NOT NULL default 'all',
						`start` varchar(15) NOT NULL default '',
						`end` varchar(15) NOT NULL default '',
						`fpage` tinyint(1) NOT NULL default '0',
						`innews` tinyint(1) NOT NULL default '0',
						`devicelevel` varchar(10) NOT NULL default '',
						`allow_views` tinyint(1) NOT NULL default '0',
						`max_views` int(11) NOT NULL default '0',
						`allow_counts` tinyint(1) NOT NULL default '0',
						`max_counts` int(11) NOT NULL default '0',
						`views` int(11) NOT NULL default '0',
						`clicks` int(11) NOT NULL default '0',
						`rubric` mediumint(9) NOT NULL default '0',
						PRIMARY KEY  (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_rss";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_rss (
						`id` smallint(6) NOT NULL auto_increment,
						`url` varchar(255) NOT NULL default '',
						`description` text NOT NULL,
						`allow_main` tinyint(1) NOT NULL default '0',
						`allow_rating` tinyint(1) NOT NULL default '0',
						`allow_comm` tinyint(1) NOT NULL default '0',
						`text_type` tinyint(1) NOT NULL default '0',
						`date` tinyint(1) NOT NULL default '0',
						`search` text NOT NULL,
						`max_news` tinyint(3) NOT NULL default '0',
						`cookie` text NOT NULL,
						`category` smallint(6) NOT NULL default '0',
						`lastdate` int(11) unsigned NOT NULL default '0',
						PRIMARY KEY  (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_views";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_views (
						`id` int(11) NOT NULL auto_increment,
						`news_id` int(11) NOT NULL default '0',
						PRIMARY KEY  (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";


	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_rssinform";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_rssinform (
						`id` smallint(6) NOT NULL auto_increment,
						`tag` varchar(40) NOT NULL default '',
						`descr` varchar(255) NOT NULL default '',
						`category` varchar(200) NOT NULL default '',
						`url` varchar(255) NOT NULL default '',
						`template` varchar(40) NOT NULL default '',
						`news_max` smallint(6) NOT NULL default '0',
						`tmax` smallint(6) NOT NULL default '0',
						`dmax` smallint(6) NOT NULL default '0',
						`approve` tinyint(1) NOT NULL default '1',
						`rss_date_format` varchar(20) NOT NULL default '',
						PRIMARY KEY  (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_notice";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_notice (
						`id` mediumint(9) NOT NULL auto_increment,
						`user_id` int(11) NOT NULL default '0',
						`notice` text NOT NULL,
						PRIMARY KEY  (`id`),
						KEY `user_id` (`user_id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_static_files";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_static_files (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`static_id` int(11) NOT NULL default '0',
						`author` varchar(40) NOT NULL default '',
						`date` varchar(15) NOT NULL default '',
						`name` varchar(200) NOT NULL default '',
						`onserver` varchar(190) NOT NULL default '',
						`dcount` int(11) NOT NULL default '0',
						`size` bigint(20) NOT NULL default '0',
						`checksum` char(32) NOT NULL default '',
						`driver` tinyint(1) NOT NULL DEFAULT '0',
						`is_public` tinyint(1) NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`),
						KEY `static_id` (`static_id`),
						KEY `onserver` (`onserver`),
						KEY `author` (`author`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_tags";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_tags (
						`id` int(11) NOT NULL auto_increment,
						`news_id` int(11) NOT NULL default '0',
						`tag` varchar(100) NOT NULL default '',
						PRIMARY KEY  (`id`),
						KEY `news_id` (`news_id`),
						KEY `tag` (`tag`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_post_log";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_post_log (
						`id` int(11) NOT NULL auto_increment,
						`news_id` int(11) NOT NULL default '0',
						`expires` varchar(15) NOT NULL default '',
						`action` tinyint(1) NOT NULL default '0',
						`move_cat` varchar(190) NOT NULL DEFAULT '',
						PRIMARY KEY  (`id`),
						KEY `news_id` (`news_id`),
						KEY `expires` (`expires`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_admin_sections";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_admin_sections (
						`id` mediumint(9) NOT NULL auto_increment,
						`name` varchar(100) NOT NULL default '',
						`title` varchar(255) NOT NULL default '',
						`descr` varchar(255) NOT NULL default '',
						`icon` varchar(255) NOT NULL default '',
						`allow_groups` varchar(255) NOT NULL default '',
						PRIMARY KEY  (`id`),
						UNIQUE KEY `name` (`name`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_subscribe";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_subscribe (
						`id` int(11) NOT NULL auto_increment,
						`user_id` int(11) NOT NULL default '0',
						`name` varchar(40) NOT NULL default '',
						`email`  varchar(50) NOT NULL default '',
						`news_id` int(11) NOT NULL default '0',
						`hash` varchar(32) NOT NULL default '',
						PRIMARY KEY  (`id`),
						KEY `news_id` (`news_id`),
						KEY `user_id` (`user_id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_sendlog";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_sendlog (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`user` varchar(40) NOT NULL DEFAULT '',
						`date` int(11) unsigned NOT NULL DEFAULT '0',
						`flag` tinyint(1) NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`),
						KEY `user` (`user`),
						KEY `date` (`date`),
						KEY `flag` (`flag`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_login_log";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_login_log (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`ip` varchar(46) NOT NULL DEFAULT '',
						`count` smallint(6) NOT NULL DEFAULT '0',
						`date` int(11) unsigned NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`),
						UNIQUE KEY `ip` (`ip`),
						KEY `date` (`date`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_mail_log";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_mail_log (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`user_id` int(11) NOT NULL DEFAULT '0',
						`mail` varchar(50) NOT NULL DEFAULT '',
						`hash` varchar(40) NOT NULL DEFAULT '',
						PRIMARY KEY (`id`),
						KEY `hash` (`hash`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_complaint";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_complaint (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`p_id` int(11) NOT NULL DEFAULT '0',
						`c_id` int(11) NOT NULL DEFAULT '0',
						`n_id` int(11) NOT NULL DEFAULT '0',
						`text` text NOT NULL,
						`from` varchar(40) NOT NULL DEFAULT '',
						`to` varchar(255) NOT NULL DEFAULT '',
						`date` int(11) unsigned NOT NULL DEFAULT '0',
						`email` varchar(50) NOT NULL DEFAULT '',
						PRIMARY KEY (`id`),
						KEY `c_id` (`c_id`),
						KEY `p_id` (`p_id`),
						KEY `n_id` (`n_id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_ignore_list";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_ignore_list (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`user` int(11) NOT NULL default '0',
						`user_from` varchar(40) NOT NULL default '',
						PRIMARY KEY  (`id`),
						KEY `user` (`user`),
						KEY `user_from` (`user_from`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_admin_logs";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_admin_logs (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`name` varchar(40) NOT NULL DEFAULT '',
						`date` int(11) unsigned NOT NULL DEFAULT '0',
						`ip` varchar(46) NOT NULL DEFAULT '',
						`action` int(11) NOT NULL DEFAULT '0',
						`extras` text NOT NULL,
						PRIMARY KEY  (`id`),
						KEY `date` (`date`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_question";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_question (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`question` varchar(255) NOT NULL DEFAULT '',
						`answer` text NOT NULL,
						PRIMARY KEY  (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_read_log";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_read_log (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`news_id` int(11) NOT NULL DEFAULT '0',
						`ip` varchar(46) NOT NULL DEFAULT '',
						PRIMARY KEY (`id`),
						KEY `news_id` (`news_id`),
						KEY `ip` (`ip`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_spam_log";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_spam_log (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`ip` varchar(46) NOT NULL DEFAULT '',
						`is_spammer` tinyint(1) NOT NULL DEFAULT '0',
						`email` varchar(50) NOT NULL DEFAULT '',
						`date` int(11) unsigned NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`),
						KEY `ip` (`ip`),
						KEY `is_spammer` (`is_spammer`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_links";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_links (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`word` varchar(255) NOT NULL DEFAULT '',
						`link` varchar(255) NOT NULL DEFAULT '',
						`only_one` tinyint(1) NOT NULL DEFAULT '0',
						`replacearea` tinyint(1) NOT NULL DEFAULT '1',
						`rcount` tinyint(3) NOT NULL DEFAULT '0',
						`targetblank` tinyint(1) NOT NULL DEFAULT '0',
						`title` varchar(255) NOT NULL DEFAULT '',
						PRIMARY KEY (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_social_login";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_social_login (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`sid` varchar(40) NOT NULL DEFAULT '',
						`uid` int(11) NOT NULL DEFAULT '0',
						`password` varchar(32) NOT NULL DEFAULT '',
						`provider` varchar(15) NOT NULL DEFAULT '',
						`wait` tinyint(1) NOT NULL DEFAULT '0',
						`waitlogin` tinyint(1) NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`),
						KEY `sid` (`sid`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_comment_rating_log";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_comment_rating_log (
						`id` int(11) unsigned NOT NULL auto_increment,
						`c_id` int(11) NOT NULL default '0',
						`member` varchar(40) NOT NULL default '',
						`ip` varchar(46) NOT NULL default '',
						`rating` tinyint(4) NOT NULL DEFAULT '0',
						PRIMARY KEY  (`id`),
						KEY `c_id` (`c_id`),
						KEY `member` (`member`),
						KEY `ip` (`ip`)
						) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_xfsearch";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_xfsearch (
						`id` int(11) NOT NULL auto_increment,
						`news_id` int(11) NOT NULL default '0',
						`tagname` varchar(50) NOT NULL default '',
						`tagvalue` varchar(100) NOT NULL default '',
						PRIMARY KEY  (`id`),
						KEY `news_id` (`news_id`),
						KEY `tagname` (`tagname`),
						KEY `tagvalue` (`tagvalue`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_comments_files";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_comments_files (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`c_id` int(11) NOT NULL default '0',
						`author` varchar(40) NOT NULL default '',
						`date` varchar(15) NOT NULL default '',
						`name` varchar(255) NOT NULL default '',
						`driver` tinyint(1) NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`),
						KEY `c_id` (`c_id`),
						KEY `author` (`author`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_twofactor";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_twofactor (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`user_id` int(11) NOT NULL default '0',
						`pin` varchar(10) NOT NULL default '',
						`attempt` tinyint(1) NOT NULL DEFAULT '0',
						`date` int(11) unsigned NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`),
						KEY `pin` (`pin`),
						KEY `date` (`date`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_redirects";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_redirects (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`from` varchar(250) NOT NULL default '',
						`to` varchar(250) NOT NULL default '',
						PRIMARY KEY (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_post_pass";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_post_pass (
						`id` int(11) NOT NULL auto_increment,
						`news_id` int(11) NOT NULL default '0',
						`password` text NOT NULL,
						PRIMARY KEY  (`id`),
						KEY `news_id` (`news_id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_metatags";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_metatags (
						`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
						`url` varchar(250) NOT NULL default '',
						`title` varchar(200) NOT NULL default '',
						`description` varchar(300) NOT NULL default '',
						`keywords` text NOT NULL,
						`page_title` varchar(255) NOT NULL default '',
						`page_description` text NOT NULL,
						`robots` varchar(255) NOT NULL DEFAULT '',
						PRIMARY KEY (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_banners_logs";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_banners_logs (
						`id` int(11) unsigned NOT NULL auto_increment,
						`bid` int(11) NOT NULL default '0',
						`click` tinyint(1) NOT NULL default '0',
						`ip` varchar(46) NOT NULL  default '',
						PRIMARY KEY  (`id`),
						KEY `bid` (`bid`),
						KEY `ip` (`ip`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_banners_rubrics";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_banners_rubrics (
						`id` mediumint(9) NOT NULL auto_increment,
						`parentid` mediumint(9) NOT NULL default '0',
						`title` varchar(70) NOT NULL default '',
						`description` varchar(255) NOT NULL  default '',
						PRIMARY KEY  (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_plugins";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_plugins (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`name` varchar(100) NOT NULL DEFAULT '',
						`description` varchar(255) NOT NULL DEFAULT '',
						`icon` varchar(255) NOT NULL DEFAULT '',
						`version` varchar(10) NOT NULL DEFAULT '',
						`dleversion` varchar(10) NOT NULL DEFAULT '',
						`versioncompare` char(2) NOT NULL DEFAULT '',
						`active` tinyint(1) NOT NULL DEFAULT '0',
						`mysqlinstall` text NOT NULL,
						`mysqlupgrade` text NOT NULL,
						`mysqlenable` text NOT NULL,
						`mysqldisable` text NOT NULL,
						`mysqldelete` text NOT NULL,
						`filedelete` tinyint(1) NOT NULL DEFAULT '0',
						`filelist` text NOT NULL,
						`upgradeurl` varchar(255) NOT NULL DEFAULT '',
						`needplugin` varchar(100) NOT NULL default '',
						`phpinstall` text NOT NULL,
						`phpupgrade` text NOT NULL,
						`phpenable` text NOT NULL,
						`phpdisable` text NOT NULL,
						`phpdelete` text NOT NULL,
						`notice` TEXT NOT NULL,
						`mnotice` tinyint(1) NOT NULL DEFAULT '0',
						`posi` mediumint(9) NOT NULL DEFAULT '1',
						PRIMARY KEY (`id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_plugins_files";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_plugins_files (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`plugin_id` int(11) NOT NULL DEFAULT '0',
						`file` varchar(255) NOT NULL DEFAULT '',
						`action` varchar(10) NOT NULL DEFAULT '',
						`searchcode` text NOT NULL,
						`replacecode` mediumtext NOT NULL,
						`active` tinyint(1) NOT NULL DEFAULT '0',
						`searchcount` smallint(6) NOT NULL DEFAULT '0',
						`replacecount` smallint(6) NOT NULL DEFAULT '0',
						`filedisable` tinyint(1) NOT NULL DEFAULT '1',
						`filedleversion` varchar(10) NOT NULL DEFAULT '',
						`fileversioncompare` char(2) NOT NULL DEFAULT '',
						PRIMARY KEY (`id`),
						KEY `plugin_id` (`plugin_id`),
						KEY `active` (`active`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "DROP TABLE IF EXISTS " . PREFIX . "_plugins_logs";
	$tableSchema[] = "CREATE TABLE " . PREFIX . "_plugins_logs (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`plugin_id` int(11) NOT NULL DEFAULT '0',
						`area` text NOT NULL,
						`error` text NOT NULL,
						`type` varchar(10) NOT NULL DEFAULT '',
						`action_id` int(11) NOT NULL DEFAULT '0',
						PRIMARY KEY (`id`),
						KEY `plugin_id` (`plugin_id`)
					) ENGINE=" . $storage_engine . " DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci";

	$tableSchema[] = "INSERT INTO " . PREFIX . "_rssinform VALUES (1, 'dle', '{$lang['install_52']}', '0', '{$lang['install_93']}', 'informer', 3, 0, 200, 1, 'j F Y H:i')";

	$tableSchema[] = "INSERT INTO " . PREFIX . "_usergroups VALUES (1, '{$lang['install_53']}', 'all', 1, 'all', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 50, 101, 1, 1, 1, 0, 1, 1, 1, 1, 1, 1, 0, '{THEME}/images/icon_1.gif', 0, 1, 1, 1, 1, 1, 1, 0, 1,500,1000,1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,1,'<b><span style=\"color:red\">','</span></b>',1,1,'all', 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 'zip,rar,doc,pdf,mp3,mp4', 4096, 0, 2, 1, 0, 0, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0, 0, 1, '', 1, 1, 1, 3, '800x600', 300, '200x150', 1, 3, 1000, 'jpg,png,zip,pdf',1,1,'10x10', 1, 0, 0, 4)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_usergroups VALUES (2, '{$lang['install_54']}', 'all', 1, 'all', 1, 1, 1, 1, 1, 0, 1, 1, 1, 1, 50, 101, 1, 1, 1, 0, 2, 1, 1, 1, 1, 1, 0, '{THEME}/images/icon_2.gif', 0, 1, 0, 1, 1, 1, 1, 0, 1,500,1000,1, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,1,'','',1,1,'all', 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 'zip,rar,doc,pdf,mp3,mp4', 4096, 0, 2, 1, 0, 0, 0, 0, 2, 0, 0, 2, 0, 0, 2, 0, 0, 2, '', 1, 1, 1, 3, '800x600', 300, '200x150', 1, 3, 1000, 'jpg,png,zip,pdf',1,1,'10x10', 1, 0, 0, 4)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_usergroups VALUES (3, '{$lang['install_55']}', 'all', 1, 'all', 1, 1, 1, 1, 0, 0, 1, 0, 1, 1, 50, 101, 1, 1, 1, 0, 3, 0, 1, 1, 1, 1, 0, '{THEME}/images/icon_3.gif', 0, 1, 0, 1, 1, 1, 1, 0, 1,500,1000,1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,1,'','',1,1,'all', 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 'zip,rar,doc,pdf,mp3,mp4', 4096, 0, 2, 1, 0, 0, 0, 0, 3, 0, 0, 3, 0, 0, 3, 0, 0, 3, '', 1, 1, 1, 3, '800x600', 300, '200x150', 0, 3, 1000, 'jpg,png,zip,pdf',1,1,'10x10', 0, 0, 0, 4)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_usergroups VALUES (4, '{$lang['install_56']}', 'all', 1, 'all', 0, 1, 1, 1, 0, 0, 0, 0, 0, 1, 20, 101, 1, 1, 1, 0, 4, 0, 1, 1, 1, 1, 0, '{THEME}/images/icon_4.gif', 0, 1, 0, 1, 0, 1, 1, 1, 0,500,1000,0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,1,'','',1,0,'all', 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 'zip,rar,doc,pdf,mp3,mp4', 4096, 0, 2, 1, 0, 2, 0, 0, 4, 0, 0, 4, 0, 0, 4, 0, 0, 4, '', 0, 0, 0, 1, '800x600', 300, '200x150', 0, 3, 1000, 'jpg,png,zip,pdf',0,0,'10x10', 0, 0, 0, 4)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_usergroups VALUES (5, '{$lang['install_57']}', 'all', 0, 'all', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 1, 0, 5, 0, 1, 1, 1, 0, 1, '{THEME}/images/icon_5.gif', 0, 1, 0, 0, 0, 0, 1, 1, 0,1,1,0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,0,'','',0,0,'all', 0, 0, 0, 0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, '', 0, 0, 2, 1, 0, 2, 0, 0, 5, 0, 0, 5, 0, 0, 5, 0, 0, 5, '', 0, 0, 0, 1, '800x600', 300, '200x150', 0, 3, 1000, 'jpg,png,zip,pdf',0,0,'10x10', 0, 0, 0, 4)";

	$tableSchema[] = "INSERT INTO " . PREFIX . "_rss VALUES (1, 'https://dle-news.ru/rss.xml', '{$lang['install_58']}', 1, 1, 1, 1, 1, '<div class=\"card-body post-body pl-4 pr-3 pb-4 pt-0\">{get}<div class=\"card-footer d-flex align-content-center pt-0 pl-0 pr-4 pb-3\">', 5, '', 1, 0)";

	$tableSchema[] = "INSERT INTO " . PREFIX . "_email values (1, 'reg_mail', '{$lang['install_59']}', 0)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_email values (2, 'feed_mail', '{$lang['install_60']}', 0)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_email values (3, 'lost_mail', '{$lang['install_61']}', 0)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_email values (4, 'new_news', '{$lang['install_62']}', 0)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_email values (5, 'comments', '{$lang['install_63']}', 0)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_email values (6, 'pm', '{$lang['install_64']}', 0)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_email values (7, 'wait_mail', '{$lang['install_65']}', 0)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_email values (8, 'newsletter', '{$lang['install_66']}', 0)";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_email values (9, 'twofactor', '{$lang['install_67']}', 0)";

	$tableSchema[] = "INSERT INTO " . PREFIX . "_category (name, alt_name, keywords) values ('{$lang['install_68']}', 'o-skripte', '')";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_category (name, alt_name, keywords) values ('{$lang['install_69']}', 'v-mire', '')";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_category (name, alt_name, keywords) values ('{$lang['install_70']}', 'ekonomika', '')";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_category (name, alt_name, keywords) values ('{$lang['install_71']}', 'religiya', '')";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_category (name, alt_name, keywords) values ('{$lang['install_72']}', 'kriminal', '')";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_category (name, alt_name, keywords) values ('{$lang['install_73']}', 'sport', '')";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_category (name, alt_name, keywords) values ('{$lang['install_74']}', 'kultura', '')";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_category (name, alt_name, keywords) values ('{$lang['install_75']}', 'inopressa', '')";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_banners (banner_tag, descr, code, approve, short_place, bstick, main, category) values ('header', '{$lang['install_76']}', '<div style=\"text-align:center;\"><a href=\"https://dle-news.ru/\" target=\"_blank\"><img src=\"{$url}templates/Default/images/_banner_.gif\" style=\"border: none;\" alt=\"\" /></a></div>', 1, 0, 0, 0, 0)";

	$add_time = time();
	$thistime = date("Y-m-d H:i:s", $add_time);

	$tableSchema[] = "INSERT INTO " . PREFIX . "_static (`name`, `descr`, `template`, `allow_br`, `allow_template`, `grouplevel`, `tpl`, `metadescr`, `metakeys`, `views`, `template_folder`, `date`) VALUES ('dle-rules-page', '{$lang['install_77']}', '{$lang['install_78']}', 1, 1, 'all', '', '{$lang['install_77']}', '{$lang['install_77']}', 0, '', '{$add_time}')";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_users (name, password, email, reg_date, lastdate, user_group, news_num, info, signature, favorites, xfields, logged_ip) values ('$reg_username', '$reg_password', '$reg_email', '$add_time', '$add_time', '1', '3', '', '', '', '', '{$_IP}')";
	$tableSchema[] = "INSERT INTO " . PREFIX . "_vote (category, vote_num, date, title, body) VALUES ('all', '0', '$thistime', '{$lang['install_79']}', '{$lang['install_80']}')";

	$title = $lang['install_81'];
	$short_story = $lang['install_82'];
	$full_story = "";

	$tableSchema[] = "INSERT INTO " . PREFIX . "_post (id, date, autor, short_story, full_story, xfields, title, keywords, category, alt_name, allow_comm, approve, allow_main, tags) values ('1', '$thistime', '$reg_username', '$short_story', '$full_story', '', '$title', '', '1', 'post1', '1', '1', '1', 'по, новости')";

	$title = $lang['install_83'];
	$short_story = $lang['install_84'];

	$add_time = time() - 20;
	$thistime = date("Y-m-d H:i:s", $add_time);

	$tableSchema[] = "INSERT INTO " . PREFIX . "_post (id, date, autor, short_story, full_story, xfields, title, keywords, category, alt_name, allow_comm, approve, allow_main, tags) values ('2', '$thistime', '$reg_username', '$short_story', '$full_story', '', '$title', '', '1', 'post2', '1', '1', '1', 'по, новости')";

	$title = $lang['install_85'];
	$short_story = $lang['install_86'];

	$add_time = time() - 50;
	$thistime = date("Y-m-d H:i:s", $add_time);

	$tableSchema[] = "INSERT INTO " . PREFIX . "_post (id, date, autor, short_story, full_story, xfields, title, keywords, category, alt_name, allow_comm, approve, allow_main, tags) values ('3', '$thistime', '$reg_username', '$short_story', '$full_story', '', '$title', '', '1', 'post4', '1', '1', '1', '')";

	$tableSchema[] = "INSERT INTO " . PREFIX . "_post_extras (news_id, user_id) values ('1', '1'), ('2', '1'), ('3', '1')";

	$tableSchema[] = "INSERT INTO " . PREFIX . "_post_extras_cats (news_id, cat_id) values ('1', '1'), ('2', '1'), ('3', '1')";

	$tableSchema[] = "INSERT INTO " . PREFIX . "_tags (news_id, tag) values ('1', '{$lang['install_87']}'), ('2', '{$lang['install_87']}'), ('3', '{$lang['install_87']}'), ('1', '{$lang['install_88']}'), ('2', '{$lang['install_88']}')";


	$done = 0;

	$total = count($tableSchema);
	$offset = intval($_POST['offset']);
	$limit = 10;

	for ($i = 0; $i < $limit; $i++) {
		$index = $offset + $i;

		if (isset($tableSchema[$index])) {
			$db->query($tableSchema[$index]);
			$done++;
		}
	}


	if ($done) {
		$offset = $offset + $done;
	} else {
		$offset = $total;
	}

	echo json_encode(array(
		'status' => 'ok',
		'offset' => $offset,
		'total' => $total
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	die();

} else {

	$_SESSION['dle_install'] = 1;

	$sys_con_langs_arr = get_folder_list('language');

	$output = "<select class=\"uniform\"  data-width=\"100%\" name=\"selected_language\">\r\n";

	foreach ($sys_con_langs_arr as $value => $description) {

		if (isset($description['icon']) and $description['icon']) {
			$output .= "<option data-content=\"<span class='select-icon'><img src='language/{$value}/{$description['icon']}'></span><span class='select-descr'>{$description['name']}</span>\" value=\"$value\"";
		} else {
			$output .= "<option value=\"$value\"";
		}

		if ($selected_language == $value) {
			$output .= " selected ";
		}

		$output .= ">{$description['name']}</option>\n";
	}

	$output .= "</select>";

	echo $skin_header;

echo <<<HTML
<div class="col-md-7 col-md-offset-2" style="margin-top:80px;">
<form method="post" action="" class="form-horizontal">
<input type="hidden" name="action" value="eula">
<div class="panel panel-default">
	<div class="panel-heading">
	{$lang['install_1']}
	</div>
	<div class="panel-body">
	<div class="mb-10">{$lang['install_7']}</div>
	<div class="form-group">
		<label class="control-label col-md-4">{$lang['install_9']}</label>
		<div class="col-md-8">
		{$output}
		</div>
	</div>

	</div>
	<div class="panel-footer">
	<button type="submit" class="btn bg-teal btn-sm btn-raised position-left"><i class="fa fa-arrow-circle-o-right position-left"></i>{$lang['install_10']}</button>
	</div>
</div>
</form>
</div>
HTML;

	echo $skin_footer;

}

?>