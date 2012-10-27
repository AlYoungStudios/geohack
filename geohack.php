<?PHP
/**
 (c) by Magnus Manske (2006)
 Released under GPL
 geo_param.php is (c) 2005, Egil Kvaleberg <egil@kvaleberg.no>, also GPL
  modified by Tanner M. Young of Al Young Studios for the BenHaven Archives, 2012
*/
#include "../common.php";
ob_start ( 'ob_gzhandler' ) ; # Enable gzip compression (mod_gzip)
ini_set ( 'user_agent', 'BenHaven Archives Geohack (+http://www.benhavenarchives.org/g/)' ) ; # Set user agent
set_time_limit ( 20 ) ; # 20 sec on region lookup should be enough for everyone!
#ini_set('display_errors',1);
error_reporting(0);

include 'geo_param.php';
include 'mapsources.php';
#include 'region.php'; 

function get_request ( $key , $default = "" ) {
	if ( isset ( $_REQUEST[$key] ) ) return str_replace ( "\'" , "'" , $_REQUEST[$key] ) ;
	return $default ;
}
function fix_language_code ( $lang , $default = "en" ) {
  $lang = trim ( strtolower ( $lang ) ) ;
  if ( preg_match ( "/^([\-a-z]+)/" , $lang , $l ) ) {
    $lang = $l[0] ;
  } else $lang = $default ; // Fallback
  return $lang ;
}
function get_div_section ($html, $nodeId, $begin = 0) {
	$begin = strpos($html, "<div id=\"".$nodeId."\"", $begin);
	if($begin==false) return '';
	$end=$start=$begin;
	do{
		$end = strpos($html, "</div>", $end+6);
		$start = strpos($html, "<div", $start+4);
	 }while($start!=false && $start < $end );
	 return substr($html, $begin, $end-$begin+6);
}
function make_link ( $lang , $theparams , $r_pagename ) {
	# TODO theparams match characters: %+
	$query = ( $r_pagename ? '&pagename=' . $r_pagename : '' ) ;
	if ( preg_match( '/[^0-9A-Za-z_.:;@$!*(),\/\\-]/', $theparams ) == 0 ) {
		# Short url
		$path = "/g/" . $theparams ;
	} else {
		$path = $_SERVER['SCRIPT_NAME'] ;
		$query = "&language=$lang$query&params=$theparams" ;
	}
	if ( isset ( $_REQUEST['title'] ) ) {
		$query .= '&title=' . htmlspecialchars ( $_REQUEST['title'] ) ;
	}
	if ( $query ) {
		return $path . "?" . substr ( $query , 1 ) ;
	} else {
		return $path ;
	}
}
function get_html ( $request_url ) {
	$context = stream_context_create ( array ( 'http' => array( 'method' => 'GET', 'header' => 'Accept-Encoding: gzip' ) ) ) ;
	$page = file_get_contents ( $request_url, false, $context ) ;
	# ungzip if needed
	if ( $page && substr( $page , 0, 3 ) == "\x1f\x8b\x08" ) {
		return gzinflate ( substr($page, 10, -8)  ) ;
	} else {
		return $page ;
	}
}


# Get everything we need to run
$lang = fix_language_code ( get_request ( 'language' , 'en' ) , '' ) ;
if ( ! $lang ) {
	//header('HTTP/1.1 400 Bad Request');
	echo 'No language given';
	exit;
}
$theparams = htmlspecialchars ( get_request ( 'params' , '' ) ) ;
if ( $theparams == '' ) {
	//header("HTTP/1.1 400 Bad Request");
	echo "No parameters given (<code>&params=</code> is empty or missing)" ;
	exit;
}
$textLocation = '';
if (isset($_REQUEST['location'])===true) {
  $textLocation = explode(',',preg_replace('/[^ ,.A-Za-z\d]/','',str_replace('_',' ',$_REQUEST['location'])));
  foreach ($textLocation as $key => &$value) {
    $value = trim($value);
    $value = '<a href="http://www.benhavenarchives.org/wiki/' . $value . '">' . $value . '</a>';
  }
  $textLocation = implode(', ',$textLocation);
}
# Using REFERER as a last resort for pagename
preg_match("/https?:\/\/[^\/]+\/?(?:wiki\/|w\/index.php\?.*?title=)([^&?#{|}\[\]]+)/", ( $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '' ), $ref_match );
$r_pagename = get_request( 'pagename', ( $ref_match ? urldecode( $ref_match[1] ) : '' ) ) ;
$r_title = get_request( 'title', str_replace( '_', ' ', $r_pagename ) ) ;
$r_pagename = htmlspecialchars ( $r_pagename ) ;
$r_title = htmlspecialchars ( $r_title ) ;

# Initilize Map Sources
$md = new map_sources ( $theparams , "Some title" ) ;

if (($e = $md->p->get_error()) != "") {
	//header("HTTP/1.1 400 Bad Request");
	echo '<p>' . htmlspecialchars( $e ) . '</p>';
	exit;
}

# (auto-)detect region
$region_name = false ;
$globe = '';
$nlzoom = '';
foreach ( $md->p->pieces AS $k => $v ) {
	if ( substr ( $v , 0 , 7 ) == "region:" )
		$region_name = strtoupper ( substr ( $v , 7 ) ) ;
	else if ( substr ( $v , 0 , 6) == "globe:" )
		$globe = strtolower ( substr ( $v , 6 ) );
	else if ( substr ( $v , 0 , 5) == "zoom:" )
		$nlzoom = strtolower ( substr ( $v , 5 ) );
}
$lat = $md->p->make_minsec($md->p->latdeg);
$lon = $md->p->make_minsec($md->p->londeg);
if ( $region_name === false && ! ( $globe != '' && $globe != 'earth' ) ) {
	$url = "http://www.benhavenarchives.org/g/region.php?tsv&lat=" . $lat['deg'] . "&long=" . $lon['deg'] ;
	$ctx = stream_context_create(array('http' => array('timeout' => 5))); 
	$region = @explode ( "\t" , file_get_contents ( $url , 0 , $ctx ) ) ;
	if ( count ( $region ) > 2 ) {
		array_shift ( $region ) ;
		array_shift ( $region ) ;
		if ( count ( $region ) > 0 ) {
			$region_name = strtoupper ( array_shift ( $region ) ) ;
			if ( $region_name != "" ) $md->p->pieces[] = "region:" . $region_name ;
		}
	}
}
$region_name = array_shift ( explode ( '-' , $region_name ) ) ;
$region_name = array_shift ( explode ( '_' , $region_name ) ) ;

# Read template
#$pagename = get_request ( "altpage" , "Template:GeoTemplate" ) ;
$pagename = 'Template:GeoTemplate';
if ( $globe != '' && $globe != 'earth' ) {
	$pagename .= '/' . str_replace ( "&", "%26", $globe ) ;
}
if ( ! get_request ( 'project' , '' ) ) {
	$request_url = 'http://www.benhavenarchives.org/wiki/'.$pagename;
} else {
	$request_url = 'http://www.benhavenarchives.org/wiki/'.$pagename;
	$lang = 'en';
}

$page =  file_get_contents($request_url);

if ( $page === false || $page == '' ) {
	# fall back to the BenHaven Archives
    $page = file_get_contents('http://www.benhavenarchives.org/wiki/'.$pagename);
}

if ( $page === false || $page == '' ) {
	//header('HTTP/1.1 502 Bad Gateway');
	echo '<!DOCTYPE html>';
	echo '<html><head><title>502 Bad Gateway</title></head><body>';
	echo "Failed to open <a href=\"{$request_url}\">{$request_url}</a>.";
	echo '</body></html>';
	exit;
}

$page = str_replace ( ' href="/w' , " href=\"//www.benhavenarchives.org/w" , $page ) ;
$actions = str_replace ( 'id="p-cactions"', '', get_div_section ( $page, "p-cactions" ) ) ;
$languages = preg_replace_callback ( '/ href="\/\/www\.benhavenarchives\.org\/wiki\/[^"]*/', create_function(
	'$match',
	'global $theparams, $r_pagename;return " href=\"" . make_link ( $match[1] , $theparams , $r_pagename );'
), get_div_section ( $page, "p-lang" ) ) ;

# Remove edit links
do {
	$op = $page ;
	$p = explode ( '<span class="editsection"' , $page , 2 ) ;
	if ( count ( $p ) == 1 ) continue ;
	$page = array_shift ( $p ) ;
	$p = explode ( '</span>' , array_pop ( $p ) , 2 ) ;
	$page .= array_pop ( $p ) ;
} while ( $op != $page ) ;

# Build the page
//$page = array_pop ( explode ( '<div id="content">' , $page , 2 ) ) ;
//$page = array_shift ( explode ( '<div id="mw-hdead"' , $page , 2 ) ) ;
# XXX There should be failsafe branch here
$md->thetext = $page ;
$page = $md->build_output () ;

# Ugly hacks
$page = str_replace ('{nztmeasting}','0',$page);
$page = str_replace ('{nztmnorthing}','0',$page);

# Localized services
$locmaps = get_div_section ( $page, 'GEOTEMPLATE-'.$region_name );
$locinsert =  get_div_section ( $page, 'GEOTEMPLATE-LOCAL' );
if($locmaps && $locinsert){
	$page = str_replace($locmaps, '', $page);
	$page = str_replace($locinsert, $locmaps, $page);
	$page = str_replace(get_div_section ( $page, 'GEOTEMPLATE-REGIONS' ), '', $page);
}
# FIXME better titles
$mytitle = 'The BenHaven Archives';
if ($r_title) $mytitle = $r_title.' Location - '.$mytitle;
elseif ($r_pagename) $mytitle = str_replace('_', ' ', $r_pagename ).' - '.$mytitle;
elseif ($lat && $lon) $mytitle = $lat['deg'].'; '.$lon['deg'].$mytitle;
//header('Cache-Control: max-age=86400');
echo str_replace(array($pagename,'id="mw-head"'),array($r_title.' (Location)','id="mw-head" style="display:none"'),$page);
?>