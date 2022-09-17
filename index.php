<?php
error_reporting(E_ERROR | E_PARSE);

// Convert createdAt to indonesian format
function formatted($date)
{
    $date = date('d-m-Y H:i', strtotime($date));
    $bulan = array(
        1 =>   'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    $slice_date = explode('-', $date);

    return $slice_date[0] . ' ' . $bulan[(int)$slice_date[1]] . ' ' . $slice_date[2] . ' WIB';
}

// Get received name
function getName($data)
{
    $data = preg_replace("/DELIVERED TO /", "", $data);
    $data = preg_replace('/\s+/', ' ', $data);
    $data = explode('|', $data);
    $data = $data[0];
    $data = preg_replace("/[^a-z A-Z]/", "", $data);

    return $data;
}

$response = [];
$history = [];

// get data from url
$link = 'https://gist.githubusercontent.com/nubors/eecf5b8dc838d4e6cc9de9f7b5db236f/raw/d34e1823906d3ab36ccc2e687fcafedf3eacfac9/jne-awb.html';
$jsonData   = file_get_contents($link);

// if get content success
if (file_get_contents($link)) {
    $code = '060101';
    $message = 'Delivery tracking detail fetched successfully';

    // load document html to dom
    $dom = new DOMDocument;
    $dom->loadHTML($jsonData);

    // get data from table html
    $tbody = $dom->getElementsByTagName('tbody');
    $thead  = $dom->getElementsByTagName('thead');
    $tr  = $dom->getElementsByTagName('tr');

    // var_dump($tr->length);
    // die();
    $i = 0;
    // looping to get data from element in table
    foreach ($tr as $element) {
        $i++;
        if ($element->getElementsByTagName('td')->item(0)->textContent == 'History ') {
            break;
        }
    }
    foreach ($tr as $element1) {
        $formatted = [];
        $createdAt = $element1->getElementsByTagName('td')->item(0)->textContent; // get createdAt data
        $description = $element1->getElementsByTagName('td')->item(1)->textContent; // get description data
        // Convert datetime
        $newCreatedAt = date("d-m-Y H:i", strtotime($createdAt));
        $formatted['createdAt'] = formatted($createdAt);

        // Push to array
        array_push($history, array(
            "description" => $description,
            "createdAt" => $createdAt,
            "formatted" => $formatted
        ));
    }
    array_splice($history, 0, $i);

    // Make array response
    $receivedBy = getName($description);
    $histories = array_reverse($history);
}
// if get content failure
else {
    $code = '404';
    $message = 'Delivery tracking detail fetched failure';
    $receivedBy = null;
    $histories = null;
}
$response['status']['code'] = $code;
$response['status']['message'] = $message;
$response['data']['receivedBy'] = $receivedBy;
$response['data']['histories'] = $histories;
echo json_encode($response, JSON_PRETTY_PRINT);
