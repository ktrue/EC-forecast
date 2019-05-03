<?php 
if ( strtolower($_REQUEST['sce']) == 'view' ) {
//--self downloader -- not required in your page
   $filenameReal = $_SERVER["PATH_TRANSLATED"];
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');
   
   readfile($filenameReal);

   exit;
}
// --------------- do this to include the forecast in your page --------
   $doInclude = true; // shut off the HTML printing with ec-forecast.php
   $doLang = 'fr';  // set to 'fr' for french, 'en' for english
   require("ec-forecast.php");
// ---------------------------------------------------------------------
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>EC-Forecast Test page</title>
<style type="text/css">
<!--
body {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 10px;
	color: #3366FF;
	background-color: #FFFFFF;
}
h1, h2, h3, h4, h5, h6 {
	font-family: Arial,sans-serif;
	margin: 0px;
	padding: 0px;
}

h1{
	font-family: Verdana,Arial,sans-serif;
	font-size: 120%;
	color: #334d55;
	font-weight: bold;
}

h2{
 font-size: 114%;
 color: #006699;
}
.codebox {
	border: 1px solid #080;
	color: #000000;
	padding: 5px;
	background-color: #FFFF99;
	margin: 5%;
	width: 85%;
}
.ECtable {
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-size: 10px;
    color: #0000FF;
    background-color: #CCFFFF;
}

.ECforecast {
    font-family: Tahoma, Arial, Helvetica, sans-serif;
    font-size: 10px;
    color: #996633;
    background-color: #66FFFF;
}

-->
</style>
</head>
<body>
<h1>Test page for ec-forecast.php</h1>
<p>This is just a test page to show how parts of the ec-forecast can be included</p>
<p>Include just two icons with text here</p> 
<!-- Note: the div id=codebox is only to highlight the sample
     it is NOT required in your code.. only the stuff enclosed
	 in the php tags is required at the top 
-->
<div class="codebox">
<table>
<tr>
  <td align="center" valign="top"><?php print $forecast[0]; ?></td>
  <td align="center" valign="top"><?php print $forecast[1]; ?></td>
</tr>
</table>
</div>
<p>Print just one day and detailed forecast here</p>
<div class="codebox">
<?php print "<b>" .$forecastdays[0] . "</b>: " . $forecastdetail[0]; ?></div>
<p>Print just the Icons below this</p>
<div class="codebox"><?php print $weather; ?></div>
<p>Print just the detailed text below this</p>
<div class="codebox"><?php print $textforecast; ?></div>
</body>
</html>
