ec-forecast-README.txt  -- Version 5.00 -- 27-Sep-2017

Version 5.00 is a major rewrite of the original script to use XML data to produce the
forecast information instead of page-scrape of the EC website.  This should further
insulate us from random EC website changes as the XML format has been stable for 
multiple years.

  Ken True
  webmaster@saratoga-weather.org

CONTENTS:
  ec-forecast.php  -- the complete forecast script
  ec-forecast-lookup.txt -- REQUIRED lookup table for EC page-id to XML filename
  ec-forecast-testpage.php - a test page to show you how to include variables
                             from the forecast into your own page(s)
  ec-forecast-README.txt -- this file
  ec-icons/*.png - a complete set of icons used by the program.  It includes the
                   41 icons used by EC and many icons with PoP included (like
                   09.png with 60% PoP -> 09p60.png
       Note: the entire ec-icons/ directory needs to be uploaded before using the
             script, and it should be placed just below the directory that the
             ec-forecast.php script runs in.

INSTALLATION:
  unzip the distribution file ec-forecast-Vn-nn.zip (using folder names option) into the
  root or other directory of your website and upload it.

  Customize the ec-forecast.php variables for your language, and especially set
  the $ECURL to the forecast for the area closest to your station.

  Go to https://weather.gc.ca/canada_e.html and select your language English or French

  Click on your province on the map.
  Choose a location and click on the link for the selected forecast city

  Copy the URL from the browser address bar, and paste it into $ECURL below.
  The URL may be from either the weather.gc.ca or meteo.gc.ca sites.
  Examples:
  English: https://weather.gc.ca/city/pages/on-107_metric_e.html or 
  French:  https://meteo.gc.ca/city/pages/on-107_metric_f.html   

  Then set your default language in the program (see settings), and
  you should be ready to test using both
    ec-forecast.php   (should show your forecast)
    ec-forecast-testpage.php (should show includes working).


DE-INSTALLATION
  Just delete the scripts listed in contents, and the entire ec-icons/ directory.


Support will be on an as-available basis .. please email me 
at webmaster@saratoga-weather.org .

Best regards,
Ken True
webmaster@saratoga-weather.org
https://saratoga-weather.org/
