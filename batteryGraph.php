<?PHP
include('phpgraphlib.php');

try{
include 'lib/db.php';

$timezone  = -5; //(GMT -5:00) EST (U.S. & Canada) 

//doubt battery would last or not be charged in 24hrs
//now minus number of seconds in 24 hours.
$yesterday = new MongoDate(strtotime("-1 day"));
$query = array( 'created_at' => array('$gte' => $yesterday));
$cursor = $prodBattery_collection->find($query)->sort(array('created_at' => 1));
$data = array();
$previousHour = -1;
while ( $cursor->hasNext() )
{
	$position++;
	$document = $cursor->getNext();
	$dateSec = $document['created_at']->sec + ( 3600 * ($timezone+date("I")) );
	$dt = new DateTime("@$dateSec"); 
	$formattedDate = $dt->format('Y-m-d H:i:s'); 
	$hour = $dt->format('YmdH'); 
#	echo "$hour > $previousHour<br>";
	if($hour > $previousHour){
		$previousHour = $hour;
		$hh = substr($hour, -2);
		$data[$hour] = $batteryLevel;
	}
	$batteryLevel = $document['uploaderBattery'];
	#echo sprintf("Battery: %s, created_at: %s, %s <br>\n", $batteryLevel, $formattedDate, $dateSec);
}

$graph = new PHPGraphLib(650,200);
$graph->addData($data);
$graph->setTitle('Uploader battery level');
$graph->setBars(false);
$graph->setLine(true);
$graph->setDataPoints(true);
$graph->setDataPointColor('maroon');
$graph->setDataValues(true);
$graph->setDataValueColor('maroon');
$graph->setGoalLine(.0025);
$graph->setGoalLineColor('red');
$graph->createGraph();


} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}


?>
