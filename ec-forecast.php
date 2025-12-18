<?php
// PHP script by Ken True, webmaster@saratoga-weather.org
// ec-forecast.php  version 1.00 - 10-Aug-2006
// Version 1.01 - 14-Dec-2006 - fixed script to handle changes in EC website
// Version 1.02 - 14-Dec-2006 - fixed problems with include mode/no printing.
// Version 1.03 - 14-Mar-2007 - fixed to handle changes to EC website
// Version 1.04 - 16-May-2007 - fixed to handle changes to EC website
// Version 1.05 - 15-Jun-2007 - handle printable/regular EC URL + table, debugging improvements
// Version 1.06 - 27-Jun-2007 - added parsing/printing for alerts/watches/warnings $alertstring
// Version 1.07 - 06-Aug-2007 - corrected php delim at top of file (missing php)
// Version 1.08 - 14-Dec-2007 - added optional current conditions report table $currentConditions
// Version 2.00 - 24-Jan-2008 - major rewrite to handle many changes to EC forecast website + new icons
// Version 2.01 - 26-Jan-2008 - added 'Air Quality Health Index' to optional conditions display
// Version 2.02 - 26-Feb-2008 - added support for Carterlake/WD/PHP template settings.
// Version 2.03 - 01-Mar-2008 - fixed to handle changes to EC website for conditions
// Version 2.04 - 19-Mar-2008 - fixed to handle changes to EC website for forecast
// Version 2.05 - 19-Mar-2008 - corrected extraction of Update date from EC website
// Version 2.06 - 05-Jun-2008 - fixed to handle changes to EC website for historical conditions
// Version 2.07 - 21-Dec-2008 - added printing/formatting for historical conditions
// Version 2.08 - 10-Mar-2009 - fixed to handle change to EC website for abnormal temperature trend indicator
// Version 2.09 - 09-Nov-2009 - fixed missing-space in warning titles problem
// Version 2.10 - 10-Nov-2009 - fixed to handle changes to EC website for normals conditions display
// Version 2.11 - 26-May-2010 - fixed to handle changes to EC website for humidex/wind-chill
// Version 2.12 - 19-Feb-2011 - added formatting to EC watch/warning/ended message alerts
// Version 2.13 - 30-May-2011 - fixed handling of $SITE['fcsticonsdirEC'] when used with template
// Version 2.14 - 16-Apr-2013 - fixes for changes in EC website structure
// Version 2.15 - 17-Apr-2013 - fixes for $title display with new EC website structure
// Version 2.16 - 12-May-2013 - added settings to display days of week w/o day month for icons and detail area; icon type selection for .gif/.png; added debugging code for EC website fetch; added multi-forecast capability
// Version 2.17 - 15-May-2013 - fixes for changes in EC website structure
// Version 3.00 - 17-Oct-2014 - redesign for major changes in EC website structure
// Version 3.01 - 29-Oct-2014 - fix for 'Normals' extract and text forecast
// Version 3.02 - 29-Apr-2015 - fixes for changes in EC website structure+new temps processing
// Version 3.03 - 01-Dec-2015 - fixes for current conditions based on EC website changes
// Version 3.04 - 14-Dec-2015 - fixes for changes in EC website structure (chunked+gzipped response)
// Version 3.05 - 16-Dec-2015 - fixes for changes in temperature forecast wording+extraction
// Version 4.00 - 22-Oct-2016 - major redesign for EC website changes+curl fetch
// Version 4.01 - 27-Oct-2016 - fix for conditions icon extract, yesterday data, use curl fetch for URL
// Version 4.02 - 22-Feb-2017 - force HTTPS to EC website, improved error handling
// Version 4.03 - 31-Aug-2017 - fixes for changes in EC website structure
// Version 5.00 - 27-Sep-2017 - major redesign to use EC XML forecast data instead of website scraping
// Version 5.01 - 07-Nov-2017 - added windchill display to conditions box
// Version 5.02 - 20-Nov-2017 - added wind-gust display to conditions box and hourly display, fix no conds icon issue
// Version 5.03 - 16-Oct-2019 - change XML access URL to https on EC site
// Version 5.04 - 27-Dec-2022 - fixes for PHP 8.2
// Version 5.05 - 09-Feb-2023 - fixes for .png icons and PHP 8.2
// Version 5.06 - 18-May-2023 - added 'advisory' alert display same as 'statement' type
// Version 5.07 - 02-Jul-2024 - fixes for changes in EC XML returns w/o almanac section
// Version 6.00 - 26-Oct-2024 - rewrite to use new EC JSON return instead of XML citypage
// Version 6.01 - 26-Oct-2024 - fix for hourly display icons when using .png icons
// Version 6.02 - 28-Oct-2024 - fixed alert display when there are highway alerts
// Version 7.00 - 18-Dec-2025 - major update to support color-coded alerts from EC
//
  $Version = "V7.00 - 18-Dec-2025";

// error_reporting(E_ALL); // uncomment for checking errata in code
//---------------------------------------------------------------------------------------------
// NOTE: as of V5.00, the separate file 'ec-forecast-lookup.txt' is REQUIRED to be in the
//       same directory as this script.  It provides the lookup for EC page-id to XML file id.
//---------------------------------------------------------------------------------------------
// NOTE: Version 6.00+ allows use of old and new format URLS for ECURL entries in the script
//
// OLD: $ECURL = 'https://weather.gc.ca/city/pages/on-77_metric_e.html';  # Old format
// NEW  $ECURL = 'https://weather.gc.ca/en/location/index.html?coords=43.258,-79.869'; # New Format
//
//---------------------------------------------------------------------------------------------
//* 
// Settings:
// --------- start of settings ----------
// you need to set the $ECURL to the printable forecast for your area
//
//  Go to https://weather.gc.ca/ and select your language English or French
//
//  Search for your location by name.
//
//  Copy the URL from the browser address bar, and paste it into $ECURL below.
//  The URL may be from either the weather.gc.ca or meteo.gc.ca sites.
//  Examples:
//  English: https://weather.gc.ca/en/location/index.html?coords=43.258,-79.869 or 
//  French:  https://meteo.gc.ca/fr/location/index.html?coords=43.258,-79.869   
//
//$ECURL = 'https://weather.gc.ca/city/pages/on-77_metric_e.html';  # Old format
$ECURL = 'https://weather.gc.ca/en/location/index.html?coords=43.258,-79.869'; # New Format
//
$defaultLang = 'en';  // set to 'fr' for french default language
//                    // set to 'en' for english default language
//
$printIt = true;    // set to false if you want to manually print on your page
//
$showConditions = true; // set to true to show current conditions box
$showAlmanac    = true; // set to true to show almanac box
$show24hour     = true; // set to true to show the 24 hour forecast box
//
$imagedirEC = "ec-icons/";
//directory with your image icons WITH the trailing slash
//
$cacheName = 'ec-forecast.json'; // note: will be changed to include lat-long/language
$cacheFileDir = './';   // directory to store cache files (with trailing / )
//
$refetchSeconds = 600;  // get new forecast from EC 
//                         every 10 minutes (600 seconds)
//
//$LINKtarget = 'target="_blank"';  // to launch new link in new page
$LINKtarget = '';  // to launch new link in same page
//
$charsetOutput = 'ISO-8859-1'; // default character encoding of output
// new settings with V2.16 ------ all have Settings.php overrides available for template use
/* deprecated in V5.00+ .. data not in XML feeds
$doIconDayDate = false;        // =false; Icon names = day of week. =true; icon names as Day dd Mon
$doDetailDayDate = false;      // =false; for day name only, =true; detail day as name, nn mon.
*/ 
$iconTypeEC = '.gif';            // ='.gif' or ='.png' for ec-icons file type

// The optional multi-city forecast .. make sure the first entry is for the $ECURL location
// The contents will be replaced by $SITE['ECforecasts'] if specified in your Settings.php
//*
$ECforecasts = array(
 // Location|forecast-URL  (separated by | characters)
  'St. Catharines, ON|https://weather.gc.ca/en/location/index.html?coords=43.160,-79.245', // St. Catharines, ON
  'Hamilton, ON|https://weather.gc.ca/city/pages/on-77_metric_e.html',
  'Hamilton, ON new|https://weather.gc.ca/en/location/index.html?coords=43.258,-79.869',
  'MontrÈal, QC|https://meteo.gc.ca/city/pages/qc-147_metric_f.html',
  'MontrÈal, QC new|https://meteo.gc.ca/fr/location/index.html?coords=45.529,-73.562',
  'St. John\'s, NL new|https://meteo.gc.ca/fr/location/index.html?coords=47.558,-52.717',
  'Victoria, BC new|https://weather.gc.ca/en/location/index.html?coords=48.433,-123.362',
  'Vancouver, BC new|https://weather.gc.ca/en/location/index.html?coords=49.245,-123.115',
  'Regina, SK|https://weather.gc.ca/en/location/index.html?coords=50.450,-104.617',
  'Lethbridge, AB|https://weather.gc.ca/en/location/index.html?coords=49.693,-112.835',
  'Merritt, BC|https://weather.gc.ca/en/location/index.html?coords=50.111,-120.790',
  'Val-d\'Or, QC|https://weather.gc.ca/en/location/index.html?coords=48.105,-77.796',
  'Boissevain, MB|https://weather.gc.ca/en/location/index.html?coords=49.231,-100.055',
); 
//*/
// end of new settings with V2.16 ------

// ---------- end of settings -----------
//---------------------------------------------------------------------------------------------
// overrides from Settings.php if available
global $SITE;
if (isset($SITE['fcsturlEC']))      {$ECURL = $SITE['fcsturlEC'];}
if (isset($SITE['defaultlang']))    {$defaultLang = $SITE['defaultlang'];}
if (isset($SITE['LINKtarget']))     {$LINKtarget = $SITE['LINKtarget'];}
if (isset($SITE['fcsticonsdirEC'])) {$imagedirEC = $SITE['fcsticonsdirEC'];}
if (isset($SITE['charset']))        {$charsetOutput = strtoupper($SITE['charset']); }
// following overrides are new with V2.16
if (isset($SITE['ECiconType']))     {$iconTypeEC = $SITE['ECiconType']; }         // new with V2.16
/* deprecated in V5.00+ .. data not in XML feeds
if (isset($SITE['ECiconDayDate']))  {$doIconDayDate = $SITE['ECiconDayDate']; } // new with V2.16
if (isset($SITE['ECdetailDayDate'])){$doDetailDayDate = $SITE['ECdetailDayDate']; } // new with V2.16
*/
if (isset($SITE['ECforecasts']))    {$ECforecasts = $SITE['ECforecasts']; }     // new with V2.16
if (isset($SITE['cacheFileDir']))   {$cacheFileDir = $SITE['cacheFileDir']; }   // new with V2.16
if (isset($SITE['ECshowConditions'])) {$showConditions = $SITE['ECshowConditions'];} // new 5.00
if (isset($SITE['ECshowAlmanac']))    {$showAlmanac = $SITE['ECshowAlmanac'];}       // new 5.00
if (isset($SITE['ECshow24hour']))     {$show24hour = $SITE['ECshow24hour'];}         // new 5.00
// end of overrides from Settings.php if available
//---------------------------------------------------------------------------------------------
//
// The program will return with bits of the forecast items in various 
// PHP variables:
// With V4.00 and an EC site redesign, the EC now returns 12hour forecast periods
//  $i = 0 to 11  with 0=now, 1=next period, 2=period+2, 3=period+3, 4=period+4 (etc.)
//
// $forecasttitles[$i] = Day/night day of week for forecast
// $forecasticon[$i] = <img> statement for forecast icon
// $forecasttemp[$i] = Red Hi, Blue Lo temperature(s)
// $forecasttext[$i] = Summary of forecast for icon
//
// $forecastdays[$n] = Day/night day of week for detail forecast
// $forecastdetail[$n] = detailed forecast text
//
// Also returned are these useful variables filled in:
// $title = updated/issued text in language selected
// $textfcsthead = 'Current Forecast' or 'Textes des prÈvisions'
//
// $weather = fully formed html table with two rows of Icons and text 
// $textforecast = fully formed <div> with text forecast as <dl>
//
// $alertstring = styled HTML with current advisories/warnings with dropdown/expand
// $currentConditions = table with current conditions at EC forecast site
// $almanac = styled box with Average/Extreme data for the EC forecast site (V5.00)
// $forecast24h = styled table with rolling 24hr forecast details (V5.00)
//
// you can set $printIT = false; and just echo/print the variables above
//  in your page to precisely position them.  Or use $forecast[$i] to
//  print just one of the items where you need it.
// 
//  I'd recommend you NOT change any of the main code.  You can do styling
//  for the results by using CSS.  See the companion test page
//  ec-forecast-testpage.php to demonstrate CSS styling of results.
//
//---------------------------------------------------------------------------------------------
// ---------- main code -----------------
if (isset($_REQUEST['sce']) and strtolower($_REQUEST['sce']) == 'view' ) {
//--self downloader --
   $filenameReal = __FILE__;
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain,charset=ISO-8859-1");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');
   readfile($filenameReal);
   exit;
}
// initialize arrays and expected variables
$conditions = array();
$forecasticon = array();
$forecasttemp  = array();
$forecasttemptype = array();
$forecasttempabn = array();
$forecasttempHigh  = array();
$forecasttempLow  = array();
$forecastpop   = array();
$forecasttitles = array();
$forecast = array();
$forecasttemptxt = array();
$forecastrealday = array();
$forecasthours   = array();
$updated = 'unknown';
$currentConditions = ''; // HTML for table of current conditions
$alertstring = ''; // HTML+Javascript for alert displays
$forecast24h = ''; // HTML for 24hour forecast table
$almanac = ''; // HTML for almanac table

$charsetInput = 'UTF-8'; // they claim ISO-8859-1, but it's really UTF-8 in French XML.  Sigh.
  
if (! isset($PHP_SELF) ) { $PHP_SELF = $_SERVER['PHP_SELF']; }
if(!function_exists('langtransstr')) {
	// shim function if not running in template set
	function langtransstr($input) { return($input); }
}

$t = pathinfo($PHP_SELF);  // get our program name for the HTML comments
$Program = $t['basename'];
$Status = "<!-- ec-forecast.php - $Version -->\n";

if (! isset($doInclude)) { $doInclude = false ; }
if( (isset($_REQUEST['inc']) and strtolower($_REQUEST['inc']) == 'y') or 
    $doInclude )
 {$printIt = false;}

if(!isset($_REQUEST['lang'])) { $_REQUEST['lang'] = '';}
// overrides from calling page
if(isset($doPrint))          {$printIt        = $doPrint; }
if(isset($doShowConditions)) {$showConditions = $doShowConditions;}
if(isset($doShowAlmanac))    {$showAlmanac    = $doShowAlmanac; }
if(isset($doShow24hour))     {$show24hour     = $doShow24hour;  }

if(isset($_REQUEST['lang'])) {
  $Lang = strtolower($_REQUEST['lang']);
} else {
  $Lang = '';
}
if (isset($doLang)) {$Lang = $doLang;};
if (! $Lang) {$Lang = $defaultLang;};

$doDebug = (isset($_REQUEST['debug']) and strtolower($_REQUEST['debug']) == 'y')?true:false;

if ($Lang == 'fr') {
  $LMode = 'f';
  $ECNAME = "Environnement Canada";
  $ECHEAD = 'PrÈvisions';
  $abnormalString = '<p class="ECforecast"><strong>*</strong> - Indique une tendance inverse de la tempÈrature.</p>' . "\n";
} else {
  $Lang = 'en';
  $LMode = 'e';
  $ECNAME = "Environment Canada";
  $ECHEAD = 'Forecast';
  $abnormalString = '<p class="ECforecast"><strong>*</strong> - Denotes an abnormal temperature trend.</p>' . "\n";
}

// get the selected forecast location code
$haveIndex = '0';
if (!empty($_GET['z']) && preg_match("/^[0-9]+$/i", htmlspecialchars($_GET['z']))) {
  $haveIndex = htmlspecialchars(strip_tags($_GET['z']));  // valid zone syntax from input
} 

if(!isset($ECforecasts[0])) {
	// print "<!-- making NWSforecasts array default -->\n";
	$ECforecasts = array("|$ECURL"); // create default entry
}
//  print "<!-- ECforecasts\n".print_r($ECforecasts,true). " -->\n";
// Set the default zone. The first entry in the $SITE['ECforecasts'] array.
list($Nl,$Nn) = explode('|',$ECforecasts[0].'|||');
$FCSTlocation = $Nl;
$ECURL = $Nn;

if(!isset($ECforecasts[$haveIndex])) {
	$haveIndex = 0;
}

// locations added to the drop down menu and set selected zone values
$dDownMenu = '';
for ($m=0;$m<count($ECforecasts);$m++) { // for each locations
  list($Nlocation,$Nname) = explode('|',$ECforecasts[$m].'|||');
  $seltext = '';
  if($haveIndex == $m) {
    $FCSTlocation = $Nlocation;
    $ECURL = $Nname;
	$seltext = ' selected="selected" ';
  }
  $dDownMenu .= "     <option value=\"$m\"$seltext>".langtransstr($Nlocation)."</option>\n";
}

// build the drop down menu
$ddMenu = '';
// create menu if at least two locations are listed in the array
if (isset($ECforecasts[0]) and isset($ECforecasts[1])) {
	$ddMenu .= '<table style="border:none;width:99%"><tr align="center">
      <td style="font-size: 14px; font-family: Arial, Helvetica, sans-serif">
      <script type="text/javascript">
        <!--
        function menu_goto( menuform ){
         selecteditem = menuform.logfile.selectedIndex ;
         logfile = menuform.logfile.options[ selecteditem ].value ;
         if (logfile.length != 0) {
          location.href = logfile ;
         }
        }
        //-->
      </script>
     <form action="" method="get">
     <p><select name="z" onchange="this.form.submit()">
     <option value=""> - '.langtransstr('Select Forecast').' - </option>
' . $dDownMenu .
		$ddMenu . '     </select></p>
     <div><noscript><pre><input name="submit" type="submit" value="'.langtransstr('Get Forecast').'" /></pre></noscript></div>
     </form>
    </td>
   </tr>
 </table>
';
}
if(file_exists('ec-forecast-lookup.txt')) {
	include_once('ec-forecast-lookup.txt'); // load lookup table
} else {
	print $Status;
	print "<p>ec-forecast.php ERROR: this script requires 'ec-forecast-lookup.txt' in the same directory, and it is not available.</p>\n";
	return(false);
}
/*
// we need to use an array like this, but sometimes this script will not be
// saved as ASCII/ISO-8859-1, and the array of characters gets garbled.

$trantab = array(
  'ISO' =>
	array('¿','‡','¬','‚','∆','Ê',
			 '«','Á',
			 '…','È','»','Ë',' ','Í','À','Î',
			 'Œ','Ó','œ','Ô',
			 '‘','Ù','å','ú',
			 'Ÿ','˘','€','˚','‹','¸',
			 'ü','ˇ'),
	// UTF-8 characters represented as ISO-8859-1
	'UTF' =>
	array('√Ä','√†','√Ç','√¢','√Ü','√¶',
			 '√á','√ß',
			 '√â','√©','√à','√®','√ä','√™','√ã','√´',
			 '√é','√Æ','√è','√Ø',
			 '√î','√¥','≈í','≈ì',
			 '√ô','√π','√õ','√ª','√ú','√º',
			 '≈∏','√ø'),
);
// so we used the following to encode it:

$serialized = serialize($trantab);
$base64 = base64_encode($serialized);

// and used the output of the base64_encode in the define() statement below.
// then we reconstitute the array with perfect fidelity using 
//   $trantab = unserialize(base64_decode(ISO_UTF_ARRAY));
// below.
*/
if(!defined('ISO_UTF_ARRAY')) {
define('ISO_UTF_ARRAY',
'YToyOntzOjM6IklTTyI7YTozMjp7aTowO3M6MToiwCI7aToxO3M6MToi4CI7aToyO3M6MToi 
wiI7aTozO3M6MToi4iI7aTo0O3M6MToixiI7aTo1O3M6MToi5iI7aTo2O3M6MToixyI7aTo3 
O3M6MToi5yI7aTo4O3M6MToiySI7aTo5O3M6MToi6SI7aToxMDtzOjE6IsgiO2k6MTE7czox 
OiLoIjtpOjEyO3M6MToiyiI7aToxMztzOjE6IuoiO2k6MTQ7czoxOiLLIjtpOjE1O3M6MToi 
6yI7aToxNjtzOjE6Is4iO2k6MTc7czoxOiLuIjtpOjE4O3M6MToizyI7aToxOTtzOjE6Iu8i 
O2k6MjA7czoxOiLUIjtpOjIxO3M6MToi9CI7aToyMjtzOjE6IowiO2k6MjM7czoxOiKcIjtp 
OjI0O3M6MToi2SI7aToyNTtzOjE6IvkiO2k6MjY7czoxOiLbIjtpOjI3O3M6MToi+yI7aToy 
ODtzOjE6ItwiO2k6Mjk7czoxOiL8IjtpOjMwO3M6MToinyI7aTozMTtzOjE6Iv8iO31zOjM6 
IlVURiI7YTozMjp7aTowO3M6Mjoiw4AiO2k6MTtzOjI6IsOgIjtpOjI7czoyOiLDgiI7aToz 
O3M6Mjoiw6IiO2k6NDtzOjI6IsOGIjtpOjU7czoyOiLDpiI7aTo2O3M6Mjoiw4ciO2k6Nztz 
OjI6IsOnIjtpOjg7czoyOiLDiSI7aTo5O3M6Mjoiw6kiO2k6MTA7czoyOiLDiCI7aToxMTtz 
OjI6IsOoIjtpOjEyO3M6Mjoiw4oiO2k6MTM7czoyOiLDqiI7aToxNDtzOjI6IsOLIjtpOjE1 
O3M6Mjoiw6siO2k6MTY7czoyOiLDjiI7aToxNztzOjI6IsOuIjtpOjE4O3M6Mjoiw48iO2k6 
MTk7czoyOiLDryI7aToyMDtzOjI6IsOUIjtpOjIxO3M6Mjoiw7QiO2k6MjI7czoyOiLFkiI7 
aToyMztzOjI6IsWTIjtpOjI0O3M6Mjoiw5kiO2k6MjU7czoyOiLDuSI7aToyNjtzOjI6IsOb 
IjtpOjI3O3M6Mjoiw7siO2k6Mjg7czoyOiLDnCI7aToyOTtzOjI6IsO8IjtpOjMwO3M6Mjoi 
xbgiO2k6MzE7czoyOiLDvyI7fX0=');
}
//reconstitute our trantab from the base64 encoded serialized value
//so we won't have issues if someone inadvertantly saves this script as
// UTF-8 instead of ASCII/ISO-8859-1
//

global $trantab;
$trantab = unserialize(base64_decode(ISO_UTF_ARRAY));

// support both french and english caches
$ECURL = preg_replace('|weatheroffice|i','weather',$ECURL); // autochange Old EC URL if present
$ECURL = preg_replace('|_.\.html|',"_$LMode.html",$ECURL);
$ECURL = preg_replace('|http://|i','https://',$ECURL); // force HTTPS access

# compute new URL based on old URL format if necessary

list($ECURL,$PAGEURL,$cacheName) = gen_ecurl($ECURL);

// force refresh of cache
if (isset($_REQUEST['cache'])) { $refetchSeconds = 1; }


function gen_ecurl($URL) {
  # convert old to new format URLS and process new format URLs
  # to generate data and link URLs
  
  global $Lang,$EClookup,$Status,$cacheFileDir;
  # input URLs:
  # 
  
  # Old Format
  # $ECURL = 'https://weather.gc.ca/city/pages/on-107_metric_e.html';  # Old format
  #           https://meteo.gc.ca/city/pages/on-107_metric_f.html
  
  #New format
  #           https://weather.gc.ca/en/location/index.html?coords=49.245,-123.115
  #           https://meteo.gc.ca/fr/location/index.html?coords=49.245,-123.115
  
  # return:
  # $ECURL = 'https://weather.gc.ca/api/app/en/Location/49.245,-123.115?type=city'; 
  #           https://meteo.gc.ca/api/app/fr/Location/49.245,-123.115?type=city
  
  $Status .= "<!-- gen_ecurl: URL='$URL' -->\n";
  
  if(strpos($URL,'/pages/')!==false) { # handle OLD forma
    # OLD format URL
    $U= parse_url($URL);
    $host = $U['host'];
    $path = $U['path'];
    $P = pathinfo($path);
    $pp = explode('_',$P['filename'].'_');
    $id = $pp[0]; # gets the PP-nnn code from old url.
    
    if(isset($EClookup[$id])){
      #  'ab-1' => 'AB|s0000493|Cochrane|Cochrane|51.21|-114.47',

      list($pv,$scode,$ENname,$FRname,$lat,$lon) = explode('|',$EClookup[$id]);
      $latlon = "$lat,$lon";
     } else {
      $Status .= "<!-- Warning: unable to find $id in EClookup table -->\n";
    }
    
    $cache = $cacheFileDir.'ecforecast-'.str_replace('.','_',$latlon)."-$Lang.json";
   
    if($Lang == 'fr') {
      $ECURL = "https://meteo.gc.ca/api/app/v3/fr/Location/$latlon?type=city";
      $PGURL = "https://meteo.gc.ca/fr/location/index.html?coords=$latlon";
      $Status .= "<!-- using $ECURL for French forecast -->\n";
      $Status .= "<!-- using $PGURL for page -->\n";
    }
    if($Lang == 'en') {
     $ECURL = "https://weather.gc.ca/api/app/v3/en/Location/$latlon?type=city";
     $PGURL = "https://weather.gc.ca/en/location/index.html?coords=$latlon";
     $Status .= "<!-- using $ECURL for English forecast -->\n";
     $Status .= "<!-- using $PGURL for page -->\n";
    }
    $Status  .= "<!-- cache '$cache' -->\n";
    return(array($ECURL,$PGURL,$cache));
  }

  if(strpos($URL,'/location/')!==false) { # Handle new format URLs
    # NEW format URL
    $U= parse_url($URL);
    $host = $U['host'];
    $path = $U['path'];
    $query = $U['query'];
    
    $latlon = str_replace('coords=','',$query);
    $cache = $cacheFileDir.'ecforecast-'.str_replace('.','_',$latlon)."-$Lang.json";
   
    if($Lang == 'fr') {
      $ECURL = "https://meteo.gc.ca/api/app/v3/fr/Location/$latlon?type=city";
      $PGURL = "https://meteo.gc.ca/fr/location/index.html?coords=$latlon";
      $Status .= "<!-- using $ECURL for French forecast -->\n";
      $Status .= "<!-- using $PGURL for page -->\n";
    }
    if($Lang == 'en') {
     $ECURL = "https://weather.gc.ca/api/app/v3/en/Location/$latlon?type=city";
     $PGURL = "https://weather.gc.ca/en/location/index.html?coords=$latlon";
     $Status .= "<!-- using $ECURL for English forecast -->\n";
     $Status .= "<!-- using $PGURL for page -->\n";
    }
    $Status  .= "<!-- cache '$cache' -->\n";
    return(array($ECURL,$PGURL,$cache));
  }
  
}

if($ECURL === false) {
	print $Status;
	print "<p>ec-forecast.php ERROR: '$FCSTlocation' has an invalid EC page URL '$ECURL'.<br/> The corresponding JSON weather data file is not found for page ID='$ECpgcode'.</p>\n"; 
	return(false);
}
$cacheAge = (file_exists($cacheName))?time()-filemtime($cacheName):9999999;

//---------------------------------------------------------------------------------------------

// load the XML from the EC or cache
$total_time = 0.0;
if (file_exists($cacheName) and $cacheAge < $refetchSeconds) {
		$Status .= "<!-- using Cached version from $cacheName age=$cacheAge seconds old -->\n";
    $content = file_get_contents($cacheName);
	} else {
		$Status .= "<!-- refreshing $cacheName age=$cacheAge seconds old -->\n";
		$Status .= "<!-- ECURL='$ECURL'\n     EC   PAGEURL='$PAGEURL' -->\n";
		$time_start = ECF_fetch_microtime();
		$rawhtml = ECF_fetch_URL($ECURL,false);
	  $time_stop = ECF_fetch_microtime();
	  $total_time += ($time_stop - $time_start);
	  $time_fetch = sprintf("%01.3f",round($time_stop - $time_start,3));
		$RC = '';
	if (preg_match("|^HTTP\/\S+ (.*)\r\n|",$rawhtml,$matches)) {
		$RC = trim($matches[1]);
	}
	$Status .= "<!-- time to fetch: $time_fetch sec (RC=$RC) -->\n";
	
	if(preg_match('|30\d |i',$RC)) { //oops.. a redirect.. retry the new location
		sleep(2); // wait two seconds and retry
		preg_match('|Location: (.*)\r\n|',$rawhtml,$matches);
		if(isset($matches[1])) {$ECURL = $matches[1];} // update the URL
		$time_start = ECF_fetch_microtime();
		$rawhtml = ECF_fetch_URL($ECURL,false);
		$time_stop = ECF_fetch_microtime();
		$total_time += ($time_stop - $time_start);
		$time_fetch = sprintf("%01.3f",round($time_stop - $time_start,3));
		$RC = '';
		if (preg_match("|^HTTP\/\S+ (.*)\r\n|",$rawhtml,$matches)) {
			$RC = trim($matches[1]);
		}
		$Status .= "<!-- second time to fetch: $time_fetch sec ($RC) -->\n";
	}

  $stuff = explode("\r\n\r\n",$rawhtml); // maybe we have more than one header due to redirects.
  $content = (string)array_pop($stuff); // last one is the content
  $headers = (string)array_pop($stuff); // next-to-last-one is the headers

	if(preg_match('|200|',$RC)) { // good return so save off the cache	  
		$fp = fopen($cacheName, "w");
		if ($fp) {
			// $site = utf8_decode($site); // convert to ISO-8859-1 for use (like old EC site)
			$write = fputs($fp, $content);
			fclose($fp);  
			$Status .= "<!-- cache saved to $cacheName, ".strlen($content)." bytes. -->\n";
		} else {
			$Status .= "<!-- unable to open $cacheName for writing .. cache not saved -->\n";
		}
	} else {
		$Status .= "<!-- headers returned:\n$headers\n -->\n";
		$Status .= "<!-- using Cached version from $cacheName due to unsucessful fetch(s); age=$cacheAge seconds old -->\n";
	} // end of cache save
} 
	
// load the XML into an array
if(! file_exists($cacheName)) {
	print $Status;
	print "<!-- cache file $cacheName not found. Exiting. -->\n";
	return (false);
}

$doIconv = ($charsetInput == $charsetOutput)?false:true; // only do iconv() if sets are different
 
$Status .= "<!-- using charsetInput='$charsetInput' charsetOutput='$charsetOutput' doIconv='$doIconv' -->\n";

// Set up the built-in legends to use in both English and French	
$LegendsLang = array(
// English
'en' => array(
  'citycondition' => 'Condition',
  'obsdate' => 'Date',
  'cityobserved' => 'Observed at',	  
  'temperature' => 'Temperature',
  'pressure' => 'Pressure',
  'tendency' => 'Tendency',
  'humidity' => 'Humidity',
  'windchill' => 'Wind<br/>Chill',
  'windchillabbr' => 'Wind Chill',
  'humidex' => 'Humidex',
  'visibility' => 'Visibility',
  'dewpoint' => 'Dew point',
  'wind' => 'Wind',
	'gust' => 'gust',
	'calm' => 'calm',
  'aqhi' => 'Air Quality Health Index',
  'maxtemp' => 'Max',
  'mintemp' => 'Min',
  'maxmin' => 'Normals',
  'precip' => 'Total Precipitation',
  'precip' => 'Rainfall',
  'snow' => 'Snowfall',
  'sunrise' => 'Sunrise',
  'sunset' => 'Sunset',
  'moonrise' => 'Moonrise',
  'moonset' => 'Moonset',
	'obs' => 'Currently',
	'yday' => 'Yesterday', 
	'norms' => 'Normals',
	'issued'  => 'Issued',
	'extremeMax' => 'Highest temperature',
	'extremeMin' => 'Lowest temperature',
	'normalMax' => 'Average high',
	'normalMin' => 'Average low',
	'normalMean' => 'Average',
	'extremeRainfall' => 'Greatest rainfall',
	'extremeSnowfall' => 'Greatest snowfall',
	'extremePrecipitation' => 'Greatest precipitation',
	'extremeSnowOnGround' => 'Most snow on the ground',
	'almanacpop' => 'Monthly frequency of precipitation',
  'avgexhead'  => 'Averages and extremes',
	'na' => 'n/a',
	'forecast24' => '24 Hour Forecast',
  'datetime' => 'Date/Time',
  'temperature' => 'Temp.',
  'weatherconds' => 'Weather Conditions',
  'lop' => 'LOP &dagger;',
  'lopnote' => '&dagger; Likelihood of Precipitation (LOP) as described in the public forecast '.
	  'as a chance of measurable precipitation for a period of time.<br/>' .
    '&nbsp;&nbsp;Nil: 0%<br/>' .
    '&nbsp;&nbsp;Low: 40% or below<br/>' . 
    '&nbsp;&nbsp;Medium: 60% or 70%<br/>' .
    '&nbsp;&nbsp;High: Above 70%<br/>',
  'nonsig' => '&Dagger; Value not significant ',
  'impact' => 'Impact Level: ',
  'confidence' => 'Forecast Confidence: ',
  'effectivefor' => 'In effect for:'
  ),
'fr'=> array(
  // French
  'citycondition' => 'Condition',
  'obsdate' => 'Date',
  'cityobserved' => 'EnregistrÈes ‡',
  'temperature' => 'TempÈrature',
  'pressure' => 'Pression',
  'tendency' => 'Tendance',
  'humidity' => 'HumiditÈ',
  'windchill' => 'Refr.<br/>Èolien',
  'windchillabbr' => 'refroidissement Èolien',
  'humidex' => 'Humidex',
  'visibility' => 'VisibilitÈ',
  'dewpoint' => 'Point de rosÈe',
  'wind' => 'Vent',
	'calm' => 'calme',
	'gust' => 'rafale',
  'aqhi' => 'Cote air santÈ',
  'maxmin' => 'Normales',
  'maxtemp' => 'Max',
  'mintemp' => 'Min',
  'precip' => 'PrÈcipitation totale',
  'precip' => 'Pluie',
  'snow' => 'Neige',
  'sunrise' => 'Lever',
  'sunset' => 'Coucher',
  'moonrise' => 'Lever de la lune',
  'moonset' => 'Coucher de la lune',
  'obs' => 'Conditions actuelles',
  'yday' => 'DonnÈes d\'hier',
  'norms' => 'Normales',
	'issued'  => '…mises ‡',
	'extremeMax' => 'TempÈrature la plus ÈlevÈe',
	'extremeMin' => 'TempÈrature la plus basse',
	'normalMax' => 'TempÈrature maximale moyenne',
	'normalMin' => 'TempÈrature minimale moyenne',
	'normalMean' => 'TempÈrature moyenne',
	'extremeRainfall' => 'Pluie maximale',
	'extremeSnowfall' => 'Neige maximale',
	'extremePrecipitation' => 'PrÈcipitation maximale',
	'extremeSnowOnGround' => 'Maximum de neige au sol',
	'almanacpop' => 'FrÈquence mensuelle de prÈcipitation',
  'avgexhead'  => 'Moyennes et extrÍmes',
	'na' => 'n.d.',
	'forecast24' => 'PrÈvisions 24 heures',
  'datetime' => 'Date/Heure',
  'temperature' => 'Temp',
  'weatherconds' => 'Condition mÈtÈo',
  'lop' => 'EdP &dagger;',
  'wind' => 'Vents',
  'lopnote' => '&dagger; …ventualitÈ de prÈcipitation (EdP) mesurable, indiquÈ dans la prÈvision '.
	  'publique comme probabilitÈ de prÈcipitation pour une pÈriode de temps.<br/>' .
    '&nbsp;&nbsp;Nulle: 0%<br/>' .
    '&nbsp;&nbsp;Basse: 40% et moins<br/>' .
    '&nbsp;&nbsp;Moyenne: 60% ou 70%<br/>' .
    '&nbsp;&nbsp;…levÈe : 80% et plus<br/>',
  'nonsig' => '&Dagger; Valeur non significative',
  'impact' => 'Niveau d\'impact: ',
  'confidence' => 'Confiance dans les prÈvisions: ',
  'effectivefor' => 'En vigueur pour:',

  )
);

$Legends = $LegendsLang[$Lang];  // use the legends based on language choice

if($doIconv) { // put legends in UTF-8 for later conversion 
  $TLegends = array();
  foreach ($Legends as $key => $val) {
	  $nval = iconv('ISO-8859-1','UTF-8//TRANSLIT',$val);
	  $TLegends[$key] = $nval;
  }
  $Legends = $TLegends;
  $Status .= "<!-- converted lookup legends to UTF-8 -->\n";
}

$MonthNamesLang = array( // easier to use this than switching locales...
 'en' => array(
		'01' => 'January',
		'02' => 'February',
		'03' => 'March',
		'04' => 'April',
		'05' => 'May',
		'06' => 'June',
		'07' => 'July',
		'08' => 'August',
		'09' => 'September',
		'10' => 'October',
		'11' => 'November',
		'12' => 'December'
  ),
 'fr' => array(
		'01' => 'janvier',
		'02' => 'fÈvrier',
		'03' => 'mars',
		'04' => 'avril',
		'05' => 'mai',
		'06' => 'juin',
		'07' => 'juillet',
		'08' => 'ao˚t',
		'09' => 'septembre',
		'10' => 'octobre',
		'11' => 'novembre',
		'12' => 'dÈcembre'
  )
);

$MonthNames = $MonthNamesLang[$Lang]; // month names (for 24hr forecast) based on language

if($doIconv) { // put months in UTF-8 
  $TMonths = array();
  foreach ($MonthNames as $key => $val) {
	  $nval = iconv('ISO-8859-1','UTF-8//TRANSLIT',$val);
	  $TMonths[$key] = $nval;
  }
  $MonthNames = $TMonths;
  $Status .= "<!-- converted lookup months to UTF-8 -->\n";
}

$RAWJSON = json_decode($content,true);
$JSON = isset($RAWJSON[0]['lastUpdated'])?$RAWJSON[0]:array();

//----------- handle the city conditions -----------------------------------------
$X = $JSON['observation'];

/*
    "observation": {
      "observedAt": "Hamilton Munro Int'l Airport",
      "provinceCode": "ON",
      "climateId": "6153193",
      "tcid": "yhm",
      "timeStamp": "2024-10-23T18:00:00.000Z",
      "timeStampText": "2:00 PM EDT Wednesday 23 October 2024",
      "iconCode": "03",
      "condition": "Mostly Cloudy",
      "temperature": {
        "imperial": "68",
        "imperialUnrounded": "68.4",
        "metric": "20",
        "metricUnrounded": "20.2",
        "qaValue": 100
      },
      "dewpoint": {
        "imperial": "51",
        "imperialUnrounded": "50.7",
        "metric": "10",
        "metricUnrounded": "10.4",
        "qaValue": 100
      },
      "feelsLike": {
        "imperial": "72",
        "metric": "22",
        "qaValue": 100
      },
      "pressure": {
        "imperial": "29.9",
        "metric": "101.1",
        "changeImperial": "0.01",
        "changeMetric": "0.04",
        "qaValue": 100
      },
      "tendency": "rising",
      "visibility": {
        "imperial": "15",
        "metric": "24",
        "qaValue": 100
      },
      "visUnround": 24.10000000000000142108547152020037174224853515625,
      "humidity": "53",
      "humidityQaValue": 100,
      "windSpeed": {
        "imperial": "17",
        "metric": "27",
        "qaValue": 100
      },
      "windGust": {
        "imperial": "24",
        "metric": "38",
        "qaValue": 100
      },
      "windDirection": "WSW",
      "windDirectionQAValue": 100,
      "windBearing": "243.0"
    },


*/	
// NOTE: we'll store the current conditions in the $conditions array for later assembly

if(isset($X['observedAt'])) { // got an observation.. format it
	$conditions['cityobserved'] = $Legends['cityobserved'] . ': <strong>'.
	  (string)$X['observedAt'] . '</strong>';
	$obsdate = (string)$X['timeStampText'];
	if($doIconv) {
		$obsdate = iconv($charsetInput,$charsetOutput.'//TRANSLIT',ECF_UTF_CLEANUP($obsdate));
	}
	$conditions['obsdate'] = $Legends['obsdate'] .': <strong>'. 
	  $obsdate . '</strong>';
	if(isset($X['condition']) and strlen((string)$X['condition']) > 0) {
	  $conditions['citycondition'] = '<strong>'.
	    (string)$X['condition'] . '</strong>';
		$conditions['icon'] = (string)$X['iconCode'] . $iconTypeEC;
	}
	$conditions['pressure'] = $Legends['pressure'] . ': <strong>'.
	  (string)$X['pressure']['metric'] . ' kPa</strong>';
	$conditions['tendency'] = $Legends['tendency'] . ': <strong>'.
	  (string)$X['pressure']['changeMetric'] . ' kPa</strong>';
	$conditions['temperature'] = $Legends['temperature'] . ': <strong>'.
	  (string)$X['temperature']['metric'] . ' &deg;C</strong>';
	if(strlen((string)$X['dewpoint']['metric']) > 0) {
	  $conditions['dewpoint'] = $Legends['dewpoint'] . ': <strong>'.
	    (string)$X['dewpoint']['metric'] . ' &deg;C</strong>';
	}
	if(isset($X['humidity'])) {
	  $conditions['humidity'] = $Legends['humidity'] . ': <strong>'.
	    (string)$X['humidity'] . ' %</strong>';
	}
	$conditions['wind'] = $Legends['wind'] . ': <strong>';
	  if($X['windSpeed']['metric'] > 0) {
	    $conditions['wind'] .= (string)$X['windDirection'] . ' ' . $X['windSpeed']['metric']; 
		  if(isset($X['windGust']['metric']) and strlen((string)$X['windGust']['metric'])>0) {
			  $conditions['wind'] .= ' ' . $Legends['gust'] . ' ' . (string)$X['windGust']['metric'];
		  }
		  $conditions['wind'] .= ' km/h';
		} else {
			$conditions['wind'] .= $Legends['calm'];
		}
	$conditions['wind'] .= '</strong>';
	if(isset($X['humidex'])) {
	  $conditions['humidex'] = $Legends['humidex'] . ': <strong>'.
	    (string)$X['humidex'] . '</strong>';
	}
	if($X['temperature']['metric'] <= 0 and isset($X['feelsLike']['metric'])) {
		$tl = str_replace('<br/>',' ',$Legends['windchill']);
	  $conditions['windchill'] = $tl . ': <strong>'.
	    $X['feelsLike']['metric'] . ' &deg;C</strong>';
	}
	if(isset($X['visibility']['metric'])) {
	  $conditions['visibility'] = $Legends['visibility'] . ': <strong>'.
	    $X['visibility']['metric'] . ' km</strong>';
	}
	
}

// extract 'normals'
/*
    "dailyFcst": {
      "dailyIssuedTimeShrt": "5:00 AM PDT",
      "regionalNormals": {
        "metric": {
          "highTemp": 13,
          "lowTemp": 6,
          "text": "Low 6. High 13."
        },
        "imperial": {
          "highTemp": 55,
          "lowTemp": 43,
          "text": "Low 6. High 13."
        }
      },
*/
if(isset($JSON['dailyFcst']['dailyIssuedTimeShrt'])) {
$X = $JSON['dailyFcst'];

  if(isset($X['regionalNormals']['metric']['text'])) {
    $conditions['maxmin'] = $Legends['maxmin'] . 
      ': Max <strong>' . $X['regionalNormals']['metric']['highTemp'] . 
      '&deg;C</strong> Min <strong>' . $X['regionalNormals']['metric']['lowTemp'] .
      '&deg;C</strong>';
  }

  /*  yesterday info not available
  // extract the yesterday values
  $X = $xml->yesterdayConditions;
  if(isset($X->temperature[1])) {
    $conditions['ydayheading'] = $Legends['yday'];
    $conditions['ydaymaxtemp'] = $Legends['maxtemp'] . ': <strong>' .
      (string)$X->temperature[0] . ' &deg;C</strong>';
    $conditions['ydaymintemp'] = $Legends['mintemp'] . ': <strong>' .
      (string)$X->temperature[1] . ' &deg;C</strong>';
    $conditions['ydayprecip'] = $Legends['precip'] . ': <strong>' .
      (string)$X->precip . ' mm</strong>';
  }
  */

  /*
      "riseSet": {
        "set": {
          "time12h": "6:13 pm",
          "epochTimeRounded": "1729386000",
          "time": "18:13"
        },
        "timeZone": "PDT",
        "rise": {
          "time12h": "7:41 am",
          "epochTimeRounded": "1729346400",
          "time": "7:41"
        }
      },
  */
  // extract the sunrise/sunset data	
  $X = $JSON['riseSet'];
  if(isset($X['rise']['time'])) {
    $conditions['sunrise'] = $Legends['sunrise'] . ': <strong>' .
      $X['rise']['time'] . '</strong>';
    $conditions['sunset'] = $Legends['sunset'] . ': <strong>' .
      $X['set']['time'] . '</strong>';
  }
}
// Almanac info is not available in new JSON data

// change conditions back to ISO-8859-1 if needed

if($doIconv) {
  foreach ($conditions as $key => $val) {
    if($key == 'obsdate') {continue;} // it's already in iso-8859-1 strangely
	  $conditions[$key] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$val);
  }
}

$Status .= "<!-- conditions\n" . print_r($conditions,true) . " -->\n";

//---------------------------------------------------------------------------------------------
// Process the Hourly Forecast (if availablel)
/*
    "hourlyFcst": {
      "hourlyIssuedTimeShrt": "5:00 AM PDT",
      "hourly": [
        {
          "date": "19 October 2024",
          "periodID": 0,
          "windGust": {
            "metric": "",
            "imperial": ""
          },
          "windDir": "SE",
          "feelsLike": {
            "metric": "",
            "imperial": ""
          },
          "condition": "Rain at times heavy",
          "precip": "100",
          "temperature": {
            "metric": "14",
            "imperial": "57"
          },
          "iconCode": "13",
          "time": "7 AM",
          "windSpeed": {
            "metric": "30",
            "imperial": "19"
          },
          "epochTime": 1729346400,
          "dateShrt": "19 Oct"
        },

*/

if(isset($JSON['hourlyFcst']['hourly'][0])) {
	$UOMTempUsed = "&deg;C";
	$UOMWindUsed = 'km/h';
	$n = 0;
	$forecasthours['haveWindChill'] = false; // assume no Humidex found
	$forecasthours['haveHumidex'] = false;   // assume no WindChill found
	#$forecasthours['TZ'] = $TZabbr;
	$forecasthours['tempUOM'] = $UOMTempUsed;
	$forecasthours['windUOM'] = $UOMWindUsed;
	$lastDateShrt = '';
	foreach ($JSON['hourlyFcst']['hourly'] as $i => $X) {
    if(isset($X['date'])) {$lastDateShrt = (string)$X['date'];}
		$forecasthours[$n]['UTCstring'] = (string)$X['epochTime'];
    $forecasthours[$n]['date'] = $lastDateShrt;
    $forecasthours[$n]['time'] = (string)$X['time'];
		$forecasthours[$n]['cond'] = (string)$X['condition'];
		if($doIconv) {
			$forecasthours[$n]['cond'] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',
			    $forecasthours[$n]['cond']);
		}
		$forecasthours[$n]['icon'] = ECF_replace_icon((string)$X['iconCode'],(string)$X['precip']);
		$forecasthours[$n]['lop']  = (string)$X['precip'];  // likelyhood of precipitation
		
    $forecasthours[$n]['pop']  = (string)$X['precip']; // actual PoP if any
		$forecasthours[$n]['temp'] = (string)$X['temperature']['metric'];
		$forecasthours[$n]['wind'] = (string)$X['windDir']." ".(string)$X['windSpeed']['metric'];
		if(isset($X['windGust']['metric']) and strlen((string)$X['windGust']['metric'])>0) {
			$forecasthours[$n]['wind'] .= ' ' . $Legends['gust'] . ' ' . (string)$X['windGust']['metric'];
		}

		if(!empty($X['windChill'])) {
			$forecasthours[$n]['windchill'] = (string)$X['windChill'];
			$forecasthours['haveWindChill'] = true;
		}
		if(!empty($X['humidex'])) {
			$forecasthours[$n]['humidex'] = (string)$X['humidex'];
			$forecasthours['haveHumidex'] = true;
		}
		$n++;
	}
}
// end Hourly Forecast processing
if($doDebug) {
  $Status .= "<!-- hourlyForecast UOMtemp='$UOMTempUsed' UOMwind='$UOMWindUsed' -->\n";
  $Status .= "<!-- forecasthours\n".print_r($forecasthours,true)." -->\n";
}

//---------------------------------------------------------------------------------------------

// process the daily forecast periods
$i = 0;
$foundAbnormal = 0;  // No abnormal indicators in XML (so far)
$alertstring = '';

// get forecast issued date
if(isset($JSON['dailyFcst']['dailyIssuedTime'])) {
	$updated = $Legends['issued'].': '.(string)$JSON['dailyFcst']['dailyIssuedTime'];
	if($doIconv) {
		$updated = iconv($charsetInput,$charsetOutput.'//TRANSLIT',ECF_UTF_CLEANUP($updated));
	}
	$Status .= "<!-- forecast '$updated' -->\n";
}
// get the official location name
if(isset($JSON['displayName'])) {
	$title = (string)$JSON['displayName'] . ', ' . strtoupper($JSON['province']);
	if($doIconv) {
		$title = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$title);
	}
}

if(isset($JSON['dailyFcst']['daily'])) {
  foreach ($JSON['dailyFcst']['daily'] as $idx => $X) {
  /*
      "dailyFcst": {
        "dailyIssuedTimeShrt": "5:00 AM PDT",
        "regionalNormals": {
          "metric": {
            "highTemp": 13,
            "lowTemp": 6,
            "text": "Low 6. High 13."
          },
          "imperial": {
            "highTemp": 55,
            "lowTemp": 43,
            "text": "Low 6. High 13."
          }
        },
        "daily": [{
            "date": "Sat, 19 Oct",
            "summary": "Rain at times heavy",
            "periodID": 1,
            "periodLabel": "Today",
            "windChill": {
              "calculated": [],
              "textSummary": ""
            },
            "sun": {
              "value": "0",
              "units": "hours"
            },
            "temperatureText": "High 15.",
            "humidex": [],
            "precip": "",
            "frost": {
              "textSummary": ""
            },
            "titleText": "Today: Rain at times heavy. High 15.",
            "temperature": {
              "periodHigh": 15,
              "metric": "15",
              "imperial": "59"
            },
            "iconCode": "13",
            "text": "Rain at times heavy. Amount 30 to 40 mm. Wind southeast 30 km\/h except gusting to 60 near the water. High 15. UV index 1 or low."
          }, ...
  */	
    $forecasticon[$i] = (string)$X['iconCode'];
    $forecasttext[$i] = (string)$X['summary'];
    $forecastpop[$i]  = (string)$X['precip'];
    $forecasticon[$i] = ECF_replace_icon($forecasticon[$i],$forecastpop[$i]);

    $tSummary = (string)$X['summary'];
    $forecasttemp[$i] = (string)$X['temperature']['metric'];
    if(isset($X['temperature']['periodHigh'])) {
      $forecasttemp[$i] ='Max: '.$forecasttemp[$i];
      } else {
      $forecasttemp[$i] ='Min: '.$forecasttemp[$i];}
    $tAbn = '';
    $forecasttempabn[$i] = '';
    if(preg_match('!( rising | falling | hausse | baisse )!i',$tSummary)) {
      $tAbn = ' <strong>*</strong>';
      $foundAbnormal++;
    }
    $forecasttemptype[$i] = strtolower(substr((string)$X['temperatureText'],0,3));
    $forecasttemptype[$i] = str_replace('low','min',$forecasttemptype[$i]);
    $forecasttemptype[$i] = str_replace('hig','max',$forecasttemptype[$i]);
    $forecasttempabn[$i]  = $tAbn;
      $t = '<span style="color:'; 
      $t .= ($forecasttemptype[$i] == 'min')?'#00f':'#f00';
      $t .= '"><b>'.$forecasttemp[$i].'&deg;C</b></span>';
      $t .= $forecasttempabn[$i]."<br/>";
    $forecasttemp[$i] = $t;

    $forecasttitles[$i] = (string)$X['periodLabel'];
    $forecastdetail[$i] = (string)$X['text'];
    $forecastdays[$i]   = (string)$X['periodLabel'];

    //print "i=$i\n".print_r($X,true)."\n--------\n";
    $i++;
  }

  // fix the charset if needed
  if($doIconv) {
    for ($i=0;$i<count($forecasttext);$i++) {
      $forecasttext[$i] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',
                                ECF_UTF_CLEANUP($forecasttext[$i])); 
      $forecasttitles[$i] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$forecasttitles[$i]); 
      $forecastdetail[$i] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',
                                  ECF_UTF_CLEANUP($forecastdetail[$i]));
      $forecastdays[$i]   = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$forecastdays[$i]); 
    }
    $Status .= "<!-- converted output to $charsetOutput -->\n";
  }
}

if($doDebug) {
	$Status .= "<!-- forecasttitles \n".print_r($forecasttitles,true)." -->\n";
	$Status .= "<!-- forecasticon \n".print_r($forecasticon,true)." -->\n";
	$Status .= "<!-- forecastpop \n".print_r($forecastpop,true)." -->\n";
	$Status .= "<!-- forecasttemptype \n".print_r($forecasttemptype,true)." -->\n";
	$Status .= "<!-- forecasttemp \n".print_r($forecasttemp,true)." -->\n";
	$Status .= "<!-- forecasttempabn \n".print_r($forecasttempabn,true)." -->\n";
	$Status .= "<!-- forecasttext \n".print_r($forecasttext,true)." -->\n";
	$Status .= "<!-- forecastdetail \n".print_r($forecastdetail,true)." -->\n";
}
// end daily forecast period processing

//---------------------------------------------------------------------------------------------
// generate the HTML from the extracted data
//---------------------------------------------------------------------------------------------
// make the Current conditions table from $conditions array

$nC = 3; // number of columns in the conditions table
	
if (isset($conditions['cityobserved']) ) { // only generate if we have the data
	if (isset($conditions['icon']) and ! $conditions['icon'] ) { $nC = 2; };
	
	
	$currentConditions = '<table class="ECforecast" cellpadding="3" cellspacing="3" style="border: 1px solid #909090;">' . "\n";
	
	$currentConditions .= '
  <tr><td colspan="' . $nC . '" align="center"><small>' . $conditions['cityobserved'] .
  '<br/>'. $conditions['obsdate'] . 
  '</small></td></tr>' . "\n<tr>\n";
  if (isset($conditions['icon'])) {
    $currentConditions .= '
    <td align="center" valign="middle">' . 
"    <img src=\"$imagedirEC" . $conditions['icon'] . "\"\n" .
               "     height=\"51\" width=\"60\" \n" . 
			   "     alt=\"" . strip_tags($conditions['citycondition']) . "\"\n" .
			   "     title=\"" . strip_tags($conditions['citycondition']) . "\" /> <br/>" . 
			   $conditions['citycondition'] . "\n";
	$currentConditions .= '    </td>
';
    } // end of icon
    $currentConditions .= "
    <td valign=\"middle\">\n";

	if (isset($conditions['temperature'])) {
	  $currentConditions .= 
	  $conditions['temperature'] . "<br/>\n";
	}
	if (isset($conditions['windchill'])) {
	  $currentConditions .=
	  $conditions['windchill'] . "<br/>\n";
	}
	if (isset($conditions['humidex'])) {
	  $currentConditions .=
	  $conditions['humidex'] . "<br/>\n";
	}
	if (isset($conditions['wind'])) {
	  $currentConditions .= 
	  $conditions['wind'] . "<br/>\n";
	}
	if (isset($conditions['humidity'])) {
	  $currentConditions .=
	  $conditions['humidity'] . "<br/>\n";
	}
	if (isset($conditions['dewpoint'])) {
	  $currentConditions .=
	  $conditions['dewpoint'] . "<br/>\n";
	}
	
	if (isset($conditions['precip'])) {
	  $currentConditions .=
	  $conditions['precip'] . "<br/>\n";
	}
	
	$currentConditions .= 
	$conditions['pressure'] . "<br/>\n";
	
	if (isset($conditions['tendency'])) {
	  $currentConditions .=
	  $conditions['tendency'] . "<br/>\n" ;
	}
	if (isset($conditions['aqhi'])) {
	  $currentConditions .=
	  $conditions['aqhi'] . "<br/>\n" ;
	}
	if (isset($conditions['visibility'])) {
	  $currentConditions .=
	  $conditions['visibility'] . "\n" ;
	}
	$currentConditions .= '	   </td>
';
	$currentConditions .= '    <td valign="middle">
';
	if(isset($conditions['sunrise']) and isset($conditions['sunset']) ) {
	  $currentConditions .= 
	  $conditions['sunrise'] . "<br/>\n" .
	  $conditions['sunset'] . "<br/>\n" ;
	}
	if (isset($conditions['moonrise']) and isset($conditions['moonset']) ) {
  	  $currentConditions .=
	  $conditions['moonrise'] . "<br/>\n" .
	  $conditions['moonset'] ;
	}
    if(isset($conditions['maxmin'])  ) {
		$currentConditions .= str_replace(':',':<br/>',$conditions['maxmin']) . "<br/>\n";
	}
    if(isset($conditions['ydayheading']) and 
	   isset($conditions['ydaymaxtemp']) and
	   isset($conditions['ydaymintemp']) ) {
		$currentConditions .= $conditions['ydayheading'] . "<br/>\n" .
		'&nbsp;&nbsp;' . $conditions['ydaymaxtemp'] . "<br/>\n" .
		'&nbsp;&nbsp;' . $conditions['ydaymintemp'] . "<br/>\n";
		if(isset($conditions['ydayprecip'])) {
			$currentConditions .= 
		'&nbsp;&nbsp;' . $conditions['ydayprecip'] . "<br/>\n";
		}
		if(isset($conditions['ydaysnow'])) {
			$currentConditions .= 
		'&nbsp;&nbsp;' . $conditions['ydaysnow'] . "<br/>\n";
		}
		$currentConditions .= "<br/>\n";
	}
	$currentConditions .= '
	</td>
  </tr>
';
	$currentConditions .= '
</table>
';
} // end of if isset($conditions['cityobserved'])
// end of current conditions mods

//---------------------------------------------------------------------------------------------
// Generate the $almanac HTML (note: data not present in JSON.. left this in case it appears in the future)

if(isset($conditions['extremeMax']) and strlen($conditions['extremeMax']) > 3 ) { // got any almanac? 
  $almanacList = array(
    'extremeMax', 
    'extremeMin', 
    'normalMax', 
    'normalMin', 
    'normalMean', 
    'extremeRainfall', 
    'extremeSnowfall', 
    'extremePrecipitation', 
    'extremeSnowOnGround', 
    'almanacpop', 
	
  );

	$almanac = '<table class="ECforecast" cellpadding="3" cellspacing="3" style="border: 1px solid #909090;">' . "\n";
	$l = $Legends['avgexhead'];
	if($doIconv) {
		$l = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$Legends['avgexhead']);
	}
	$almanac .= '<tr><th colspan="3">'.$l."</th></tr>\n";
	
	foreach ($almanacList as $i => $key) {
		if(isset($conditions[$key])) {
			list($value,$period,$year) = explode('|',$conditions[$key]);
			if(strlen($period)>0) {$period = '('.$period.')';}
			$value = str_replace('C','&deg;C',$value);
			$year = (strlen($year)>0)?$year:'&nbsp;';
			$l = $Legends[$key];
			if($doIconv) {
				$l = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$Legends[$key]);
			}
			$almanac .= '<tr><td>'.$l.$period.'</td>';
			$almanac .= '<td style="text-align:right"><strong>'.$value.'</strong></td>';
			$almanac .= '<td style="text-align:right"><strong>'.$year."</strong></td></tr>\n";
			
		}
	}
	$almanac .= "</table>\n";

} // end of $almanac HTML generation

//---------------------------------------------------------------------------------------------
// generate the HTML for the 24hr forecast table display

if(count($forecasthours) > 0) {
	$tCols = 5;
	if($forecasthours['haveHumidex'] or $forecasthours['haveWindChill']) { $tCols++; }
  $nonSigFound = false;
	$tDateLast = '';
	$forecast24h = '<div class="ECforecast">'."\n";
  $tTitle = $Legends['forecast24']." - $title";
  if($doIconv) {
    $tTitle = @iconv($charsetInput,$charsetOutput."//TRANSLIT",ECF_UTF_CLEANUP($tTitle));
  }
	$forecast24h .= '<h2>'.$tTitle."</h2>\n";
  $forecast24h .= '<table class="ECtable" style="border: 1px solid">'."\n";
  $forecast24h .= "<tr>\n";
	
	$forecast24h .= ' <td class="table-top" style="text-align: center">'.$Legends['datetime']."</td>\n";
	$forecast24h .= ' <td class="table-top" style="text-align: center">'.$Legends['temperature'].'<br/>('.$forecasthours['tempUOM'].")</td>\n";
  if($doIconv) {
    $Legends['weatherconds'] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$Legends['weatherconds']);
  }
	$forecast24h .= ' <td class="table-top" style="text-align: center"><br/>'.$Legends['weatherconds']."</td>\n";
	$forecast24h .= ' <td class="table-top" style="text-align: center"><br/>'.$Legends['lop']."</td>\n";
	$forecast24h .= ' <td class="table-top" style="text-align: center">'.$Legends['wind'].'<br/>('.$forecasthours['windUOM'].")</td>\n";
  if($forecasthours['haveHumidex']) {
	  $forecast24h .= ' <td class="table-top" style="text-align: center"><br/>'.$Legends['humidex']."</td>\n";
	}
  if($forecasthours['haveWindChill']) {
	  $forecast24h .= ' <td class="table-top" style="text-align: center">'.
		'<abbr title="'.$Legends['windchillabbr'].'">'.$Legends['windchill']."</abbr></td>\n";
	}
	
	$forecast24h .= "</tr>\n";
	$tDateLast = '';
	for($i=0;$i<24;$i++) {
		// generate the detail line for the hour
		$F = $forecasthours[$i];
/*
		[UTCstring] => 201709262000
		[time] => 16:00
		[date] => 2017-09-26
		[cond] => GÈnÈralement ensoleillÈ
		[icon] => 01
		[lop] => Nulle
		[pop] => 0
		[temp] => 29
		[wind] => S 10 km/h
		[humidex] => 36
*/
    $t = '';
    if($tDateLast !== $F['date']) {
			$t .= '<tr><td class="table-top" colspan="'.$tCols.'"  style="text-align: left">'.
			  $F['date'].
				'</td></tr>'."\n";
		  $tDateLast = $F['date'];
		}
		$t .= '<tr>'."\n";
		$t .= ' <td style="text-align: center">'.$F['time']."</td>\n";
		$t .= ' <td style="text-align: center">'.$F['temp']."</td>\n";
		$t .= ' <td style="text-align: left">';
		$alttext = $F['cond'];
	  if ($F['pop'] <> '') {
		  $alttext .= " (" . $F['pop'] ."%)";
	  }
	  $t .= '<span style="vertical-align: middle">'.
		   "<img src=\"$imagedirEC" . ECF_replace_icon($F['icon'],$F['pop']) . '"'.
			 ' height="25" width="30"' . 
			 ' alt="'.$alttext.'" title="'.$alttext.'" /></span>'. 
			 '&nbsp;&nbsp;<span style="vertical-align: top">'.$F['cond'].
			 "</span></td>\n";

		$t .= ' <td style="text-align: center">'.$F['lop']."</td>\n";
		$t .= ' <td style="text-align: center">';
		if(strpos($F['wind'],'VR') !== false) {
			$t .= '<abbr title="Direction variable">'.$F['wind']."</abbr></td>\n";
		} else {
			$t .= $F['wind']."</td>\n";
		}
		if($forecasthours['haveHumidex']) {
  		$t .= ' <td style="text-align: center">';
			if(isset($F['humidex'])) {
				$t .= $F['humidex'];
			} else {
				$t .= '&Dagger;';
				$nonSigFound = true;
			}
			$t .= "</td>\n";
		}
		if($forecasthours['haveWindChill']) {
  		$t .= ' <td style="text-align: center">';
			if(isset($F['windchill'])) {
				$t .= $F['windchill'];
			} else {
				$t .= '&Dagger;';
				$nonSigFound = true;
			}
			$t .= "</td>\n";
		}
		$t .= '</tr>'."\n";
		$forecast24h .= $t;
	
	}
	$forecast24h .= "<tr>\n";
	$forecast24h .= ' <td colspan="'.$tCols.'">&nbsp;</td>'."\n";
  $forecast24h .= "</tr>\n";
	$forecast24h .= "<tr>\n";
  if($doIconv) {
    $Legends['lopnote'] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$Legends['lopnote']);
  }
	$forecast24h .= ' <td colspan="'.$tCols.'">'.
	  $Legends['lopnote']."</td>\n";
  $forecast24h .= "</tr>\n";
	if($nonSigFound) {
		$forecast24h .= "<tr>\n";
 		$forecast24h .= ' <td colspan="'.$tCols.'"">'.
			$Legends['nonsig']."</td>\n";
		$forecast24h .= "</tr>\n";
	}
	$forecast24h .= "</table>\n</div><!-- end of 24hr forecast table -->\n";
	if($doIconv) {
		#$forecast24h = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$forecast24h);
	}
} // end generate HTML for 24hr forecast table display

//---------------------------------------------------------------------------------------------
// now format the forecast for display

if (count($forecasticon) > 0) {
  //-----------------------------------------------------------------------------	
  // calc the width percentage based on number of icons to display

  $wdth = intval(100/(count($forecasticon)/2));
  // set the legend
  $weather7days = ($Lang=='fr')?'PrÈvisions':'Extended Forecast';

  // now make the table
  $weather = '<div class="ECforecast">'."\n";
  $weather .= '<table class="ECtable">'."\n";
  $weather .= "<tr>\n";
  $weather .= '<td colspan="7" class="table-top">&nbsp;'.$weather7days." - $ECNAME</td>\n";
  $weather .= "</tr>\n";

  $weather .= "<tr>\n";

  $tweather = array();

  // generate the icon for each period  
  foreach ($forecasticon as $i => $v) {

    $tweather[$i] = '<!-- '.$i.' --><td style="width: '.$wdth.'%; text-align: center; vertical-align: top;"><b>'.
    $forecasttitles[$i]."</b>\n";
    $alttext = $forecasttext[$i];
    if ($forecastpop[$i] <> '') {
      $alttext .= " (" . $forecastpop[$i] ."%)";
    }
    $forecasticon[$i] = 
      "    <img src=\"$imagedirEC" . $forecasticon[$i] .
      "\"\n" .
      "     height=\"51\" width=\"60\" \n" . 
      "     alt=\"$alttext\"\n" .
      "     title=\"".$forecastdetail[$i]."\" /> <br/>\n";
    $tweather[$i] .= "<br/>" . $forecasticon[$i] . "\n";
    $tweather[$i] .= "    " . $forecasttext[$i] . "<br/>\n";
    $tweather[$i] .= "    " . $forecasttemp[$i] . "\n";
    $tweather[$i] .= "  </td>\n";

    $forecast[$i] = 
      $forecasttitles[$i] . "<br/>\n" .
      $forecasticon[$i] . "<br/>\n" .
      $forecasttext[$i] . "<br/>\n" .
      $forecasttemp[$i] . "\n";

  } // end generate forecast icons in $tweather array

  // now loop over the $tweather array to build the two table rows with icons

  if($forecasttemptype[0] == 'min') { $iStart = -1; } else { $iStart = 0; }

  for ($i=0;$i<=count($tweather);$i=$i+2) {
   if(isset($tweather[$i+$iStart])) {
     $weather .= $tweather[$i+$iStart];
   } else {
     $weather .= '<td style="width: '.$wdth.'%; text-align: center; vertical-align: top;">';
     $weather .= "&nbsp;\n</td>\n";
   }
  }

  $weather .= "</tr>\n<tr>\n";

  for ($i=1;$i<=count($tweather);$i=$i+2) {
   if(isset($tweather[$i+$iStart])) {
     $weather .= $tweather[$i+$iStart];
   } else {
     $weather .= '<td style="width: '.$wdth.'%; text-align: center; vertical-align: top;">';
     $weather .= "&nbsp;\n</td>\n";
   }
  }

  $weather .= "</tr>\n</table>\n";
  $weather .= "</div>\n\n";
}

/* note: finish styling of alert links in your .css by adding:
 
.ECred a:link,
.ECred a:visited,
.ECred a:hover,
.ECgrey a:link,
.ECgrey a:visited,
.ECgrey a:hover,

{
	color:white !important;
}

.ECyellow a:link,
.ECyellow a:visited,
.ECyellow a:hover,
.ECorange a:link,
.ECorange a:visited,
.ECorange a:hover,
 {
{
	color:black !important;
}

abbr[title]{cursor:help;}
*/

//---------------------------------------------------------------------------------------------
// process alerts JSON and generate HTML

if (isset($JSON['alert']['zoneId'])) { // create the $alertstring HTML if there are alert(s)
  
  /*
 
 */

  $alertstring = '';
  // group alerts by type and add to $alertstring
  $XA = isset($JSON['alert']['alerts'])?$JSON['alert']['alerts']:array();
  $n = 0;
  $ecAlertFontSize = '130%';
	foreach ($XA as $i => $A) { 
    $alertstring .= format_alert($A,$n,'weather');
    $n++;
	}
   
  $XA = isset($JSON['alert']['hwyAlerts'])?$JSON['alert']['hwyAlerts']:array();
	foreach ($XA as $i => $A) { 
    $alertstring .= format_alert($A,$n,'highway');
    $n++;
	}
  
  if(strlen($alertstring) > 0) {
    $alertstring .= "<script type=\"text/javascript\">
  
  function toggle_view(name) {
 		var element = document.getElementById(name);
    var indic = document.getElementById(name+'_I');
		if (! element ) { return; } 
    var current = element.style.display;
    if(current == 'none') {
      element.style.display = 'inline';
      if(indic) { indic.innerHTML = '&#9650;';}
    } else {
      element.style.display = 'none';
     if(indic) { indic.innerHTML = '&#9654;';}
    }
    return;
  }
  </script>
  ";
  }
  
} else { // no alerts to show
  $alertstring = '';
}


// ---- end of alerts mods---

//---------------------------------------------------------------------------------------------
// finish HTML assembly for the page (detail text forecast)
if(isset($forecastdays) and count($forecastdays) > 0){
  $textfcsthead = $ECHEAD;
  // assemble the text forecast as a definition list
  $textforecast = '<div class="ECforecast"><h2>'.$textfcsthead.'</h2>'."\n<h3>$title ($updated)</h3>\n<dl>\n";

  if($doDebug) {
    $Status .= "<!-- forecastdays\n".print_r($forecastdays,true)." -->\n";
    $Status .= "<!-- forecastdetail\n".print_r($forecastdetail,true)." -->\n";
  }

  for ($g=0;$g < count($forecastdays);$g++) {
     $textforecast .= "  <dt><b>" . $forecastdays[$g] ."</b></dt>\n";
     $textforecast .= "  <dd>" . $forecastdetail[$g] . "</dd>\n";
  }
  $textforecast .= "</dl>\n";
  $textforecast .= "</div>\n";
// ----- end of detail text forecast HTML assembly
} else {
  $textforecast = '';
}
if(!isset($weather)){$weather = '';}

//---------------------------------------------------------------------------------------------
// print it out:
if ($printIt and  ! $doInclude ) {
  header('Content-type: text/html,charset='.$charsetOutput);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charsetOutput; ?>" />
<title><?php print "$ECHEAD - $ECNAME"; ?></title>
<style type="text/css">
  body {
    font-family: Arial, Helvetica, sans-serif;
  }
/* styling for links in EC alert boxes  V7.00 */ 
.ECred a:link,
.ECred a:visited,
.ECred a:hover,
.ECgrey a:link,
.ECgrey a:visited,
.ECgrey a:hover,

{
	color:white !important;
}

.ECyellow a:link,
.ECyellow a:visited,
.ECyellow a:hover,
.ECorange a:link,
.ECorange a:visited,
.ECorange a:hover,
 {
{
	color:black !important;
}

abbr[title]{cursor:help;}
</style>
</head>
<body>
<?php 
}

if ($printIt) {
  $ECURL = preg_replace('|&|Ui','and',$ECURL); // make link XHTML compatible
  print $Status;
  print $alertstring;
  print $ddMenu;
  if ($showConditions) {
	  print "<table style=\"width: 99%; border: none;\"><tr><td align=\"center\">\n";
    print $currentConditions;
	  print "</td></tr></table>\n";
  }
  print $weather;
  if ($foundAbnormal > 0) {print $abnormalString; }
  print $textforecast;
	if($showAlmanac and strlen($almanac) > 0) {
	  print "<table style=\"width: 99%; border: none;\"><tr><td align=\"center\">\n";
    print $almanac;
	  print "</td></tr></table>\n";
	}
	if($show24hour and strlen($forecast24h) > 0) {
	  print "<table style=\"width: 99%; border: none;\"><tr><td align=\"center\">\n";
		print $forecast24h;
	  print "</td></tr></table>\n";
	}
  print "<p><a $LINKtarget href=\"$PAGEURL\">$ECNAME</a></p>\n";
}
if ($printIt  and ! $doInclude ) {?>
</body>
</html>
<?php
}
// --------- end of main program --------------

// ----------------------------functions ----------------------------------- 

//---------------------------------------------------------------------------------------------
// function to format the alert for printing

function format_alert($A,$n,$wType) {
  global $ecAlertFontSize, $doIconv, $charsetInput, $charsetOutput,$Legends;

  static $alertstyles = array(
     'red' => 'color: white; background-color: #DI0000; border: 2px solid black;',
    'orange'   => 'color: black; background-color: #ff9500; border: 2px solid black;',
	  'yellow' => 'color: black; background-color: #FFFF00; border: 2px solid black;',
    'grey'   => 'color: white; background-color: #656565; border: 2px solid black;',
    
  );
 $wIcon = ($A['program'] == 'highway')?'&#9951;':'&#9888;';  #9951, #128664
 $alertstring = '';
  $atype = $A['colour'];
  $ID = "alert_$n";
  $IDI = "alert_$n".'_I';
  if($A['status'] == 'ended') {return($alertstring);}
  $alertstring .= '<table class="ECforecast EC'.$atype.'" style="'.$alertstyles[$atype].' padding: 5px;width:100%;border:none;margin-top:5px;">'."\n";
  $alertstring .= "<tr><td style=\"text-align:center !important;font-size:$ecAlertFontSize;width:25px;cursor:help;\"><span onclick=\"toggle_view('$ID');\">$wIcon</span></td>";
  $tLoc = '';
  if($A['program'] == 'highway' and isset($A['refLocs']) and is_array($A['refLocs'])) {
      
     foreach ($A['refLocs'] as $key => $val) {
       $tLoc .= $A['refLocs'][$key]['name']."<br/>";
     }
    
  }
  $alertstring .= "<td style=\"text-align: center;font-size:$ecAlertFontSize;font-weight:bold;text-decoration-line: underline;\"><span onclick=\"toggle_view('$ID');\">";
  $aHead = $tLoc.$A['bannerText'];
  /*
  if($wType == 'highway') {
    $tZoneNames = '';
    foreach($A['zones'] as $k => $t) {
      $tZoneNames = $t."<br/>";
    }
    $aHead = $tZoneNames . $aHead;
  }
  */
  if($doIconv) {
    $aHead = iconv($charsetInput,$charsetOutput."//TRANSLIT",$aHead);
  }
  $alertstring .= "<span style=\"cursor:help;\">".$aHead."</span>";
  $alertstring .= "</span></td>\n";
  $alertstring .= "<td style=\"text-align:center !important;font-size:$ecAlertFontSize;font-weight:bold;width:25px;cursor:help;\"><span onclick=\"toggle_view('$ID');\" id=\"$IDI\">&#9654;</span></td></tr>\n</table>\n";


  # assemble details panel
  $tText = $A['issueTimeText']."\n\n";
  #$tText .= "<strong>".$A['alertHeaderText']."</strong>";
  /*
  $tText .= "<ul>";
  foreach ($A['zones'] as $k => $tZone) {
    $tText .= "<li>$tZone</li>";
  }
  $tText .= "</ul>";
  */
  if(isset($A['impact'])) {$tText .= $Legends['impact'].$A['impact']."\n\n";}
  if(isset($A['confidence'])) {$tText .= $Legends['confidence'].$A['confidence']."\n\n\n";}
  $tText .= $A['text']."\n";
  
  if(isset($A['refLocs']) and is_array($A['refLocs'])) {
     $tText .= "\n<strong>".$Legends['effectivefor']. "</strong><ul>";
    
     foreach ($A['refLocs'] as $key => $val) {
       $tText .= "<li>".$A['refLocs'][$key]['name']."</li>";
     }
    
     $tText .= "</ul>\n";
  }

  $tText = str_replace("\n","<br/>\n",$tText);
  if($doIconv) {
    $tText = iconv($charsetInput,$charsetOutput."//TRANSLIT",$tText);
  }
  $alertstring .= "<div class=\"ECforecast\" id=\"$ID\" style=\"display: none !important;width: 99% !important;\">";
  $alertstring .= "<div class=\"EC$atype ECforecast\" style=\"padding: 5px;border: black solid 1px;border-top: none;\">$tText</div>";
  $alertstring .= "</div>\n";
  $alertstring .= "<!-- finished for type='$atype' alerts -->\n";
  return ($alertstring); 
}

//---------------------------------------------------------------------------------------------

function ECF_replace_icon($icon,$pop) {
// now replace icon with spiffy updated icons with embedded PoP to
//    spruce up the dull ones from www.weatheroffice.ec.gc.ca 
  global $imagedirEC,$iconTypeEC;
  $curicon = str_replace('.gif','',$icon);
  $curicon = str_replace('.png','',$curicon);
  if ($pop > 0) {
	  $testicon = $curicon."p$pop$iconTypeEC";
	  if (file_exists("$imagedirEC/$testicon")) {
	    $newicon = $testicon;
	  } else {
	    $newicon = "$curicon$iconTypeEC";
	  }
  } else {
	  $newicon = "$curicon$iconTypeEC";
  }
#  $newicon = str_replace(".gif",$iconTypeEC,$newicon); // support other icon types
  return($newicon);
}

//---------------------------------------------------------------------------------------------
// get contents from one URL and return as string 
 function ECF_fetch_URL($url,$useFopen) {
  global $Status, $needCookie;
  $overall_start = time();
  if (! $useFopen) {
   // Set maximum number of seconds (can have floating-point) to wait for feed before displaying page without feed
   $numberOfSeconds=4;   

// Thanks to Curly from ricksturf.com for the cURL fetch functions

  $data = '';
  $domain = parse_url($url,PHP_URL_HOST);
  $theURL = str_replace('nocache','?'.$overall_start,$url);        // add cache-buster to URL if needed
  $Status .= "<!-- curl fetching '$theURL' -->\n";
  $ch = curl_init();                                           // initialize a cURL session
  curl_setopt($ch, CURLOPT_URL, $theURL);                         // connect to provided URL
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                 // don't verify peer certificate
  curl_setopt($ch, CURLOPT_USERAGENT, 
    'Mozilla/5.0 (ec-forecast.php - saratoga-weather.org)');
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $numberOfSeconds);  //  connection timeout
  curl_setopt($ch, CURLOPT_TIMEOUT, $numberOfSeconds);         //  data timeout
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);              // return the data transfer
  curl_setopt($ch, CURLOPT_NOBODY, false);                     // set nobody
  curl_setopt($ch, CURLOPT_HEADER, true);                      // include header information
  if (isset($needCookie[$domain])) {
    curl_setopt($ch, $needCookie[$domain]);                    // set the cookie for this request
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);             // and ignore prior cookies
    $Status .=  "<!-- cookie used '" . $needCookie[$domain] . "' for GET to $domain -->\n";
  }

  $data = curl_exec($ch);                                      // execute session

  if(curl_error($ch) <> '') {                                  // IF there is an error
   $Status .= "<!-- Error: ". curl_error($ch) ." -->\n";        //  display error notice
  }
  $cinfo = curl_getinfo($ch);                                  // get info on curl exec.
/*
curl info sample
Array
(
[url] => http://saratoga-weather.net/clientraw.txt
[content_type] => text/plain
[http_code] => 200
[header_size] => 266
[request_size] => 141
[filetime] => -1
[ssl_verify_result] => 0
[redirect_count] => 0
  [total_time] => 0.125
  [namelookup_time] => 0.016
  [connect_time] => 0.063
[pretransfer_time] => 0.063
[size_upload] => 0
[size_download] => 758
[speed_download] => 6064
[speed_upload] => 0
[download_content_length] => 758
[upload_content_length] => -1
  [starttransfer_time] => 0.125
[redirect_time] => 0
[redirect_url] =>
[primary_ip] => 74.208.149.102
[certinfo] => Array
(
)

[primary_port] => 80
[local_ip] => 192.168.1.104
[local_port] => 54156
)
*/
  $Status .= "<!-- HTTP stats: " .
    " RC=".$cinfo['http_code'];
	if(isset($cinfo['primary_ip'])) {
    $Status .= " dest=".$cinfo['primary_ip'] ;
	}
	if(isset($cinfo['primary_port'])) { 
	  $Status .= " port=".$cinfo['primary_port'] ;
	}
	if(isset($cinfo['local_ip'])) {
	  $Status .= " (from sce=" . $cinfo['local_ip'] . ")";
	}
	$Status .= 
	"\n      Times:" .
    " dns=".sprintf("%01.3f",round($cinfo['namelookup_time'],3)).
    " conn=".sprintf("%01.3f",round($cinfo['connect_time'],3)).
    " pxfer=".sprintf("%01.3f",round($cinfo['pretransfer_time'],3));
	if($cinfo['total_time'] - $cinfo['pretransfer_time'] > 0.0000) {
	  $Status .=
	  " get=". sprintf("%01.3f",round($cinfo['total_time'] - $cinfo['pretransfer_time'],3));
	}
    $Status .= " total=".sprintf("%01.3f",round($cinfo['total_time'],3)) .
    " secs -->\n";

  //$Status .= "<!-- curl info\n".print_r($cinfo,true)." -->\n";
  curl_close($ch);                                              // close the cURL session
  //$Status .= "<!-- raw data\n".$data."\n -->\n"; 
  $stuff = explode("\r\n\r\n",$data); // maybe we have more than one header due to redirects.
  $content = (string)array_pop($stuff); // last one is the content
  $headers = (string)array_pop($stuff); // next-to-last-one is the headers
  if($cinfo['http_code'] <> 200) {
    $Status .= "<!-- headers:\n".$headers."\n -->\n"; 
  }
  return $data;                                                 // return headers+contents

 } else {
//   print "<!-- using file_get_contents function -->\n";
   $STRopts = array(
	  'http'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (ec-forecast.php - saratoga-weather.org)\r\n" .
				"Accept: text/plain,text/html\r\n"
	  ),
	  'https'=>array(
	  'method'=>"GET",
	  'protocol_version' => 1.1,
	  'header'=>"Cache-Control: no-cache, must-revalidate\r\n" .
				"Cache-control: max-age=0\r\n" .
				"Connection: close\r\n" .
				"User-agent: Mozilla/5.0 (ec-forecast.php - saratoga-weather.org)\r\n" .
				"Accept: text/plain,text/html\r\n"
	  )
	);
	
   $STRcontext = stream_context_create($STRopts);

   $T_start = ECF_fetch_microtime();
   $xml = file_get_contents($url,false,$STRcontext);
   $T_close = ECF_fetch_microtime();
   $headerarray = get_headers($url,0);
   $theaders = join("\r\n",$headerarray);
   $xml = $theaders . "\r\n\r\n" . $xml;

   $ms_total = sprintf("%01.3f",round($T_close - $T_start,3)); 
   $Status .= "<!-- file_get_contents() stats: total=$ms_total secs -->\n";
   $Status .= "<-- get_headers returns\n".$theaders."\n -->\n";
//   print " file() stats: total=$ms_total secs.\n";
   $overall_end = time();
   $overall_elapsed =   $overall_end - $overall_start;
   $Status .= "<!-- fetch function elapsed= $overall_elapsed secs. -->\n"; 
//   print "fetch function elapsed= $overall_elapsed secs.\n"; 
   return($xml);
 }

}    // end ECF_fetch_URL

//---------------------------------------------------------------------------------------------

function ECF_fetch_microtime()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

//---------------------------------------------------------------------------------------------

function ECF_get_time ($utcstring,$utcoffset) {
	// convert the YYYYDDMMHHmmss to ISO UTC format for strtotime use
	global $Status, $doDebug;
	$tstr = substr($utcstring,0,4).'-' .
	        substr($utcstring,4,2).'-' .
					substr($utcstring,6,2).'T' . 
					substr($utcstring,8,2).':' .
					substr($utcstring,10,2).':' .
					substr($utcstring,12,2). '00 UTC';
					
	if($doDebug) {
		$Status .= "<!-- ECF_get_time utcstring='$utcstring' tstr='$tstr' utcoffset='$utcoffset' (hrs) -->\n";  }
	return(strtotime($tstr)+$utcoffset*3600);
	
}

//---------------------------------------------------------------------------------------------

function ECF_UTF_CLEANUP ($str) {
// Clean embedded ISO-8859-1 characters with UTF-8 replacements so iconv can work.
// EC is a bit lazy about mixing character sets in descriptions.
  global $trantab;
  return($str); # temp disable this function
  $cstr = str_replace(
		// ISO-8859-1 characters
		$trantab['ISO'],
		// UTF-8 characters represented as ISO-8859-1
		$trantab['UTF'],
		$str); 
	return($cstr);
}
// end of ec-forecast.php