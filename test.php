<?php

// ================ Setup Data
define('USERNAME','');
define('APIKEY','');
define('REGION','ORD');

$lb_config = array( 
    'base' => 'stroz-test-', 
    'port' => 80,
    'protocol' => 'HTTP',
    'nodes' => array('10.180.175.174'),
    'vip' => 'PUBLIC'
);

$ts = time();

// ================= Configure tests to run: 
//      authenticate 	=> required, authenticate to API
//      create 		=> optional, create and delete a Load Balancer
//      list 		=> optional, test listing of LB/LB Details
//	info		=> optional, get protocol and algorithm lists
function dotest($testname) { 
    $tests = array('authenticate','create','list','info');
    return (array_search($testname,$tests) === FALSE ? FALSE : TRUE); 
}

// ================= Set the Debug Level
define('DEBUG',0);

// ================= Require the use of the library files we need
// First, make sure that the document root (where php-cloudservers lives) is available to us
set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT']);
require('php-cloudservers/authentication.php');
require('create.php');
require('list.php');
require('delete.php');
?>
<html>
    <head>
        <title>php-loadbalancers test suite</title>
        <style type="text/css">
.fail {
    color: red;
}

.pass {
    color: green;
}

        </style>
    </head>
    <body>
<?php
if (dotest('authenticate')) {
    print "<h1>Authentication</h1>";
    $auth = authenticate(USERNAME, APIKEY);
    if (DEBUG > 5) print $auth['Status'];
    
    if ( $auth["Status"] == "Success" ) {
        print "<p class=\"pass\">Authentication Succeeded</p>";
        $xauthtoken = $auth['XAuthToken'];
        preg_match("(ord|dfw|lon)",$auth['XStorageUrl'],$regions);
        $region = $regions[0];
        $accountid = basename($auth['XServerManagementUrl']);
        print "<ul><li>Auth Token: $xauthtoken</li>";
        print "<li>Region: $region</li>";
        print "<li>Account ID: $accountid</li>";
        print "</ul></p>";
    } else {
        print "<p class=\"fail\">Authentication Failed, Is your username and api key right?</p>";
        exit;
    }
}
flush();
if (dotest('create')) {
    print "<h1>Creating a Load Balancer</h1>";
    $lb = create($xauthtoken, $region, $accountid, $lb_config['base'] . $ts, $lb_config['port'], $lb_config['protocol'], $lb_config['nodes'], $lb_config['vip']);
    if ( !array_key_exists("Status", $lb) && $lb['name'] == $lb_config['base'] . $ts ) {
        // == Save this LB as $todelete so we can clean up ==
        $todelete = $lb['id'];
        print "<p class=\"pass\">Load Balancer Creation Succeeded</p>";
        print "<ul><li>Load Balancer: {$lb['name']} - ID: {$lb['id']} - Status: {$lb['status']}</li>";
        print "<li>Protocol: {$lb['protocol']} on port {$lb['port']}</li>";
        print "<li>Building in: {$lb['cluster']['name']} with IPs:";
        print "<ul>";
        foreach ( $lb['virtualIps'] as $ip ) 
            print "<li>{$ip['ipVersion']}: {$ip['address']}</li>";
        print "</ul>";
        print "<li>Pointed at nodes:";
        foreach ( $lb['nodes'] as $node ) print " {$node['address']}";
        print "</li></ul>";
    } else {
        print "<p class=\"fail\">Load Balancer Creation Failed, {$lb['message']}</p>";
        exit;
    }
}

flush();
if (dotest('list')) {
    print "<h1>Listing Load Balancers</h1>";
    print "<h2>Load Balancers on account $accountid</h2>";
    

    
    $lblist = list_loadbalancers($xauthtoken, $region, $accountid);
    if (DEBUG > 5) print_r ($lblist);
    if ( !array_key_exists("Status", $lblist)) {
        print "<p class=\"pass\">Load Balancers:</p>";
        foreach ($lblist as $lb) {
            print "<table class=\"lbarray\">";
            foreach ($lb as $key => $value) {
                if ( $key == 'created' || $key == 'updated' ) 
                    $value = $value['time'];
                elseif ( $key == 'virtualIps' ) {
                    $v = '';
                    foreach ( $value as $vip ) 
                        $v .= $vip['address'] . '<br />';
                    $value = $v;
                }
                print "<tr><td>$key</td><td>$value</td></tr>";
            }
            print "</table>";
        }
    } else {
        print "<p class=\"fail\">Load Balancer Listing Failed, {$lblist['message']}</p>";
        exit;
    }
    print "<h2>Details for Load Balancer #$todelete</h2>";
    $lb = list_loadbalancer_details($xauthtoken, $region, $accountid, $todelete);
    if (DEBUG > 5) print_r ($lb);
    if ( !array_key_exists("Status", $lblist)) {
        print "<p class=\"pass\">Load Balancer:</p>";
        print "<table class=\"lbarray\">";
        foreach ($lb as $key => $value) {
            if ( $key == 'created' || $key == 'updated' ) 
                $value = $value['time'];
            elseif ( $key == 'cluster' )
                $value = $value['name'];
            elseif ( $key == 'connectionLogging' )
                $value = ($value['ENABLED'] != '' ? "Enabled" : "Disabled");
            elseif ( $key == 'virtualIps' || $key == 'nodes' ) {
                $v = '';
                foreach ( $value as $vip ) 
                    $v .= $vip['address'] . '<br />';
                $value = $v;
            }
            print "<tr><td>$key</td><td>$value</td></tr>";
        }
        print "</table>";
    } else {
        print "<p class=\"fail\">Load Balancer Listing Failed, {$lb['message']}</p>";
}

}

flush();
if (dotest('info')) {
    print "<h1>Semi-Static Content GETs</h1>";
    print "<h2>List Available Protocols</h2>";
    $protocols = list_protocols($xauthtoken, $region, $accountid);
    if (DEBUG > 5) print_r( $protocols );
    if ( !array_key_exists("Status", $protocols)) {
        print "<table class=\"lbarray\">";
        print "<tr><th>Protocol Name</th><th>Default Port</th></tr>\n";
        foreach ($protocols as $protocol) 
            print "<tr><td>{$protocol['name']}</td><td>{$protocol['port']}</td></tr>\n";
        print "</table>";
    }

    print "<h2>List Available Algorithms</h2>";
    $algorithms = list_algorithms($xauthtoken, $region, $accountid);
    if (DEBUG > 5) print_r( $algorithms );
    if ( !array_key_exists("Status", $algorithms)) {
        print "<p>Available Algorithms: \n<ul>";
        foreach ($algorithms as $algorithm) 
            print "<li>{$algorithm['name']}</li>\n";
        print "</ul></p>";
    }

}

flush();

if (dotest('create')) {
    // Part 2 of 'create' time to cleanup $todelete
    print "<h2>Waiting for Load Balancer #$todelete to finish Build (to delete)</h2><ul>";
    while (true) {
	$lb = list_loadbalancer_details($xauthtoken, $region, $accountid, $todelete);
	flush();
	print "<li>";
	if ($lb['Status'] !== "Failure" && $lb['status'] != 'ACTIVE') {
	    print "Load Balancer is still in Build Status: Waiting 60 seconds... ";
	    flush();
	    sleep(30);
	    print "30 seconds";
	    flush();
	    sleep(30);
	} else if ($lb['Status'] === 'Failure') {
	    die("Failed!?");
	} else {
	    print "Success!</li></ul>";
	    break;
	}
	print "</li>";
    }

    $response = remove($xauthtoken, $region, $accountid, $todelete);
    if (DEBUG > 0) print_r ($response);
    if ($response['status'] == "success") {
    	print "<p class=\"pass\">Removed Load Balancer successfully</p>";
    } else {
	    print "<p class=\"fail\">Load Balancer: $todelete is not deleted</p>";
    }
}

/* vi: ts=4 et */
?>
