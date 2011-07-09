<?php

function lbaas_url($region, $accountid) {
    if ( strtoupper($region) == "ORD" ) 
        $url = "https://ord.loadbalancers.api.rackspacecloud.com/v1.0/$accountid";
    elseif ( strtoupper($region) == "DFW" )
        $url = "https://dfw.loadbalancers.api.rackspacecloud.com/v1.0/$accountid";
    elseif ( strtoupper($region) == "LON" )
        $url = "https://lon.loadbalancers.api.rackspacecloud.com/v1.0/$accountid";
    else 
	$url = -1;
    return $url;
}

function error($message) {
    return array( "status" => "failure", "message" => $message );
}

// debug_response($info, $api_response);
/*
    print "<pre>";
    print_r( $info );
    print_r( $api_response );
    print "</pre>";
*/
?>

