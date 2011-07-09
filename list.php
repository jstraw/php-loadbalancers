<?php

require "functions.php";


function list_protocols($xauthtoken, $region, $accountid) {
    $url = "/loadbalancers/protocols";
    $response = getlisting($xauthtoken, $region, $accountid, $url);
    if ($response["Status"] === "Failure") return $response;
    else return $response['protocols'];
}

function list_algorithms($xauthtoken, $region, $accountid) {
    $url = "/loadbalancers/algorithms";
    $response = getlisting($xauthtoken, $region, $accountid, $url);
    if ($response["Status"] === "Failure") return $response;
    else return $response['algorithms'];
}

function list_loadbalancers($xauthtoken, $region, $accountid, $deleted=false) {
    $url = "/loadbalancers" . ($deleted ? "?status=DELETED" : "");
    $response = getlisting($xauthtoken, $region, $accountid, $url);
    if ($response["Status"] === "Failure") return $response;
    else return $response['loadBalancers'];
}

function list_loadbalancer_details($xauthtoken, $region, $accountid, $id) {
    $url = "/loadbalancers/" . $id;
    $response = getlisting($xauthtoken, $region, $accountid, $url);
    if ($response["Status"] === "Failure") return $response;
    else return $response['loadBalancers'];
}
// All of the list functions run almost exactly the same way,
//  Took all of the identical pieces and extracted to getlisting
function getlisting($xauthtoken, $region, $accountid, $urlext) {
    $headers = array("X-Auth-Token" => "$xauthtoken");

    $url = lbaas_url($region, $accountid); 
    if ( $url == -1 ) return error("Invalid Region: $region");
    $url .= $urlext; 

    $api_response = http_parse_message(http_get($url,array("headers"=>$headers),$info));
    // $info will give request data, $api_response for response info
    if (ereg("20(.)",$api_response->responseCode,$regs)) {
        return json_decode($api_response->body,true);
    } else
        return error($api_response->responseStatus);
}
?>
