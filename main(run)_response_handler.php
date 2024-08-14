<?php

include 'request_handler.php';

// Process the bid request and get the response
$response = handleBid($bidRequestJson, $campaigns);

// Output the response
echo $response;