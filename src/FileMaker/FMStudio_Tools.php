<?php // FMStudio v1.0 - do not remove comment, needed for DreamWeaver support 
// Copyright 2007 FMWebschool Inc. this file is part of FMStudio

//UPDATED 10/21/2008 bharlow - http://fmwebschool.com/frm/index.php?topic=1591.0

$self_url = $_SERVER['PHP_SELF'];
if(isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '') $self_url.='?'.$_SERVER['QUERY_STRING'];
$self_url_clean = preg_replace('#\?.*$#','',$self_url);

if(!session_id()) session_start();

fmsGET2POST();

function fmsGetMIMEType($url,&$ext) {
	global $fmsMIME_TYPES;
	$ext = preg_replace('#^.*data\.([^\?]*)\?.*$#','\1',$url);
	$ext = strtolower(trim($ext));
	if($ext == '') return 'text/plain';
	switch($ext){
	case 'gif.php':
	case 'gif':
		return 'image/gif';
	case 'png.php':
	case 'png':
		return 'image/png';
	case 'jpg.php':
	case 'jpg':
		return 'image/jpeg';
	case '.cnt':
	
	case isset($fmsMIME_TYPES[$ext]):
		return $fmsMIME_TYPES[$ext];
	default:
		return 'text/plain';
	}
}

function fmsShowImage(&$fm,$layout,$field,$recid) {
	$rec = $fm->getRecordById($layout,$recid);
	$url = $rec->getField($field);
	if($url != '') {
		header('Content-Type: '.fmsGetMIMEType($url,$ext));
		$data = $fm->getContainerData($url);
		header('Content-Disposition: inline; filename='.$field.'-'.$recid.'.'.$ext);
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".strlen($data));
		echo $data;
	}else{
		$data = pack("H*",'47494638396101000100a10100000000ffffffffffffffffff21f904010a0001002c00000000010001000002024c01003b');
		header('Content-Type: image/gif');
		header('Content-Disposition: inline; filename=blank.gif');
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".strlen($data));
		echo $data;
	}
	exit();
}

function fmsImageURL($field,$recid) {
	$self = array_shift(explode('?',$_SERVER['PHP_SELF'],2));
	return $self.'?image='.$field.'&recid='.$recid;
}

$fmsCRYPT_SALT = "FMStudio!";		// Feel free to change this line to a unique value for your server
function fmsServeFile(&$fm,$layout,$field,$recid) {
	global $fmsCRYPT_SALT;
	if(bin2hex(substr(crypt(md5($field.$fmsCRYPT_SALT.$recid),$fmsCRYPT_SALT),4,12)) != $_GET['h']) die('security check failed');
	$rec = $fm->getRecordById($layout,$recid);
	$url = $rec->getField($field);
	if($url != '') {
		$properties = $fm->getProperties();
		$fullUrl = 'http://'.$properties['username'].':'.$properties['password'].'@'.$properties['hostspec'].html_entity_decode($url);
		$ch = curl_init($fullUrl);
		curl_setopt($ch,CURLOPT_HTTPHEADER, array('X-FMI-PE-ExtendedPrivilege: tU+xR2RSsdk='));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch,CURLOPT_HEADER, TRUE);
		$data = curl_exec($ch) or die(curl_error($ch));
		$body = substr($data, strpos($data, "\r\n\r\n") + 4);
       	$headers = fmsHeadersToArray(substr($data, 0, -strlen($body)));
		if(isset($headers['content-type'])) header($headers['content-type'][0]);
		if(isset($headers['content-disposition'])) header($headers['content-disposition'][0]);
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".strlen($body));
		echo $body;
	}else{
		die('no file is associated with this record');
	}
	exit();
}

function fmsGetFileData($fm, $url) {
	$ret = array(
		'name'=>null,
		'data'=>null,
		'size'=>0,
		'headers'=>array(),
		'mime'=>'application/octet-stream',
	);
	if($url != '') {
		if($fm !== null) {
			$properties = $fm->getProperties();
			$fullUrl = 'http://'.$properties['username'].':'.$properties['password'].'@'.$properties['hostspec'].html_entity_decode($url);
		}else{
			$fullUrl = $url;
		}
		$ch = curl_init($fullUrl);
		curl_setopt($ch,CURLOPT_HTTPHEADER, array('X-FMI-PE-ExtendedPrivilege: tU+xR2RSsdk='));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch,CURLOPT_HEADER, TRUE);
		$data = curl_exec($ch) or die(curl_error($ch));

		$ret['data'] = substr($data, strpos($data, "\r\n\r\n") + 4);
       	$ret['headers'] = fmsHeadersToArray(substr($data, 0, -strlen($ret['data'])));
		$ret['size'] = strlen($ret['data']);
		
		if(isset($ret['headers']['content-type'])) $ret['mime'] = $ret['headers']['content-type'][1];
		if(isset($ret['headers']['content-disposition'])) {
			$ret['name'] = $ret['headers']['content-disposition'][1];
			$ret['name'] = preg_replace('#^.*filename=([^ \t,]+).*$#i','$1',$ret['name']);
		}else{
			$ext = preg_replace('#^.*data\.([^\?]*)\?.*$#','\1',$url);
			$ext = strtolower(trim($ext));
			$ret['name'] = substr(md5($ret['data']),0,6).'.'.$ext;
		}
		
		return $ret;
	}else{
		return false;
	}

}

function fmsFileURL($field,$recid) {
	global $fmsCRYPT_SALT;
	$self = array_shift(explode('?',$_SERVER['PHP_SELF'],2));
	$md5 = md5($field.$fmsCRYPT_SALT.$recid);
	return $self.'?file='.$field.'&recid='.$recid.'&h='.bin2hex(substr(crypt($md5,$fmsCRYPT_SALT),4,12));
}

function fmsHeadersToArray($headers) {
	$headers = str_replace("\r","\n",$headers);
	$headers = str_replace("\n\n","\n",$headers);
	$headers = explode("\n",$headers);
	$ret = array();
	foreach($headers as $header) {
		preg_match('#^([^:\ ]+)[:\ ](.*)$#',$header,$matches);
		if(isset($matches[1]) && isset($matches[2])) {
			$ret[strtolower($matches[1])] = array($header,trim($matches[2]));
		}
	}
	return $ret;
}


function fmsSetPage(&$rs,$name,$max) {
	$skip = 0;
	if(isset($_REQUEST[$name.'_page']))$skip = ($_REQUEST[$name.'_page']-1)*$max;
	if($skip < 0) $skip = 0;
	if($max < 0) $max = 10;
	$rs->setRange($skip,$max);
}

function fmsSetLastPage(&$result,$name,$max) {
	global ${$name.'_last_page'};
	$pages = 1;
	if($max < 0) $max = 10;
	if(!FileMaker::isError($result)) {
		$totalFound = $result->getFoundSetCount();
		$pages = ceil($totalFound / $max);
	}
	${$name.'_last_page'} = $pages;
}

function fmsGetPage($name) {
	if(isset($_REQUEST[$name.'_page']) && (int)$_REQUEST[$name.'_page'] > 1) {
		return (int)$_REQUEST[$name.'_page'];
	}else{
		return 1;
	}
}

function fmsGetPageCount($name) {
	global ${$name.'_last_page'};
	return ${$name.'_last_page'};
}

function fmsFirstPage($name,$max = -1) {
	return fmsPageURL($name,1,$max);
}

function fmsPrevPage($name,$max = -1) {
	$page = 1;
	if(isset($_REQUEST[$name.'_page'])) $page = (int)$_REQUEST[$name.'_page']-1;
	if($page < 1) $page = 1;
	return fmsPageURL($name,$page,$max);
}

function fmsNextPage($name,$max = -1) {
	$page = 2;
	if(isset($_REQUEST[$name.'_page'])) $page = (int)$_REQUEST[$name.'_page']+1;
	if($page < 2) $page = 2;
	if($page > fmsGetPageCount($name)) $page = fmsGetPageCount($name);
	return fmsPageURL($name,$page,$max);
}

function fmsLastPage($name,$max = -1) {
	return fmsPageURL($name,fmsGetPageCount($name),$max);
}

function fmsPageURL($name,$page,$max) {
	global $self_url;
	$ret_url = $self_url;
	$ret_url = fmsUrlVar($ret_url,$name.'_page',$page);
	
	$p2g = fmsPOST2GET();
	if($p2g != '') {
		$ret_url.= '&'.$p2g;
	}
	return $ret_url;
}

function fmsNavBar($name, $settings) {
	if(fmsGetPageCount($name) == 1) return;
	
	$settings = fmsDecodeAdvDialogValues($settings);
	
	$sep = $settings[5];
	
	$page = fmsGetPage($name);
	$total = fmsGetPageCount($name);
	$settings[2] = str_replace(array('#page#','#total#'),array($page,$total),$settings[2]);
	
	$ret = '';
	if($page != 1) {
		$ret.='<a href="'.fmsFirstPage($name).'" class="fms_nav_first">'.$settings[0].'</a>'.$sep;
		$ret.='<a href="'.fmsPrevPage($name).'" class="fms_nav_prev">'.$settings[1].'</a>'.$sep;
	}
	$ret.= $settings[2];
	
	if($page != $total) {
		$ret.=$sep.'<a href="'.fmsNextPage($name).'" class="fms_nav_next">'.$settings[3].'</a>'.$sep;
		$ret.='<a href="'.fmsLastPage($name).'" class="fms_nav_last">'.$settings[4].'</a>';
	}
	
	return '<span class="fms_nav_bar">'.$ret.'</span>';
}

function fmsSortLink($name, $field, $label, $settings) {
	global $self_url;
	$settings = fmsDecodeAdvDialogValues($settings);
	
	$varName = $name.'_sort';
	
	if(fmsGET($varName) && fmsGET($varName) == $field.'.ascending') {
		$varValue = $field.'.descending';
		$label.=$settings[0];
		$settings[3].=';'.$settings[4];
	}else if(fmsGET($varName) && fmsGET($varName) == $field.'.descending') {
		$varValue = $field.'.ascending';
		$label.=$settings[1];
		$settings[3].=';'.$settings[4];
	}else{
		$varValue = $field.'.ascending';
	}
	
	$url = fmsUrlVar($self_url, $varName, $varValue);
	$link = $url;
	
	
	return '<a href="'.$link.'" class="'.$settings[2].'" style="'.$settings[3].'">'.$label.'</span></a>';
}

function fmsSortLink_Process($name) {
	$varName = $name.'_sort';
	$findName = $name.'_find';
	$setting = fmsGET($varName);
	if(!$setting) return;
	$setting = explode('.',$setting,2);
	
	global $$findName;
	
	if($setting[1] == 'ascending') {
		$setting[1] = FILEMAKER_SORT_ASCEND;
	}else{
		$setting[1] = FILEMAKER_SORT_DESCEND;
	}
	$$findName->addSortRule($setting[0],1,$setting[1]);
}

function fmsUrlVar($url,$var,$value) {
	if(strpos($url,'?') == false) {
		return $url.'?'.$var.'='.$value;
	}else{
		if(strpos($url,$var) === false) {
			return $url.'&'.$var.'='.$value;
		}else{
			$var_pos = strpos($url,$var);
			if(preg_match('/'.$var.'=[^&]*/',$url)) {
				return preg_replace('/'.$var.'=[^&]*/',"{$var}={$value}",$url);
			}else{
				return preg_replace('/'.$var.'/',"{$var}={$value}",$url);
			}
		}
	}
}

function fmsRedirect($url) {
	if($url == -1) $url = $_SERVER['HTTP_REFERER'];
	header('Location: '.$url);
	exit();
}

function fmsTrapError($result,$redirect) {
	$redirect = fmsUrlVar($redirect,'errorCode',urlencode($result->code));
	$redirect = fmsUrlVar($redirect,'errorMsg',urlencode($result->getErrorString()));
	fmsRedirect($redirect);
}

function fmsEscape($text, $quoted = false) {
	$escape_chars = '/([@*#?!=<>"])/';
	$text = preg_replace($escape_chars,'\\\${1}',$text);
	if($quoted) {
		return '"'.$text.'"';
	}else{
		return $text;
	}
}

function fmsCheckLogin($connName,$login_page) {
	global $self_url;
	if(!session_id()) session_start();
	fmsCheckLogout();
	if(fmsGET($connName.'_user')) {
		$user = fmsGET($connName.'_user');
		$pass = fmsGET($connName.'_pass');
		$_SESSION[$connName.'_login'] = array('user'=>$user,'pass'=>$pass,'first'=>true);
		return;
	}
	if(!isset($_SESSION[$connName.'_login'])) {
		$_SESSION['login_conn'] = $connName;
		$_SESSION['login_from'] = $self_url;
		session_write_close();
		header('Location: '.$login_page);
		exit();
	}
}

function fmsCheckTableLogin($connName,$login_page) {
	global $self_url;
	if(!session_id()) session_start();
	fmsCheckLogout();
	if(fmsGET($connName.'_user')) {
		$user = fmsGET($connName.'_user');
		$pass = fmsGET($connName.'_pass');
		$_SESSION[$connName.'_tableLogin'] = array('user'=>$user,'pass'=>$pass,'first'=>true);
		return;
	}

	if(!isset($_SESSION[$connName.'_tableLogin'])) {
		$_SESSION['login_conn'] = $connName;
		$_SESSION['login_from'] = $self_url;
		$_SESSION['login_type'] = 'table';
		session_write_close();
		header('Location: '.$login_page);
		exit();
	}

}

function fmsCheckFirstLogin($connName,$login_page,$conn) {
	global $self_url;
	if(!session_id()) session_start();
	if($_SESSION[$connName.'_login']['first'] === true) {		
		$result = $conn->listLayouts();
		if(FileMaker::isError($result)) {
			$_SESSION['login_conn'] = $connName;
			$_SESSION['login_from'] = $self_url;
			unset($_SESSION[$connName.'_login']);
			session_write_close();
			header('Location: '.$login_page.'?errorMsg='.urlencode('Incorrect user name or password'));
			exit();
		}else{
			$_SESSION[$connName.'_login']['first'] = false;
		}
	}
}

function fmsCheckFirstTableLogin($connName,$login_page,$conn) {
	global $self_url;
	
	$settings = 'TableLogin_'.$connName;
	global $$settings;
	$settings = $$settings;
	
	if(!session_id()) session_start();
	if($_SESSION[$connName.'_tableLogin']['first'] === true) {		
		$find = $conn->newFindCommand($settings[0]);
		$find->addFindCriterion($settings[1], '=='.fmsEscape($_SESSION[$connName.'_tableLogin']['user']));
		$find->addFindCriterion($settings[2], '=='.fmsEscape($_SESSION[$connName.'_tableLogin']['pass']));
		$result = $find->execute();
		if(FileMaker::isError($result)) {
			$_SESSION['login_conn'] = $connName;
			$_SESSION['login_from'] = $self_url;
			$_SESSION['login_type'] = 'table';
			unset($_SESSION[$connName.'_login']);
			session_write_close();
			header('Location: '.$login_page.'?errorMsg='.urlencode('Incorrect user name or password'));
			exit();
		}else{
			$_SESSION[$connName.'_login']['first'] = false;
		}
	}
}

function fmsCheckLogout() {
	global $self_url_clean;
	if(isset($_GET['logout'])) {
		$conn = fmsGET('logout');
		if(isset($_SESSION[$conn.'_login'])) {
			unset($_SESSION[$conn.'_login']);
		}
		if(isset($_SESSION[$conn.'_tableLogin'])) {
			unset($_SESSION[$conn.'_tableLogin']);
		}
		session_write_close();
		if(isset($_GET['redirect']) && $_GET['redirect'] != '') {
			header('Location: '.$_GET['redirect']);
		}else{
			header('Location: '.$self_url_clean);
		}
		exit();
	}
}

function fmsPerformLogin() {
	if(!session_id()) session_start();
	fmsCheckLogout();
	if(isset($_POST['login_user'])) {
		$user = fmsPOST('login_user');
		$pass = fmsPOST('login_pass');
		if($user == '' || $pass == '') return 'User Name or Password cannot be blank';
		$conn = $_SESSION['login_conn'];
		if(isset($_SESSION['login_from']) && $_SESSION['login_from'] != '') {
			$from = $_SESSION['login_from'];
		}else if(isset($_POST['defaultURL']) && $_POST['defaultURL'] != '') {
			$from = $_POST['defaultURL'];
		}else{
			$from = 'index.php';
		}
		if(isset($_SESSION['login_type']) && $_SESSION['login_type'] == 'table') {
			$_SESSION[$conn.'_tableLogin'] = array('user'=>$user,'pass'=>$pass,'first'=>true);		
		}else{
			$_SESSION[$conn.'_login'] = array('user'=>$user,'pass'=>$pass,'first'=>true);
		}
		session_write_close();
		header('Location: '.$from);
		exit();
	}
	if(isset($_GET["errorMsg"]) && $_GET["errorMsg"] != '') {
		return $_GET["errorMsg"];
	}else{
		return '';
	}
}

$VALUE_LIST_CACHE = array();
function fmsValueListItems($conn, $layout, $list, $recid = null) {
	if($recid == "") $recid = null;
	global $VALUE_LIST_CACHE;
	
	$cache = fmsGetCache(func_get_args(),$VALUE_LIST_CACHE);
	if($cache !== false) return $cache;

	$layout = $conn->getLayout($layout);
	
	if(FileMaker::isError($layout)) return fmsStoreCache(func_get_args(),$VALUE_LIST_CACHE,array());
	return fmsStoreCache(func_get_args(),$VALUE_LIST_CACHE,$layout->getValueList($list,$recid));
}

function fmsStoreCache($args, &$cache, $data) {
	$args = md5(serialize($args));
	$cache[$args] = $data;
	return $data;
}

function fmsGetCache($args, &$cache) {
	$args = md5(serialize($args));
	if(isset($cache[$args])) return $cache[$args]; else return false;
}

function fmsLogoutLink($conn, $redirect) {
	global $self_url_clean;
	echo $self_url_clean.'?logout='.urlencode($conn);
	if($redirect != '') {
		echo '&redirect='.urlencode($redirect);
	}
}

function fmsPOST($var) {
	if(!isset($_POST[$var])) return false;
	if(get_magic_quotes_gpc()) {
		return stripslashes($_POST[$var]);
	}else{
		return $_POST[$var];
	}
}

function fmsGET($var) {
	if(!isset($_GET[$var])) return false;
	if(get_magic_quotes_gpc()) {
		return stripslashes($_GET[$var]);
	}else{
		return $_GET[$var];
	}
}

function fmsPOST2GET() {
	if(!count($_POST)) return '';
	return 'post_data='.base64_encode(serialize($_POST));
}

function fmsGET2POST() {
	$post = fmsGET('post_data');
	if($post !== false) {
		$post = base64_decode($post);
		$post = unserialize($post);
		foreach($post as $key=>$var) {
			if(!isset($_POST[$key])) $_POST[$key] = $var;
		}
	}
}

function fmsRelatedRecord($row, $related_name) {
	$records = $row->getRelatedSet($related_name);
	if(FileMaker::isError($records) || count($records) == 0) {
		return new fmsDummyRecord();
	}else{
		return array_shift($records);
	}
}

function fmsRelatedSet($row, $related_name) {
	$records = $row->getRelatedSet($related_name);
	if(FileMaker::isError($records) || count($records) == 0) {
		return array();
	}else{
		return $records;
	}
}

class fmsDummyRecord {
	function getField() {
		return '';
	}
}

function fmsCompareSet($list_item, $set) {
	if(!is_array($set)) {
		$set = str_replace("\n","\r",$set);
		$set = str_replace("\r\r","\r",$set);
		$set = explode("\r",$set);
	}
	return in_array($list_item, $set);
}

function fmsCheckboxCombine($var) {
	if(is_array($var)) {
		return implode("\r",$var);
	}else{
		return $var;
	}
}

$fmsMIME_TYPES = array(
'fp3'=>'application/filemaker',
'fp4'=>'application/filemaker',
'fp5'=>'application/filemaker',
'fp6'=>'application/filemaker',
'fp7'=>'application/filemaker',
'fp8'=>'application/filemaker',
'fp9'=>'application/filemaker',
'ez'=>'application/andrew-inset',
'hqx'=>'application/mac-binhex40',
'cpt'=>'application/mac-compactpro',
'doc'=>'application/msword',
'bin'=>'application/octet-stream',
'dms'=>'application/octet-stream',
'lha'=>'application/octet-stream',
'lzh'=>'application/octet-stream',
'exe'=>'application/octet-stream',
'class'=>'application/octet-stream',
'so'=>'application/octet-stream',
'dll'=>'application/octet-stream',
'oda'=>'application/oda',
'pdf'=>'application/pdf',
'ai'=>'application/postscript',
'eps'=>'application/postscript',
'ps'=>'application/postscript',
'rtf'=>'text/rtf',
'smi'=>'application/smil',
'smil'=>'application/smil',
'xls'=>'application/vnd.ms-excel',
'ppt'=>'application/vnd.ms-powerpoint',
'sic'=>'application/vnd.wap.sic',
'slc'=>'application/vnd.wap.slc',
'wbxml'=>'application/vnd.wap.wbxml',
'wmlc'=>'application/vnd.wap.wmlc',
'wmlsc'=>'application/vnd.wap.wmlscriptc',
'bcpio'=>'application/x-bcpio',
'bz2'=>'application/x-bzip2',
'vcd'=>'application/x-cdlink',
'pgn'=>'application/x-chess-pgn',
'cpio'=>'application/x-cpio',
'csh'=>'application/x-csh',
'dcr'=>'application/x-director',
'dir'=>'application/x-director',
'dxr'=>'application/x-director',
'dvi'=>'application/x-dvi',
'spl'=>'application/x-futuresplash',
'gtar'=>'application/x-gtar',
'gz'=>'application/x-gzip',
'tgz'=>'application/x-gzip',
'hdf'=>'application/x-hdf',
'js'=>'application/x-javascript',
'kwd'=>'application/x-kword',
'kwt'=>'application/x-kword',
'ksp'=>'application/x-kspread',
'kpr'=>'application/x-kpresenter',
'kpt'=>'application/x-kpresenter',
'chrt'=>'application/x-kchart',
'kil'=>'application/x-killustrator',
'skp'=>'application/x-koan',
'skd'=>'application/x-koan',
'skt'=>'application/x-koan',
'skm'=>'application/x-koan',
'latex'=>'application/x-latex',
'nc'=>'application/x-netcdf',
'cdf'=>'application/x-netcdf',
'ogg'=>'application/x-ogg',
'rpm'=>'application/x-rpm',
'sh'=>'application/x-sh',
'shar'=>'application/x-shar',
'swf'=>'application/x-shockwave-flash',
'sit'=>'application/x-stuffit',
'sv4cpio'=>'application/x-sv4cpio',
'sv4crc'=>'application/x-sv4crc',
'tar'=>'application/x-tar',
'tcl'=>'application/x-tcl',
'tex'=>'application/x-tex',
'texinfo'=>'application/x-texinfo',
'texi'=>'application/x-texinfo',
't'=>'application/x-troff',
'tr'=>'application/x-troff',
'roff'=>'application/x-troff',
'man'=>'application/x-troff-man',
'me'=>'application/x-troff-me',
'ms'=>'application/x-troff-ms',
'ustar'=>'application/x-ustar',
'src'=>'application/x-wais-source',
'xhtml'=>'application/xhtml+xml',
'xht'=>'application/xhtml+xml',
'zip'=>'application/zip',
'au'=>'audio/basic',
'snd'=>'audio/basic',
'mid'=>'audio/midi',
'midi'=>'audio/midi',
'kar'=>'audio/midi',
'mpga'=>'audio/mpeg',
'mp2'=>'audio/mpeg',
'mp3'=>'audio/mpeg',
'aif'=>'audio/x-aiff',
'aiff'=>'audio/x-aiff',
'aifc'=>'audio/x-aiff',
'm3u'=>'audio/x-mpegurl',
'ram'=>'audio/x-pn-realaudio',
'rm'=>'audio/x-pn-realaudio',
'ra'=>'audio/x-realaudio',
'wav'=>'audio/x-wav',
'pdb'=>'chemical/x-pdb',
'xyz'=>'chemical/x-xyz',
'bmp'=>'image/bmp',
'gif'=>'image/gif',
'ief'=>'image/ief',
'jpeg'=>'image/jpeg',
'jpg'=>'image/jpeg',
'jpe'=>'image/jpeg',
'png'=>'image/png',
'tiff'=>'image/tiff',
'tif'=>'image/tiff',
'djvu'=>'image/vnd.djvu',
'djv'=>'image/vnd.djvu',
'wbmp'=>'image/vnd.wap.wbmp',
'ras'=>'image/x-cmu-raster',
'pnm'=>'image/x-portable-anymap',
'pbm'=>'image/x-portable-bitmap',
'pgm'=>'image/x-portable-graymap',
'ppm'=>'image/x-portable-pixmap',
'rgb'=>'image/x-rgb',
'xbm'=>'image/x-xbitmap',
'xpm'=>'image/x-xpixmap',
'xwd'=>'image/x-xwindowdump',
'igs'=>'model/iges',
'iges'=>'model/iges',
'msh'=>'model/mesh',
'mesh'=>'model/mesh',
'silo'=>'model/mesh',
'wrl'=>'model/vrml',
'vrml'=>'model/vrml',
'css'=>'text/css',
'html'=>'text/html',
'htm'=>'text/html',
'asc'=>'text/plain',
'txt'=>'text/plain',
'c'=>'text/plain',
'C'=>'text/plain',
'h'=>'text/plain',
'cp'=>'text/plain',
'cpp'=>'text/plain',
'c++'=>'text/plain',
'java'=>'text/plain',
'rtx'=>'text/richtext',
'sgml'=>'text/sgml',
'sgm'=>'text/sgml',
'tsv'=>'text/tab-separated-values',
'si'=>'text/vnd.wap.si',
'sl'=>'text/vnd.wap.sl',
'wml'=>'text/vnd.wap.wml',
'wmls'=>'text/vnd.wap.wmlscript',
'etx'=>'text/x-setext',
'xml'=>'text/xml',
'xsl'=>'text/xml',
'mpeg'=>'video/mpeg',
'mpg'=>'video/mpeg',
'mpe'=>'video/mpeg',
'qt'=>'video/quicktime',
'mov'=>'video/quicktime',
'mxu'=>'video/vnd.mpegurl',
'avi'=>'video/x-msvideo',
'movie'=>'video/x-sgi-movie',
'ice'=>'x-conference/x-cooltalk',
);

function fmsDecodeAdvDialogValues($input) {
	$input = explode('/;/',$input);
	foreach($input as $key=>$value) {
		$input[$key] = urldecode($value);
	}
	return $input;
}

function fmsUTF8HTMLEntities($str) {
	return htmlentities($str, ENT_COMPAT, "UTF-8");
}

function fmsPrintDate($format, $fmDate) { fmsPrintDateTime($format, $fmDate); }
function fmsPrintDateTime($fmDate, $format) {
	if(preg_match("#^([0-9]+)/([0-9]+)/([0-9]+) ([0-9]+):([0-9]+):([0-9]+)$#", $fmDate, $m)) {
		$time = mktime($m[4], $m[5], $m[6], $m[1], $m[2], $m[3]);
	}else if(preg_match("#^([0-9]+)/([0-9]+)/([0-9]+)$#", $fmDate, $m)){
		$time = mktime(0, 0, 0, $m[1], $m[2], $m[3]);
	}else if(preg_match("#^([0-9]+):([0-9]+):([0-9]+)$#", $fmDate, $m)){
		$time = mktime($m[1], $m[2], $m[3]);
	}else{
		return '';
	}
	return date($format, $time);
}

?>