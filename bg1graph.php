<?PHP
ini_set('mongo.native_long', 0);
include('phpgraphlib.php');

#phpinfo();
try{
include 'lib/db.php';

#only looking back the past three hours (insulin effectiveness)
$now = time();
$beginTime =  $now *1000 - (1 * 60 * 60 * 1000 ); # 1 hour ago
$query = array( 'date' => array('$gte' => $beginTime));
$fields = array('date' => false);
$cursor = $prod_collection->find($query, $fields)->sort(array('date' => 1)); #TODO sort by time

# sample mongo db document
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
$data = array();
$position = 0;
while ( $cursor->hasNext() )
{
	$document = $cursor->getNext();
	if($position <0){
		  var_dump($document);
	}
	$sgv = $document['sgv'];
	$dateString = $document['dateString'];
	$mm   = substr($dateString,  0, 2);
	$dd   = substr($dateString,  3, 2);
	$yyyy = substr($dateString,  6, 4);
	$HH   = substr($dateString, 11, 2);
	$MM   = substr($dateString, 14, 2);
	$SS   = substr($dateString, 17, 2);
	$amPM = substr($dateString, 20, 2);
	if("PM" == strtoupper($amPM) && "12" != $HH){
		#convert to military time
		$hours = intval($HH) + 12;
		$HH = "$hours"; #convert back to string
	}
	$graphKey = sprintf("1%03s.%02s%02s", $position, $HH, $MM); #format so graph will stay ordered
	$direction = $document['direction'];
	$data[$graphKey] = $sgv;
	$position++;
#print "$position:$dateString $yyyy/$mm/$dd $HH:$MM:$SS $amPM, $graphKey; <br>\n";
}

$graph = new PHPGraphLib(650,260);
$graph->addData($data);
$graph->setTitle('BG for the past hour');
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
