<?php
/**
 * Cloud Watch Graph
 * @author Valerio Galano <v.galano@daredevel.com>
 * @version 0.1
 */

require 'aws/aws-autoloader.php';
require_once('jpgraph/src/jpgraph.php');
require_once('jpgraph/src/jpgraph_line.php');
require_once('jpgraph/src/jpgraph_date.php');

// IAM keys
$key = "";
$secret = "";
$region = 'eu-west-1';

// Amazon works in UTC
date_default_timezone_set('UTC');

$download = isset($_GET['download']) && strlen(trim(isset($_GET['download']))) > 0 ? trim($_GET['download']): false;
$instanceId = isset($_GET['instanceId']) && strlen(trim(isset($_GET['instanceId']))) > 0 ? $_GET['instanceId'] : null;
$metricName = isset($_GET['metricName']) && strlen(trim(isset($_GET['metricName']))) > 0 ? $_GET['metricName'] : null;
//$unitType = 'Percent';
$startTime = isset($_GET['startTime']) && strlen(trim(isset($_GET['startTime']))) > 0 ? $_GET['startTime'] : null;
$endTime = isset($_GET['endTime']) && strlen(trim(isset($_GET['endTime']))) > 0 ? $_GET['endTime'] : null;

if (null == $instanceId) {
    exit('Parametro "instanceId" non valido.');
} else {
    $instances = explode(',',$instanceId);
    $namespace = 'AWS/EC2';
    $paramName = 'InstanceId';
    if (substr($instances[0],0,3) == 'vol') {
        $namespace = 'AWS/EBS';
        $paramName = 'VolumeId';
    }
}
if (null == $metricName) {
    exit('Parametro "metricName" non valido.');
}
if (null == $startTime) {
    exit('Parametro "startTime" non valido.');
}
if (null == $endTime) {
    exit('Parametro "endTime" non valido.');
}

$statistics = array(
    'Average',
    'Sum',
    'Maximum',
    'Minimum'
);

$ec2 = buildEC2Client($key, $secret, $region);

$cw = buildCloudWatchClient($key, $secret, $region);

$graph = buildGraph($metricName);


foreach ($instances as $instanceId) {
    $dimensions = array('member' =>
        array('Name' => $paramName, 'Value' => $instanceId),
    );

    $d = fetchMetricsStatistic($cw, $namespace, $metricName, $startTime, $endTime, $statistics, $dimensions);

    if (count($d) < 2) {
        exit("Nessun dato da mostrare per istanza {$instanceId}.");
    }

    $instanceName = fetchInstanceName($ec2, $instanceId);

// Create the first line
    $p1 = new LinePlot(array_values($d), array_keys($d));
    $graph->Add($p1);
    //$p1->SetColor("#6495ED");
    $p1->SetWeight(3);
    $p1->SetLegend($instanceName);
}


if ($download) {
    $im = $graph->Stroke(_IMG_HANDLER);
    $filename = $instanceId.'-'.$metricName;
    $file_type = "image/png";
    $file_ending = "png";
    $filename = $filename . "." . $file_ending;

    header("Content-Type: application/$file_type");
    header("Content-Disposition:attachment; filename=" . $filename);
    header("Pragma: no-cache");
    header("Expires:0");
    ImagePNG($im);
} else {
    $graph->Stroke();
}

function buildEC2Client($key, $secret, $region)
{
    $credentials = new \Aws\Credentials\Credentials($key, $secret);

    $ec2 = new \Aws\Ec2\Ec2Client([
        'credentials' => $credentials,
        'region' => $region,
        'version' => 'latest'
    ]);

    return $ec2;
}

function fetchInstanceName($ec2c, $instanceId)
{
    $command = $ec2c->getCommand('DescribeTags', array(
        'Filters' => [
            [
                'Name' => 'resource-id',
                'Values' => [$instanceId], //, instance2, instance3, ...
        ],
    ]
    ));

    $result = $ec2c->execute($command);

    $result = $result->toArray();

    $result = $result['Tags'][0]['Value'];

    return $result;
/*
    echo '<pre>';
    var_dump($result);
    die();
//*/
}

function buildCloudWatchClient($key, $secret, $region)
{
    $credentials = new \Aws\Credentials\Credentials($key, $secret);

    $cw = new Aws\CloudWatch\CloudWatchClient([
        'credentials' => $credentials,
        'region' => $region,
        'version' => 'latest'
    ]);

    return $cw;
}

function fetchMetricsStatistic($cw, $namespace, $metricName, $startTime, $endTime, $statistics, $dimensions)
{
    $command = $cw->getCommand('GetMetricStatistics', array(
        'Namespace' => $namespace,
        'MetricName' => $metricName,
        'StartTime' => $startTime,
        'EndTime' => $endTime,
        'Period' => 60,
        'Statistics' => $statistics,
        'Dimensions' => $dimensions
    ));

    $result = $cw->execute($command);

/*
    echo '<pre>';
    var_dump($result);
    die();
//*/

    $d = array();
    $datapoints = $result->toArray();
    foreach ($datapoints['Datapoints'] as $point) {
        $time = strtotime($point['Timestamp']);
        $d[$time] = $point['Average'];
    }

    ksort($d);

    return $d;
}

function buildGraph($title) {
    $graph = new Graph(1000, 600);
    //$graph->SetScale("textlin");
    $graph->SetScale("datelin");

#    $theme_class = new UniversalTheme;
    #$theme_class = new VividTheme;
    $theme_class = new OceanTheme;
#    $theme_class = new GreenTheme;

    $graph->SetTheme($theme_class);
    $graph->img->SetAntiAliasing(false);
    $graph->title->Set($title);
    $graph->SetBox(false);
    $graph->SetMargin(100,20,20,20);

    $graph->img->SetAntiAliasing();

    $graph->yaxis->HideZeroLabel();
    $graph->yaxis->HideLine(false);
    $graph->yaxis->HideTicks(false, false);
    $graph->yaxis->SetTextLabelInterval(2);
    $graph->yaxis->SetFont(FF_FONT2,FS_NORMAL,10);

    $graph->xgrid->Show();
    $graph->xgrid->SetLineStyle("solid");
    $graph->xgrid->SetColor('#E3E3E3');
    $graph->xaxis->SetLabelAngle(90);
    $graph->xaxis->scale->SetDateFormat('Y-m-d H:i');
    $graph->xaxis->SetFont(FF_FONT2,FS_NORMAL,18);
    $graph->xaxis->SetTextLabelInterval(2);

    $graph->legend->SetFrameWeight(1);
    $graph->legend->SetLineWeight(4);
    $graph->legend->SetFont(FF_FONT2,FS_NORMAL,200);

    return $graph;
}


