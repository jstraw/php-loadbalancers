<?php

require "functions.php";

function create($xauthtoken, $region, $accountid, $name, $port, $protocol, $nodes, $vip, $algorithm="LEAST_CONNECTIONS") {
    $headers = array("X-Auth-Token" => "$xauthtoken", "Content-Type" => "application/json", "Accept" => "application/json");

    $url = lbaas_url($region, $accountid); 
    if ( $url == -1 ) return error("Invalid Region: $region");
    $url .= "/loadbalancers"; 

    // Setup the subarray for all the nodes, we assume that port will match...
    $nodearray = array();
    foreach ( $nodes as $ip ) {
        $nodearray[] = array( "address" => $ip, "port" => $port, "condition" => "ENABLED" );
    }
    // Setup the vip subarray (assuming we only do one for now...)
    $viparray = array();
    if ($vip == "PUBLIC" or $vip == "SERVICENET") 
        $viparray[] = array("type" => $vip);
    else 
        $viparray[] = array("id" => $vip);

    $request = json_encode(array("loadBalancer" => array( "name" => $name, "port" => $port, "protocol" => $protocol, "algorithm" => $algorithm, "nodes" => $nodearray, "virtualIps" => $viparray)));
    print $request;
    $api_response = http_parse_message(http_post_data($url,$request,array("headers"=>$headers),$info));
    // $info will give request data, $api_response for response info
    #print "<pre>";
    #print_r( $info );
    #print_r( $api_response );
    #print "</pre>";

    if (ereg("20(.)",$api_response->responseCode,$regs)) {
        $loadBalancer =  json_decode($api_response->body,true);
        return $loadBalancer['loadBalancer'];
    } else
        return error($api_response->responseStatus);
}

?>
