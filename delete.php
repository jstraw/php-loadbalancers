<?php

require "functions.php";

function remove($xauthtoken, $region, $accountid, $id) {
    $headers = array("X-Auth-Token" => "$xauthtoken", "Content-Type" => "application/json", "Accept" => "application/json");

    $url = lbaas_url($region, $accountid); 
    if ( $url == -1 ) return error("Invalid Region: $region");
    $url .= "/loadbalancers/$id"; 

    $api_response = http_parse_message(http_request(HTTP_METH_DELETE, $url,'',array("headers"=>$headers),$info));
    // $info will give request data, $api_response for response info
    print "<pre>";
    print_r( $info );
    print_r( $api_response );
    print "</pre>";

    if (ereg("20(.)",$api_response->responseCode,$regs)) {
        return array( "status" => "success", "message" => "Deleting Load Balancer ID: $id");
    } else
        return error($api_response->responseStatus);
}

?>
