<?php

function js_array($array)
{
    $temp = array_map('js_str', $array);
    return '[' . implode(',', $temp) . ']';
}

function array_push_assoc($array, $key, $value){
    $array[$key] = $value;
    return $array;
}

$response = file_get_contents("https://api.covid19api.com/countries");
$get_countries = json_decode($response, TRUE);
$slugs = [];
$countries = [];
foreach($get_countries as $country) {
    array_push($slugs, $country['Slug']);
    array_push($countries, $country['Country']);
}

$day_by_day_cases = [];
$oneweek_avg  = [];
$graph = [];
$text = '';

if(isset($_POST['queryCountry'])) {
    $country = strip_tags($_POST['queryCountry']);
    $key = array_search($country, $countries);
    $slug = $slugs[intval($key)];
    //we can make the API call now.
    $response = file_get_contents("https://api.covid19api.com/dayone/country/".$slug."/status/confirmed/live");
    $get_numbers = json_decode($response, TRUE);
    //this contains cases by day
    foreach($get_numbers as $key=>$number) {
        if($key != 0) {
            // var_dump($get_numbers[$key]['Cases']);
            array_push($day_by_day_cases, $get_numbers[$key]['Cases']-$get_numbers[$key-1]['Cases']);
        } else {
            array_push($day_by_day_cases, $get_numbers[$key]['Cases']);
        }
    }

    $rest_to_7 = 7 - (count($day_by_day_cases) % 7);
    for($i = 0; $i < $rest_to_7; $i++) {
        array_push($day_by_day_cases, 0);
    }
    $counter = 0;
    while($counter < count($day_by_day_cases)) {
        $slice_array = array_slice($day_by_day_cases, $counter, 7);
        $average = array_sum($slice_array)/count($slice_array);
        array_push($oneweek_avg, $average);
        $counter=$counter+7;
    }
    // var_dump($oneweek_avg);
    foreach($oneweek_avg as $key => $value) {
        array_push($graph, array("y" => $value, "label" => "Week ".$key));
    }
    $text = "Covid Status for ".$country;
}
// var_dump($day_by_day_cases);
// var_dump($countries);
// var_dump($get_countries);

?>
<!DOCTYPE html>
<html>
<head>
    <title>COVID 7-days</title>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="styles/style.css">
</head>
<body>
    <!--Make sure the form has the autocomplete function switched off:-->
    <h1>COVID-19</h1>
    <h2>7 days stats</h2>
    <h3>Select a country to see new infections rate on every week.</h3>
    <form class="form" autocomplete="off" action="" method="post">
        <div class="autocomplete" style="width:300px;">
            <input id="queryCountry" type="text" name="queryCountry" placeholder="Country">
        </div>
        <input type="submit">
    </form>
    <div id="chartContainer"></div>
    <script src="scripts/autocomplete.js"></script>
    <script type="text/javascript">
        <?php
            $countries_js = json_encode($countries);
            echo "var countries = ". $countries_js . ";\n";
        ?>
        autocomplete(document.getElementById("queryCountry"), countries);
    </script>
    <script src="scripts/jquery-1.12.4.min.js"></script>
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
    <script type="text/javascript">
        $(function () {
            var chart = new CanvasJS.Chart("chartContainer", {
                theme: "theme2",
                animationEnabled: true,
                title: {
                    text: "<?php echo $text ?>",
                },
                axisY: {
                    title: "Infected people"
                },
                data: [
                {
                    type: "line",
                    dataPoints: <?php echo json_encode($graph, JSON_NUMERIC_CHECK); ?>
                }
                ]
            });
            chart.render();
        });
</script>
</body>
</html>