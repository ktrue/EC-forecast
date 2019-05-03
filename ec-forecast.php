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
//
  $Version = "V5.02 - 20-Nov-2017";

// error_reporting(E_ALL); // uncomment for checking errata in code
//---------------------------------------------------------------------------------------------
// NOTE: as of V5.00, the separate file 'ec-forecast-lookup.txt' is REQUIRED to be in the
//       same directory as this script.  It provides the lookup for EC page-id to XML file id.
//---------------------------------------------------------------------------------------------
//
//* 
// Settings:
// --------- start of settings ----------
// you need to set the $ECURL to the printable forecast for your area
//
//  Go to https://weather.gc.ca/canada_e.html and select your language English or French
//
//  Click on your province on the map.
//  Choose a location and click on the link for the selected forecast city
//
//  Copy the URL from the browser address bar, and paste it into $ECURL below.
//  The URL may be from either the weather.gc.ca or meteo.gc.ca sites.
//  Examples:
//  English: https://weather.gc.ca/city/pages/on-107_metric_e.html or 
//  French:  https://meteo.gc.ca/city/pages/on-107_metric_f.html   
//
$ECURL = 'https://weather.gc.ca/city/pages/on-107_metric_e.html';
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
$imagedir = "ec-icons/";
//directory with your image icons WITH the trailing slash
//
$cacheName = 'ec-forecast.txt'; // note: will be changed to include XML source/language
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
$iconType = '.png';            // ='.gif' or ='.png' for ec-icons file type 

// The optional multi-city forecast .. make sure the first entry is for the $ECURL location
// The contents will be replaced by $SITE['ECforecasts'] if specified in your Settings.php
/*
$ECforecasts = array(
 // Location|forecast-URL  (separated by | characters)
'Hamilton, ON|https://weather.gc.ca/city/pages/on-77_metric_e.html',
'St. Catharines, ON|https://weather.gc.ca/city/pages/on-107_metric_e.html', // St. Catharines, ON
'Lincoln, ON|https://weather.gc.ca/city/pages/on-47_metric_e.html',
'Vancouver, BC|https://weather.gc.ca/city/pages/bc-74_metric_e.html',
'Calgary, AB|https://weather.gc.ca/city/pages/ab-52_metric_e.html',
'Regina, SK|https://weather.gc.ca/city/pages/sk-32_metric_e.html',
'Winnipeg, MB|https://weather.gc.ca/city/pages/mb-38_metric_e.html',
'Ottawa (Kanata - OrlÈans), ON|https://weather.gc.ca/city/pages/on-118_metric_e.html',
'MontrÈal, QC|https://weather.gc.ca/city/pages/qc-147_metric_e.html',
'Happy Valley-Goose Bay, NL|https://weather.gc.ca/city/pages/nl-23_metric_e.html',
'St. John\'s, NL|https://weather.gc.ca/city/pages/nl-24_metric_e.html',
'Fredericton, NB|https://weather.gc.ca/city/pages/nb-29_metric_e.html',
'Halifax, NS|https://weather.gc.ca/city/pages/ns-19_metric_e.html',
'Charlottetown, PE|https://weather.gc.ca/city/pages/pe-5_metric_e.html',
'Whitehorse, YT|https://weather.gc.ca/city/pages/yt-16_metric_e.html',
'Yellowknife, NT|https://weather.gc.ca/city/pages/nt-24_metric_e.html',
'Resolute, NU|https://weather.gc.ca/city/pages/nu-27_metric_e.html',
'Iqaluit, NU|https://weather.gc.ca/city/pages/nu-21_metric_e.html',
'Placentia, NL|https://weather.gc.ca/city/pages/nl-30_metric_e.html',
'Channel-Port aux Basques, NL|https://weather.gc.ca/city/pages/nl-17_metric_e.html',
'Badger, NL|https://weather.gc.ca/city/pages/nl-34_metric_e.html',
'St. Anthony, NL|https://weather.gc.ca/city/pages/nl-37_metric_e.html',
'Mont-Tremblant, QC|https://weather.gc.ca/city/pages/qc-167_metric_e.html',
'Upsala, ON|https://weather.gc.ca/city/pages/on-154_metric_e.html',
'Grande Prairie, AB|https://meteo.gc.ca/city/pages/ab-31_metric_f.html',
'Grand Forks, BC|https://weather.gc.ca/city/pages/bc-39_metric_e.html',
'Baccaro Point, NS|https://weather.gc.ca/city/pages/ns-37_metric_e.html',
'The Pas, MB|https://meteo.gc.ca/city/pages/mb-30_metric_f.html',
'Jasper, AB|https://meteo.gc.ca/city/pages/ab-70_metric_f.html',
'Elliot Lake, ON|https://weather.gc.ca/city/pages/on-170_metric_e.html',
'Deer Lake, NL|https://weather.gc.ca/city/pages/nl-39_metric_e.html',
'Toronto, ON|https://weather.gc.ca/city/pages/on-143_metric_e.html',
'Whistler, BC|https://weather.gc.ca/city/pages/bc-86_metric_e.html'
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
if (isset($SITE['fcsticonsdirEC'])) {$imagedir = $SITE['fcsticonsdirEC'];} 
if (isset($SITE['charset']))        {$charsetOutput = strtoupper($SITE['charset']); }
// following overrides are new with V2.16
if (isset($SITE['ECiconType']))     {$iconType = $SITE['ECiconType']; }         // new with V2.16
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
// $alertstring = styled box with hotlinks to advisories/warnings
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
   header("Content-type: text/plain;charset=ISO-8859-1");
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
$alerttype = array();
$alerts = array();
$alertlinks = array();
$alertlinkstext = array();
$alertstring = '';
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

// force refresh of cache
if (isset($_REQUEST['cache'])) { $refetchSeconds = 1; }

// refresh cached copy of page if needed
// fetch/cache code by Tom at carterlake.org
if($Lang == 'fr' and preg_match('|weather.gc.ca|i',$ECURL)) {
	$ECURL = str_replace('weather.gc.ca','meteo.gc.ca',$ECURL);
	$Status .= "<!-- using $ECURL for French forecast instead of weather.gc.ca -->\n";
}
if($Lang == 'en' and preg_match('|meteo.gc.ca|i',$ECURL)) {
	$ECURL = str_replace('meteo.gc.ca','weather.gc.ca',$ECURL);
	$Status .= "<!-- using $ECURL for English forecast instead of meteo.gc.ca -->\n";
}

// NEW with V5.00: lookup/convert EC page URL to XML filename URL that we load/cache
list($ECXMLURL,$ECpgcode,$EClang,$ECunits,$ECXMLbase) = ECF_XML_URL_info($ECURL);

if($ECXMLURL === false) {
	print $Status;
	print "<p>ec-forecast.php ERROR: '$FCSTlocation' has an invalid EC page URL '$ECURL'.<br/> The corresponding XML weather data file is not found for page ID='$ECpgcode'.</p>\n"; 
	return(false);
}
// unique cache per language used
$cacheName = preg_replace('|\.txt|is',"-$haveIndex-$ECpgcode-$ECXMLbase-$Lang.txt",$cacheName); 
$cacheName = $cacheFileDir.$cacheName;
$cacheAge = (file_exists($cacheName))?time()-filemtime($cacheName):9999999;

//---------------------------------------------------------------------------------------------

// load the XML from the EC or cache
$total_time = 0.0;
if (file_exists($cacheName) and $cacheAge < $refetchSeconds) {
		$Status .= "<!-- using Cached version from $cacheName age=$cacheAge seconds old -->\n";
    $content = file_get_contents($cacheName);
	} else {
		$Status .= "<!-- refreshing $cacheName age=$cacheAge seconds old -->\n";
		$Status .= "<!-- EC main URL='$ECURL'\n     EC   XMLURL='$ECXMLURL' -->\n";
		$time_start = ECF_fetch_microtime();
		$rawhtml = ECF_fetch_URL($ECXMLURL,false);
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
		$rawhtml = ECF_fetch_URL($ECXMLURL,false);
		$time_stop = ECF_fetch_microtime();
		$total_time += ($time_stop - $time_start);
		$time_fetch = sprintf("%01.3f",round($time_stop - $time_start,3));
		$RC = '';
		if (preg_match("|^HTTP\/\S+ (.*)\r\n|",$rawhtml,$matches)) {
			$RC = trim($matches[1]);
		}
		$Status .= "<!-- second time to fetch: $time_fetch sec ($RC) -->\n";
	}

	$i = strpos($rawhtml,"\r\n\r\n");
	$headers = substr($rawhtml,0,$i);
	$content = substr($rawhtml,$i+4);

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

// load the XML forecast into an array for processing	
$xml = simplexml_load_string($content);

//----------- handle the city conditions -----------------------------------------
$X = $xml->currentConditions;

/*
SimpleXMLElement Object
(
    [station] => A√©roport int. Munro de Hamilton
    [dateTime] => Array
        (
            [0] => SimpleXMLElement Object
                (
                    [@attributes] => Array
                        (
                            [name] => observation
                            [zone] => UTC
                            [UTCOffset] => 0
                        )

                    [year] => 2017
                    [month] => 09
                    [day] => 19
                    [hour] => 20
                    [minute] => 00
                    [timeStamp] => 20170919200000
                    [textSummary] => 19 septembre 2017 20h00 UTC
                )

            [1] => SimpleXMLElement Object
                (
                    [@attributes] => Array
                        (
                            [name] => observation
                            [zone] => HAE
                            [UTCOffset] => -4
                        )

                    [year] => 2017
                    [month] => 09
                    [day] => 19
                    [hour] => 16
                    [minute] => 00
                    [timeStamp] => 20170919160000
                    [textSummary] => 19 septembre 2017 16h00 HAE
                )

        )

    [condition] => Partiellement nuageux
    [iconCode] => 02
    [temperature] => 24.9
    [dewpoint] => 19.7
    [humidex] => 32
    [pressure] => 101.5
    [visibility] => 24.1
    [relativeHumidity] => 73
    [wind] => SimpleXMLElement Object
        (
            [speed] => 9
            [gust] => SimpleXMLElement Object
                (
                    [@attributes] => Array
                        (
                            [unitType] => metric
                            [units] => km/h
                        )

                )

            [direction] => E
            [bearing] => 83.0
        )

)*/	
// NOTE: we'll store the current conditions in the $conditions array for later assembly

if(isset($X->station)) { // got an observation.. format it
	$conditions['cityobserved'] = $Legends['cityobserved'] . ': <strong>'.
	  (string)$X->station . '</strong>';
	$obsdate = (string)$X->dateTime[1]->textSummary;
	if($doIconv) {
		$obsdate = iconv($charsetInput,$charsetOutput.'//TRANSLIT',ECF_UTF_CLEANUP($obsdate));
	}
	$conditions['obsdate'] = $Legends['obsdate'] .': <strong>'. 
	  $obsdate . '</strong>';
	if(isset($X->condition) and strlen((string)$X->condition) > 0) {
	  $conditions['citycondition'] = '<strong>'.
	    (string)$X->condition . '</strong>';
		$conditions['icon'] = (string)$X->iconCode . $iconType;
	}
	$conditions['pressure'] = $Legends['pressure'] . ': <strong>'.
	  (string)$X->pressure . ' kPa</strong>';
	$conditions['tendency'] = $Legends['tendency'] . ': <strong>'.
	  (string)$X->pressure['tendency'] . '</strong>';
	$conditions['temperature'] = $Legends['temperature'] . ': <strong>'.
	  (string)$X->temperature . ' &deg;C</strong>';
	if(strlen((string)$X->dewpoint) > 0) {
	  $conditions['dewpoint'] = $Legends['dewpoint'] . ': <strong>'.
	    (string)$X->dewpoint . ' &deg;C</strong>';
	}
	if(strlen((string)$X->relativeHumidity) > 0) {
	  $conditions['humidity'] = $Legends['humidity'] . ': <strong>'.
	    (string)$X->relativeHumidity . ' %</strong>';
	}
	$conditions['wind'] = $Legends['wind'] . ': <strong>';
	  if($X->wind->speed > 0) {
	    $conditions['wind'] .= (string)$X->wind->direction . ' ' . (string)$X->wind->speed; 
		  if(isset($X->wind->gust) and strlen((string)$X->wind->gust)>0) {
			  $conditions['wind'] .= ' ' . $Legends['gust'] . ' ' . (string)$X->wind->gust;
		  }
		  $conditions['wind'] .= ' km/h';
		} else {
			$conditions['wind'] .= $Legends['calm'];
		}
	$conditions['wind'] .= '</strong>';
	if(strlen((string)$X->humidex) > 0) {
	  $conditions['humidex'] = $Legends['humidex'] . ': <strong>'.
	    (string)$X->humidex . '</strong>';
	}
	if(strlen((string)$X->windChill) > 0) {
		$tl = str_replace('<br/>',' ',$Legends['windchill']);
	  $conditions['windchill'] = $tl . ': <strong>'.
	    (string)$X->windChill . '</strong>';
	}
	if(strlen((string)$X->visibility) > 0) {
	  $conditions['visibility'] = $Legends['visibility'] . ': <strong>'.
	    (string)$X->visibility . ' km</strong>';
	}
	
}

// extract the updated time and 'normals'
$X = $xml->forecastGroup;

if(isset($X->regionalNormals->temperature[1])) {
	$conditions['maxmin'] = $Legends['maxmin'] . 
	  ': Max <strong>' . (string)$X->regionalNormals->temperature[0] . 
		'&deg;C</strong> Min <strong>' . (string)$X->regionalNormals->temperature[1] .
		'&deg;C</strong>';
}
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
// extract the sunrise/sunset data	
$X = $xml->riseSet;
if(isset($X->dateTime[1]->hour)) {
	$conditions['sunrise'] = $Legends['sunrise'] . ': <strong>' .
	  (string)$X->dateTime[1]->hour . ':' . (string)$X->dateTime[1]->minute . '</strong>';
	$conditions['sunset'] = $Legends['sunset'] . ': <strong>' .
	  (string)$X->dateTime[3]->hour . ':' . (string)$X->dateTime[3]->minute . '</strong>';
}
/*   <almanac>
 <temperature class="extremeMax" period="1960-2011" unitType="metric" units="C" year="1965">28.9</temperature>
 <temperature class="extremeMin" period="1960-2011" unitType="metric" units="C" year="1999">0.6</temperature>
 <temperature class="normalMax" unitType="metric" units="C">19.5</temperature>
 <temperature class="normalMin" unitType="metric" units="C">9.0</temperature>
 <temperature class="normalMean" unitType="metric" units="C">14.3</temperature>
 <precipitation class="extremeRainfall" period="1960-2011" unitType="metric" units="mm" year="1989">44.5</precipitation>
 <precipitation class="extremeSnowfall" period="1960-2011" unitType="metric" units="cm" year="1960">0.0</precipitation>
 <precipitation class="extremePrecipitation" period="1960-2011" unitType="metric" units="mm" year="1989">44.5</precipitation>
 <precipitation class="extremeSnowOnGround" period="1970-2010" unitType="metric" units="cm" year="1970">0.0</precipitation>
 <pop units="%">37.0</pop>
 </almanac>
*/

//---------------------------------------------------------------------------------------------
// process the almanac data

if($doDebug) {$Status .= "<!-- almanac\n".print_r($xml->almanac,true)." -->\n";}

if(isset($xml->almanac->temperature[0])) {
	
  foreach ($xml->almanac->temperature as $i => $X) {
	  $item   = (string)$X['class'];
		$unit   = (string)$X['units'];
		$period = empty($X['period'])?'':(string)$X['period'];
		$year   = empty($X['year'])?'':(string)$X['year'];
		$value  = (string)$X;
		if(empty($value)) {
			$conditions[$item] = $Legends['na'].'||';
		} else {
		  $conditions[$item] = "$value $unit|$period|$year";
		}
	  if($doDebug) {
			$Status .= "<!-- temperature item=$item unit=$unit period=$period year=$year value=$value -->\n";
		}
  }
}
if(isset($xml->almanac->precipitation[0])) {
	
  foreach ($xml->almanac->precipitation as $i => $X) {
	  $item   = (string)$X['class'];
		$unit   = (string)$X['units'];
		$period = empty($X['period'])?'':(string)$X['period'];
		$year   = empty($X['year'])?'':(string)$X['year'];
		$value  = (string)$X; 
		if(empty($value)) {
			$conditions[$item] = $Legends['na'].'||';
		} else {
		  $conditions[$item] = "$value $unit|$period|$year";
		}
	  if($doDebug) {
			$Status .= "<!-- precipitation item=$item unit=$unit period=$period year=$year value=$value -->\n";
		}
  }
}

if(isset($xml->almanac->pop)) {
	  $conditions['almanacpop'] = (string)$xml->almanac->pop;
		if(empty($conditions['almanacpop'])) {
			$conditions['almanacpop'] = $Legends['na'].'||';
		} else {
		  $conditions['almanacpop'] .= ' %||';
		}

}

// change conditions back to ISO-8859-1 if needed

if($doIconv) {
  foreach ($conditions as $key => $val) {
	  $conditions[$key] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$val);
  }
}

$Status .= "<!-- conditions\n" . print_r($conditions,true) . " -->\n";
/*  Old example (in English)
(
    [cityobserved] => Observed at: <strong>MontrÈal-Trudeau Int'l Airport</strong>
    [obsdate] => Date: <strong>1:00 PM EDT Thursday 27 October 2016</strong>
    [citycondition] => <strong>Mostly Cloudy</strong>
    [pressure] => Pressure: <strong>102.9 kPa</strong>
    [tendency] => Tendency: <strong>Falling</strong>
    [temperature] => Temperature: <strong>4.8&deg;C</strong>
    [dewpoint] => Dew point: <strong>-2.3&deg;C</strong>
    [humidity] => Humidity: <strong>60%</strong>
    [wind] => Wind: <strong>E 22 km/h</strong>
    [visibility] => Visibility: <strong>24 km</strong>
    [maxmin] => Normals: <strong>Max 9&deg;C. Min 1&deg;C.</strong>
    [sunrise] => Sunrise: <strong>7:27 EDT</strong>
    [sunset] => Sunset: <strong>17:49 EDT</strong>
    [icon] => 03.png
    [ydayheading] => Yesterday's Data
    [ydaymaxtemp] => Max: <strong>3.4&deg;C</strong>
    [ydaymintemp] => Min: <strong>-0.8&deg;C</strong>
    [ydayprecip] => Rainfall: <strong>Trace</strong>
    [ydaysnow] => Snowfall: <strong>0.1 cm</strong>
	
Note: moonrise/moonset, aqhi, tendency no longer available

Array (new from XML, French example)
(
    [cityobserved] => Observed at: <strong>Hamilton Munro Int'l Airport</strong>
    [obsdate] => Date: <strong>Friday September 22, 2017 at 17:00 EDT</strong>
    [citycondition] => <strong>Mainly Sunny</strong>
    [pressure] => Pressure: <strong>101.9 kPa</strong>
    [tendency] => Tendency: <strong>falling</strong>
    [temperature] => Temperature: <strong>25.4 &deg;C</strong>
    [dewpoint] => Dew point: <strong>16.0 &deg;C</strong>
    [humidity] => Humidity: <strong>55 %</strong>
    [wind] => Wind: <strong>ENE 11 km/h</strong>
    [humidex] => Humidex: <strong>30</strong>
    [visibility] => Visibility: <strong>19.3 km</strong>
    [icon] => 01.png
    [maxmin] => Normals: Max <strong>19&deg;C</strong> Min <strong>9&deg;C</strong>
    [ydayheading] => Yesterday
    [ydaymaxtemp] => Max: <strong>27.0 &deg;C</strong>
    [ydaymintemp] => Min: <strong>14.2 &deg;C</strong>
    [ydayprecip] => Rainfall: <strong>0.0 mm</strong>
    [sunrise] => Sunrise: <strong>07:07</strong>
    [sunset] => Sunset: <strong>19:18</strong>
    [extremeMax] => 28.9 C|1960-2011|1965
    [extremeMin] => 0.6 C|1960-2011|1999
    [normalMax] => 19.5 C||
    [normalMin] => 9.0 C||
    [normalMean] => 14.3 C||
    [extremeRainfall] => 44.5 mm|1960-2011|1989
    [extremeSnowfall] => 0.0 cm|1960-2011|1960
    [extremePrecipitation] => 44.5 mm|1960-2011|1989
    [extremeSnowOnGround] => 0.0 cm|1970-2010|1970
    [almanacpop] => 37.0||
)

*/

//---------------------------------------------------------------------------------------------
// Process the Hourly Forecast (if availablel)
/*
  <hourlyForecastGroup>
    <dateTime name="forecastIssue" zone="UTC" UTCOffset="0">
      <year>2017</year>
      <month name="September">09</month>
      <day name="Monday">25</day>
      <hour>15</hour>
      <minute>00</minute>
      <timeStamp>20170925150000</timeStamp>
      <textSummary>Monday September 25, 2017 at 15:00 UTC</textSummary>
    </dateTime>
    <dateTime name="forecastIssue" zone="EDT" UTCOffset="-4">
      <year>2017</year>
      <month name="September">09</month>
      <day name="Monday">25</day>
      <hour>11</hour>
      <minute>00</minute>
      <timeStamp>20170925110000</timeStamp>
      <textSummary>Monday September 25, 2017 at 11:00 EDT</textSummary>
    </dateTime>
    <hourlyForecast dateTimeUTC="201709251900">
      <condition>Mainly sunny</condition>
      <iconCode format="png">01</iconCode>
      <temperature unitType="metric" units="C">30</temperature>
      <lop category="Nil" units="%">0</lop>
      <windChill unitType="metric"/>
      <humidex unitType="metric">38</humidex>
      <wind>
        <speed unitType="metric" units="km/h">10</speed>
        <direction windDirFull="Southeast">SE</direction>
        <gust unitType="metric" units="km/h"/>
      </wind>
    </hourlyForecast>
*/

if(isset($xml->hourlyForecastGroup)) {
  $UTCOffset = (integer)$xml->hourlyForecastGroup->dateTime[1]['UTCOffset'];
	$TZabbr    = (string)$xml->hourlyForecastGroup->dateTime[1]['zone'];
	$UOMTempUsed = "&deg;".(string)$xml->hourlyForecastGroup->hourlyForecast[0]->temperature['units'];
	$UOMWindUsed = (string)$xml->hourlyForecastGroup->hourlyForecast[0]->wind->speed['units'];
	$n = 0;
	$forecasthours['haveWindChill'] = false; // assume no Humidex found
	$forecasthours['haveHumidex'] = false;   // assume no WindChill found
	$forecasthours['TZ'] = $TZabbr;
	$forecasthours['tempUOM'] = $UOMTempUsed;
	$forecasthours['windUOM'] = $UOMWindUsed;
	
	foreach ($xml->hourlyForecastGroup->hourlyForecast as $i => $X) {
		$forecasthours[$n]['UTCstring'] = (string)$X['dateTimeUTC'];
		$tDateTime = gmdate('Y-m-d H:i',
		  ECF_get_time( $forecasthours[$n]['UTCstring'], $UTCOffset));
		list($forecasthours[$n]['date'],$forecasthours[$n]['time']) = 
		   explode(' ',$tDateTime);
		list($forecasthours[$n]['year'],$tM,$forecasthours[$n]['day']) = 
		   explode('-',$forecasthours[$n]['date']);
		//$Status .= "<!-- tM='$tM' -->\n";
		$forecasthours[$n]['month'] = $tM;
		$forecasthours[$n]['monthname'] = $MonthNames[$tM];
		if($doIconv) {
			$forecasthours[$n]['monthname'] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',
			    $forecasthours[$n]['monthname']);
		}
		$forecasthours[$n]['day'] = preg_replace('|^0|','',$forecasthours[$n]['day']);
		$forecasthours[$n]['TZ'] = $TZabbr;
		$forecasthours[$n]['cond'] = (string)$X->condition;
		if($doIconv) {
			$forecasthours[$n]['cond'] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',
			    $forecasthours[$n]['cond']);
		}
		$forecasthours[$n]['icon'] = ECF_replace_icon((string)$X->iconCode,(string)$X->lop);
		$forecasthours[$n]['lop']  = (string)$X->lop['category'];  // likelyhood of precipitation
		if($doIconv) {
			$forecasthours[$n]['lop'] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',
			    $forecasthours[$n]['lop']);
		}
		$forecasthours[$n]['pop']  = (string)$X->lop; // actual PoP if any
		$forecasthours[$n]['temp'] = (string)$X->temperature;
		$forecasthours[$n]['wind'] = (string)$X->wind->direction." ".(string)$X->wind->speed;
		if(isset($X->wind->gust) and strlen((string)$X->wind->gust)>0) {
			$forecasthours[$n]['wind'] .= ' ' . $Legends['gust'] . ' ' . (string)$X->wind->gust;
		}

		if(!empty($X->windChill)) {
			$forecasthours[$n]['windchill'] = (string)$X->windChill;
			$forecasthours['haveWindChill'] = true;
		}
		if(!empty($X->humidex)) {
			$forecasthours[$n]['humidex'] = (string)$X->humidex;
			$forecasthours['haveHumidex'] = true;
		}
		$n++;
	}
}
// end Hourly Forecast processing
if($doDebug) {
  $Status .= "<!-- hourlyForecast UOMtemp='$UOMTempUsed' UOMwind='$UOMWindUsed' UTCOffset='$UTCOffset'" .
           " hrs TZabbr='$TZabbr' -->\n";
  $Status .= "<!-- forecasthours\n".print_r($forecasthours,true)." -->\n";
}

//---------------------------------------------------------------------------------------------
// process the forecast days


$i = 0;
$foundAbnormal = 0;  // No abnormal indicators in XML (so far)
$alertstring = '';

// get forecast issued date
if(isset($xml->forecastGroup->dateTime[1]->textSummary)) {
	$updated = $Legends['issued'].': '.(string)$xml->forecastGroup->dateTime[1]->textSummary;
	if($doIconv) {
		$updated = iconv($charsetInput,$charsetOutput.'//TRANSLIT',ECF_UTF_CLEANUP($updated));
	}
	$Status .= "<!-- forecast '$updated' -->\n";
}
// get the official location name
if(isset($xml->location->name)) {
	$title = (string)$xml->location->name . ', ' . strtoupper((string)$xml->location->province['code']);
	if($doIconv) {
		$title = iconv($charsetInput,$charsetOutput.'//TRANSLIT',$title);
	}
}

// process the forecast periods
foreach ($xml->forecastGroup->forecast as $idx => $X) {
/*
    [period] => ce soir et cette nuit
    [textSummary] => Partiellement nuageux avec 40 pour cent de probabilit√© d'averses. Risque d'un orage t√¥t ce soir. Nappes de brouillard se formant au cours de la nuit. Minimum 17.
    [cloudPrecip] => SimpleXMLElement Object
        (
            [textSummary] => Partiellement nuageux avec 40 pour cent de probabilit√© d'averses. Risque d'un orage t√¥t
ce soir.
        )

    [abbreviatedForecast] => SimpleXMLElement Object
        (
            [iconCode] => 39
            [pop] => 40
            [textSummary] => Possibilit√© d'averses
        )

    [temperatures] => SimpleXMLElement Object
        (
            [textSummary] => Minimum 17.
            [temperature] => 17
        )

    [winds] => SimpleXMLElement Object
        (
        )

    [precipitation] => SimpleXMLElement Object
        (
            [textSummary] => SimpleXMLElement Object
                (
                )

            [precipType] => pluie
        )

    [visibility] => SimpleXMLElement Object
        (
            [otherVisib] => SimpleXMLElement Object
                (
                    [@attributes] => Array
                        (
                            [cause] => other
                        )

                    [textSummary] => Nappes de brouillard se formant au cours de la nuit.
                )

        )

    [relativeHumidity] => 90
)
*/	
  $forecasticon[$i] = (string)$X->abbreviatedForecast->iconCode . '.gif';
	$forecasttext[$i] = (string)$X->abbreviatedForecast->textSummary;
	$forecastpop[$i]  = (string)$X->abbreviatedForecast->pop;
	$forecasticon[$i] = ECF_replace_icon($forecasticon[$i],$forecastpop[$i]);

	$tSummary = (string)$X->temperatures->textSummary;
	$forecasttemp[$i] = (string)$X->temperatures->temperature;
	$tAbn = '';
	$forecasttempabn[$i] = '';
	if(preg_match('!( rising | falling | hausse | baisse )!i',$tSummary)) {
		$tAbn = ' <strong>*</strong>';
		$foundAbnormal++;
	}
	$forecasttemptype[$i] = strtolower(substr((string)$X->temperatures->temperature['class'],0,3));
	$forecasttemptype[$i] = str_replace('low','min',$forecasttemptype[$i]);
	$forecasttemptype[$i] = str_replace('hig','max',$forecasttemptype[$i]);
	$forecasttempabn[$i]  = $tAbn;
		$t = ucfirst($forecasttemptype[$i]).': <span style="color:'; 
		$t .= ($forecasttemptype[$i] == 'min')?'#00f':'#f00';
		$t .= '"><b>'.$forecasttemp[$i].'&deg;C</b></span>';
		$t .= $forecasttempabn[$i]."<br/>";
	$forecasttemp[$i] = $t;

	$forecasttitles[$i] = (string)$X->period;
	$forecastdetail[$i] = (string)$X->textSummary;
	$forecastdays[$i]   = (string)$X->period['textForecastName'];
	
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
// end forecast period processing

//---------------------------------------------------------------------------------------------
// generate the alerts display
/*
  <warnings url="http://weather.gc.ca/warnings/report_f.html?ab10">
    <event type="warning" priority="high" description="AVERTISSEMENT DE PLUIE EN VIGUEUR">
      <dateTime name="eventIssue" zone="UTC" UTCOffset="0">
        <year>2017</year>
        <month name="septembre">09</month>
        <day name="mercredi">20</day>
        <hour>03</hour>
        <minute>28</minute>
        <timeStamp>20170920032800</timeStamp>
        <textSummary>20 septembre 2017 03h28 UTC</textSummary>
      </dateTime>
      <dateTime name="eventIssue" zone="HAR" UTCOffset="-6">
        <year>2017</year>
        <month name="septembre">09</month>
        <day name="mardi">19</day>
        <hour>21</hour>
        <minute>28</minute>
        <timeStamp>20170919212800</timeStamp>
        <textSummary>19 septembre 2017 21h28 HAR</textSummary>
      </dateTime>
    </event>
  </warnings>

*/

$X = $xml->warnings;
if($doDebug) {$Status .= "<!-- raw warnings\n".print_r($X,true)." -->\n";}

if (isset($X['url'])) { // got one (or more) alerts
	$aURL = str_replace('http://','https://',(string)$X['url']);
  // accumulate all the alert data from the <event> info
	for ($i=0;$i<count($X->event); $i++) {
    $alerttype[$i] = (string)$X->event[$i]['type'];
    $alerts[$i]    = (string)$X->event[$i]['description'];
		if($doIconv) {
			$alerts[$i] = iconv($charsetInput,$charsetOutput.'//TRANSLIT',
			                    ECF_UTF_CLEANUP($alerts[$i]));
		}
	}
} 

if (isset($alerts[0])) { // combine alerts of the same type
  foreach($alerttype as $i => $atype) {
    $alertlinks[$atype][] = $aURL;
    $alertlinkstext[$atype][] = $alerts[$i];
  }
}

if($doDebug) { 
   $Status .= "<!-- alertlinks\n" . print_r($alertlinks,true) . "-->\n";
   $Status .= "<!-- alertlinkstext\n" . print_r($alertlinkstext,true) . "-->\n";
}
// end of alerts processing

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
"    <img src=\"$imagedir" . $conditions['icon'] . "\"\n" .
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
// Generate the $almanac HTML

if(strlen($conditions['extremeMax']) > 3 ) { // got any almanac? 
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
	$forecast24h .= '<h2>'.$Legends['forecast24']." - $title</h2>\n";
  $forecast24h .= '<table class="ECtable" style="border: 1px solid">'."\n";
  $forecast24h .= "<tr>\n";
	
	$forecast24h .= ' <td class="table-top" style="text-align: center">'.$Legends['datetime'].'<br/>('.$forecasthours['TZ'].")</td>\n";
	$forecast24h .= ' <td class="table-top" style="text-align: center">'.$Legends['temperature'].'<br/>('.$forecasthours['tempUOM'].")</td>\n";
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
	
	for($i=0;$i<24;$i++) {
		// generate the detail line for the hour
		$F = $forecasthours[$i];
/*
		[UTCstring] => 201709262000
		[time] => 16:00
		[date] => 2017-09-26
		[day] => 26
		[year] => 2017
		[month] => 09
		[monthname] => septembre
		[TZ] => HAE
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
			  $F['day'].' '.$F['monthname'].' '.$F['year'].
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
		   "<img src=\"$imagedir" . ECF_replace_icon($F['icon'].'.gif',$F['pop']) . '"'. 
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
		$forecast24h = iconv($charsetInput,$charsetOutput.'//TRANSLIT',ECF_UTF_CLEANUP($forecast24h));
	}
} // end generate HTML for 24hr forecast table display

//---------------------------------------------------------------------------------------------
// now format the forecast for display
 	
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
	  "    <img src=\"$imagedir" . $forecasticon[$i] . 
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


/* note: finish styling of alert links in your .css by adding:
 
.ECwarning a:link,
.ECstatement a:link,
.ECended a:link,
.ECended a:visited
{
	color:white !important;
}
.ECwarning a:hover,
.ECended a:hover {
	color:black !important;
}

.ECwarning a:visited,
.ECstatement a:visited
{
	color:white !important;
}

.ECwatch a:link,
.ECwatch a:visited
{
	color:black !important;
}

.ECstatement a:hover,
.ECwatch a:hover {
	color:red !important;
}
*/

//---------------------------------------------------------------------------------------------
// finish processing alerts HTML

if (count($alertlinks) > 0) { // create the $alertstring HTML if there are alert(s)

  $alertstyles = array(
    'warning' => 'color: white; background-color: #b00; border: 2px solid black;',
    'watch'   => 'color: black; background-color: #ff0; border: 2px solid black;',
	'statement' => 'color: white; background-color: #707070; border: 2px solid black;',
    'ended'   => 'color: white; background-color: #6c6; border: 2px solid black;',
    'noalert' => 'color: black; background-color: #fff; border: 2px solid black;'
  );


  // group alerts by type and add to $alertstring

	foreach ($alertlinks as $atype => $alist) { 
		$alertstring .= '<p class="ECforecast EC'.$atype.'" 
		style="'.$alertstyles[$atype].' padding: 5px;text-align: center;">'."\n";
	
		foreach ($alertlinks[$atype] as $g => $alks ) {
			$alertstring .= '<b><a ' . $LINKtarget . 
					' href="' . $alertlinks[$atype][$g] . '">  ' . 
					$alertlinkstext[$atype][$g] . 
					'  </a></b><br/>'."\n";
		}
		$alertstring .= "</p><!-- finished for type='$atype' alerts -->\n";
	}
  
} else { // no alerts to show
  $alertstring = '';
}

// ---- end of alerts mods---

//---------------------------------------------------------------------------------------------
// finish HTML assembly for the page (detail text forecast)
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

//---------------------------------------------------------------------------------------------
// print it out:
if ($printIt and  ! $doInclude ) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charsetOutput; ?>" />
<title><?php print "$ECHEAD - $ECNAME"; ?></title>
<style type="text/css">
/* styling for EC alert boxes */ 
.ECwarning a:link,
.ECstatement a:link,
.ECended a:link,
.ECended a:visited
{
	color:white !important;
}
.ECwarning a:hover,
.ECended a:hover {
	color:black !important;
}

.ECwarning a:visited,
.ECstatement a:visited
{
	color:white !important;
}

.ECwatch a:link,
.ECwatch a:visited
{
	color:black !important;
}

.ECstatement a:hover,
.ECwatch a:hover {
	color:red !important;
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
  print "<p><a $LINKtarget href=\"$ECURL\">$ECNAME</a></p>\n";
}
if ($printIt  and ! $doInclude ) {?>
</body>
</html>
<?php
}
// --------- end of main program --------------

// ----------------------------functions ----------------------------------- 
    
function ECF_replace_icon($icon,$pop) {
// now replace icon with spiffy updated icons with embedded PoP to
//    spruce up the dull ones from www.weatheroffice.ec.gc.ca 
  global $imagedir,$iconType;
			  
  $curicon = $icon;
  if ($pop > 0) {
	$testicon = preg_replace("|.gif|","p$pop$iconType",$curicon);
	if (file_exists("$imagedir/$testicon")) {
	  $newicon = $testicon;
	} else {
	  $newicon = $curicon;
	}
  } else {
	$newicon = $curicon;
  }
  $newicon = preg_replace("|.gif|",$iconType,$newicon); // support other icon types
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
  $i = strpos($data,"\r\n\r\n");
  $headers = substr($data,0,$i);
  $content = substr($data,$i+4);
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

function ECF_XML_URL_info($ECurl) {
/*
  Function to change a http://weather.gc.ca/city/pages/... URL to the corresponding
	http://dd.weather.gc.ca/citypage_weather/xml/... URL for retrieval of the
	XML forecast desired.  Uses the $EClookup table for the conversion of page-id
	to XML base filename.
	
	Returns: array(
	  XML URL or false if requested page-id is not found
		pgcode (on-15, etc)
		lang   ( 'e' or 'f')
		units  ( 'metric' or 'imperial')
		XML file ('sNNNNNNN' or '' if not found)
	
	Author: Ken True - 18-Sep-2017 - saratoga-weather.org
*/
	global $EClookup;
	
	$urlparts = parse_url($ECurl);
	$pathinfo = pathinfo($urlparts['path']);
	list($pgcode,$unit,$lang) = explode('_',$pathinfo['filename']);
	list($prov,$numb) = explode('-',$pgcode);
	$PROV = strtoupper($prov);
	
	if(isset($EClookup[$pgcode])) {
		list($Xprov,$Xfile,$XnameE,$XnameF,$Xlat,$Xlon) = explode("|",$EClookup[$pgcode]);
		
		return( 
		  array(
		    "http://dd.weather.gc.ca/citypage_weather/xml/$PROV/${Xfile}_$lang.xml",
				$pgcode,
				$lang,
				$unit,
				$Xfile
			)
			);
	} else {
		return (array(
		  false,
			$pgcode,
			$lang,
			$unit,
			''
			)
		);
	}
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
  $cstr = str_replace(
		// ISO-8859-1 characters
		$trantab['ISO'],
		// UTF-8 characters represented as ISO-8859-1
		$trantab['UTF'],
		$str); 
	return($cstr);
}
// end of ec-forecast.php