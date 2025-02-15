<?php
if (isset($_GET['query'])) {
    $query = strtolower($_GET['query']);
    $airlines = json_decode(file_get_contents('airlines.json'), true);
    
    $results = array_filter($airlines, function($airline) use ($query) {
        return strpos(strtolower($airline['name']), $query) !== false || 
               strpos(strtolower($airline['code']), $query) !== false;
    });

    echo json_encode(array_values($results));
}
?>
