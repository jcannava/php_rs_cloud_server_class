<?php
	include('php_rs_cloud_server_class.php');

// Create a new object called cloud server and complete rackspace auth with username and API key.
// <username> should be changed to your username
// <api key> api key for that username.
$request = new cloud_servers('<username>','<api key>'); 

// Get ImageID for RHEL 5.5, by changing the OS string you can select any of the available OS flavors
$imageId = $request->getImageIdByName("Red Hat Enterprise Linux 5.5");

// Get Flavor ID - size of instance
$flavorId = $request->getFlavorIdByName("2G");

// Set the hostname
$srvname = "php_rs_cloud_server_class-test2";
$res = $request->createServer($srvname, $imageId, $flavorId);

// Print server details
print_r($res);
?>
