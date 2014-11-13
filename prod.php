<html>
<head><title>BG snapshot</title>
<meta http-equiv="refresh" content="120">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.4/jquery.mobile-1.4.4.min.css">
<script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
<script src="http://code.jquery.com/mobile/1.4.4/jquery.mobile-1.4.4.min.js"></script>
<script>
function scrollTopBottom(){
	var objDiv = document.getElementById("fullPage");
	//objDiv.scrollTop = objDiv.scrollHeight;
	//var height = document.body.scrollHeight;
	//alert("scroll:"+ objDiv.scrollHeight +" : "+ height);
	//window.scrollTo(0,height);
	$(window).scrollTo(height);
}
</script>
<style>
	tr {
   	 border-bottom: 1px solid #d6d6d6;
	}
	.bgLow      { color: red; }
	.bgNormal   { color: black; }
	.bgHigh     { color: orange; }
	.bgVeryHigh { color: red; }

	.ui-table-columntoggle-btn {
   	 display: none !important;
	}
</style>
</head>
<body onLoad='javascript:scrollTopBottom();'>
<?PHP
ini_set('mongo.native_long', 0);

#phpinfo();
try{
$hoursToLookBack = ($_GET["hours"] != null) ? intval($_GET["hours"]) : 1;
if($hoursToLookBack > 24){
	$hoursToLookBack = 24;
}

include 'lib\db.php';

#only looking back the past three hours (insulin effectiveness)
$now = time();
$beginTime =  $now *1000 - ($hoursToLookBack * 60 * 60 * 1000 );
$query = array( 'date' => array('$gte' => $beginTime));
$fields = array('date' => false);

$yesterday = new MongoDate(strtotime("-1 day"));
$prodBattery_query = array( 'created_at' => array('$gte' => $yesterday));
$prodBattery_cursor = $prodBattery_collection->find($prodBattery_query)->limit(1)->sort(array('created_at' => -1));
$prodBatteryStatus = "";
if( $prodBattery_cursor->hasNext() )
{
	$document = $prodBattery_cursor->getNext();
	$dateSec = $document['created_at']->sec + ( 3600 * ($timezone+date("I")) );
	$dt = new DateTime("@$dateSec"); 
	$dt->setTimezone(new DateTimeZone('US/Eastern'));
	$formattedDate = $dt->format('h:i A'); 
	$batteryLevel = $document['uploaderBattery'];
	$prodBatteryStatus = sprintf("Battery: %s%%,  %s", $batteryLevel, $formattedDate);
}
$prod_cursor = $prod_collection->find($query, $fields)->sort(array('date' => 1)); 
showData('prod', $prod_cursor, "Main CGM", $prodBatteryStatus);

} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

# sample mongo beta_db document
#{
#    "_id": {
#        "$oid": "53d2ed85acdbe06c2caf23ec"
#    },
#    "device": "dexcom",
#    "date": 1406287556000,
#    "dateString": "07/25/2014 07:25:56 AM",
#    "sgv": "100",
#    "direction": "Flat"
#}
#

function showData($id, $cursor, $header, $footer){
	echo "<div data-role='page' id='fullPage'>\n";
	echo "  <div data-role='header'>\n";
	echo "    <h1>$header</h1>\n";
	echo "  </div>\n";
  
	echo "  <div data-role='${id}_main' class='ui-content'>\n";
	echo "    <table data-role='table' data-mode='columntoggle' class='ui-responsive'>\n";
	echo "      <thead>\n";
	echo "			<tr><th>Time</th><th>BG</th><th>Direction</th></tr>\n";
	echo "      </thead>\n";
	echo "      <tbody>\n";

//	var_dump($cursor);
	while ($cursor != NULL && $cursor->hasNext() )
	{
	$document = $cursor->getNext();
	$dateString = $document['dateString'];
	$mm   = substr($dateString,  0, 2);
	$dd   = substr($dateString,  3, 2);
	$yyyy = substr($dateString,  6, 4);
	$HH   = substr($dateString, 11, 2);
	$MM   = substr($dateString, 14, 2);
	$SS   = substr($dateString, 17, 2);
	$amPM = substr($dateString, 20, 2);
	if(intval($HH) > 12){
		$hours = intval($HH) - 12;
		$HH = "$hours"; #convert back to string
	}
	$hhMM = "$HH:$MM $amPM";
	$direction = $document['direction'];
	$sgv = $document['sgv'];
	$sgvCSS = '';
	if($sgv < 80){
	   $sgvCSS = 'bgLow';
	}else if($sgv > 80 && $sgv < 250){
	   $sgvCSS = 'bgNormal';
	}else if($sgv > 250 && $sgv < 300){
		$sgvCSS = 'bgHigh';
	}else if($sgv > 300){
		$sgvCSS = 'bgVeryHigh';
	}
	echo "<tr><td>$hhMM</td><td class='$sgvCSS'>$sgv</td><td>$direction</td></tr>\n";
	}
   echo "   </tbody>\n";
   echo " </table>\n";
  echo "</div>\n";

  echo "  <div data-role='footer'>\n";
  echo "    <h1>$footer</h1>\n";
  echo "  </div>\n";
  echo "</div> \n";
}

?>
</body>
</html>
