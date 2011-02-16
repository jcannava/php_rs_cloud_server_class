 <?php
//Copyright 2010 Jason Cannavale
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//   you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.
// 
/**
 * Class cloud_servers
 * This class provides functions for all of the public cloud server API's.
 *
 * @version    Release: 1
 * @link       http://code.google.com/p/phprscloudclass/downloads/detail?name=cloud_servers_class.php&can=2&q=
 * @since      Class available since Release 1
 */ 
class cloud_servers
{
	private $auth_url = "https://auth.api.rackspacecloud.com/v1.0";
	private $auth_token, $server_url, $servId, $new_server_ids;
	public $uname, $auth_key, $serverList, $imageList, $ship_id;
	public $flavorList = array();

/**
 * __construct - called when new cloud_server is defined.
 *
 * @param  uname    Rackspace cloud username
 * @param  auth_key  Rackspace cloud api_key
 * @variables sets the class variables auth_token, server_list, flavorList, and imageList for future use.
 */ 
	public function __construct($uname, $auth_key) { 
		$this->uname = $uname; $this->auth_key = $auth_key;
				if ( !$auth_token ) { $this->auth(); }
		$this->get_server_list();
		$this->flavorList();
		$this->imageList();
	}	

/**
 * auth - called by construct
 *
 * @param  none
 */ 
	private function auth() {
		$ch = curl_init();
		$response = $this->api_call($ch, "auth");
		$this->decode_headers($response);
		curl_close($ch);
	}//auth
	
/**
 * api_call - configure different url's for different api calls and set curl options to make the api call.
 *
 * @param  curlHandle	reference to a open curl Handle
 * @param	verb		what action to take (auth, list,flavor, status, create, delete)
 * @param 	data		only used for create action which is a curl POST
 */ 
	private function api_call( &$curlHandle, $verb, $data = NULL ) {
		switch($verb) {
		case "auth":
			$url = "https://auth.api.rackspacecloud.com/v1.0";
			$this->set_curl_opts($curlHandle, 'auth', $url);
			break;
			
		case "list":
			$url = $this->server_url . "/servers/detail";
			$this->set_curl_opts($curlHandle, 'list', $url);
			break;
			
	    	case "flavor":
			$url = $this->server_url . "/flavors";
			$this->set_curl_opts($curlHandle, 'flavors', $url);
			break;
			
	    	case "image":
	    		$url = $this->server_url . "/images/detail";
	    		$this->set_curl_opts($curlHandle, 'image', $url);
	    		break;
	    
		case "limits":
			$url = $this->server_url . "/limits";
			$this->set_curl_opts($curlHandle, 'limits', $url);
			break;
		
	    	case "status":
	    		$url = $this->server_url . "/servers/" . $this->servId;
	    		$this->set_curl_opts($curlHandle, 'status', $url);
	    		break;
	    	
		case "create":
			$url = $this->server_url . "/servers";
			$this->set_curl_opts($curlHandle, 'create', $url);
			curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
			break;
			
		case "delete":
			$url = $this->server_url . "/servers/" . $this->servId;
			$this->set_curl_opts($curlHandle, 'delete', $url);
			break;
		
		case "shareIp_create":
			$this->set_curl_opts($curlHandle, 'shareIp_create', $url);
			break;

		case "shareIp_list":
			$url = $this->server_url . "/shared_ip_groups";
			$this->set_curl_opts($curlHandle, 'shareIp_list', $url);
			break;

		case "shareIp_detail":
			$url = $this->server_url . "/shared_ip_groups/detail";
			$this->set_curl_opts($curlHandle, 'shareIp_list', $url);
			break;
		case "shareIp_delete":
			$url = $this->server_url . "/shared_ip_groups/" . $this->ship_id;
			$this->set_curl_opts($curlHandle, 'delete', $url);
			break;
		}
		$response = curl_exec($curlHandle);
		return $response;
	}//api_call

/**
 * set_curl_opts - set the common Curl options for API Calls
 *
 * @param  curlHandle	reference to a open curl Handle
 * @param	verb		what action to take (auth, list,flavor, status, create, delete)
 * @param   url			the url to call
 */
	private function set_curl_opts( &$curlHandle, $verb , $url) {
	// uncomment the following for debugging purposes.
	// curl_setopt($curlHandle, CURLOPT_VERBOSE, true);

    	curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);	
    	curl_setopt($curlHandle, CURLOPT_HEADER, 0);
		if ( !preg_match("/auth/i", $verb)) { curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $this->auth_token)); }
		
		if ( $verb == "auth" ) { 
		    $xauthUser = "X-Auth-User: " . $this->uname;
		    $xauthKey = "X-Auth-Key: " . $this->auth_key;
		    curl_setopt($curlHandle, CURLOPT_HEADER, 1);
			curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array($xauthUser, $xauthKey));
			curl_setopt($curlHandle, CURLOPT_URL, $url);
		}
		else if ( $verb == "create") {
			curl_setopt($curlHandle, CURLOPT_POST, 1);
			curl_setopt($curlHandle, CURLOPT_URL, $url);
		}
		else if ( $verb == "delete") {
			curl_setopt($curlHandle, CURLOPT_URL, $url);
			curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "DELETE");
		}
		else {
			curl_setopt($curlHandle, CURLOPT_URL, $url);
		}
	}//set_curl_opts

/**
 * decode_headers - decode return values from auth and set class variables for future use.
 *
 * @param  output	auth api_call return output.
 */ 
    private function decode_headers($output) {
		preg_match("/X-Auth-Token: [a-zA-Z0-9]+-[a-zA-Z0-9]+-[a-zA-Z0-9]+-[a-zA-Z0-9]+-[a-zA-Z0-9]+/", $output, $token);
		preg_match("/X-Server-Management-Url: https:\/\/(.*)/", $output, $mgmt_url);

		$this->auth_token = $token[0];
		list($header_junk, $this->server_url) = explode(": ", trim($mgmt_url[0]));
    }//function decode_headers

/**
 * json_debug - in case of json problems
 *
 * @param  error	error string returned from json_decode/json_encode
 * @param  str		full output from json_decode/json_encode and api call
 */ 
	private function json_debug($error, $str) {
		switch($error)
    	{
        	case JSON_ERROR_DEPTH:
            	print " - Maximum stack depth exceeded\n";
        	break;
        	case JSON_ERROR_CTRL_CHAR:
            	print " - Unexpected control character found\n";
        	break;
        	case JSON_ERROR_SYNTAX:
            	print " - Syntax error, malformed JSON\n";
        	break;
        	case JSON_ERROR_NONE:
            	print " - No errors\n";
        	break;
    	}
		print_r($str);
	}

/**
 * get_server_list - can be called at any time, but called at construct time to buil a list of all servers in account
 * for ease of use later.
 *
 * @param  none
 */ 
	public function get_server_list() {
		$ch = curl_init();
		$response = $this->api_call($ch, 'list');
		$decoded = json_decode($response, TRUE);
		
		if ( json_last_error() != "JSON_ERROR_NONE") {
			$this->json_debug(json_last_error(), $response);
		}
		
		for ($i = 0; $i < sizeof($decoded['servers']); $i++ ) {
			$this->serverList[] = $decoded['servers'][$i];
		}

		curl_close($ch);
	}//get_server_list

/**
 * flavorList - Usually called by construct builds a list of all available cloud server flavors for future use. 
 *
 * @param  none
 */ 
	public function flavorList() {
		$ch = curl_init();
		$response = $this->api_call($ch, 'flavor');
		$this->flavorList = json_decode($response, TRUE);

		if ( json_last_error() != "JSON_ERROR_NONE") {
			$this->json_debug(json_last_error(), $response);
		}

		
		curl_close($ch);
	}//flavorList

/**
 * imageList - called by construct, builds a list of user created images.
 *
 * @param  none
 */ 
	public function imageList() {
		$ch = curl_init();
		$response = $this->api_call($ch, 'image');
		
		$this->imageList = json_decode($response, TRUE);
		
		if ( json_last_error() != "JSON_ERROR_NONE") {
			$this->json_debug(json_last_error(), $response);
		}

		curl_close($ch);
	}//imageList

/**
 * serverStatus - display server status for serverid
 *
 * @param  id 	id of server to get status for.
 */ 
	public function serverStatus ( $id ) {
		$this->servId = $id;
		$ch = curl_init();
		$response = $this->api_call($ch, 'status');
		$decoded = json_decode($response);
		curl_close($ch);
		
		return $response;
	}//serverStatus

/**
 * createServer - create a new cloud server.
 *
 * @param  name		name of server to create (string)
 * @param  imageId	create server from image ID # (see imageList)
 * @param  flavorId	flavor of server to create (see flavorList)
 * @param  sharedGroupIp	create server with ip from a shared group
 */ 
	public function createServer( $name, $imageId, $flavorId, $sharedGroupId = NULL ) {
		$data = array(
					"server" => array(
					"name" => $name,
					"imageId" => $imageId,
					"flavorId" => $flavorId,
					"sharedIpGroupId" => $sharedGroupId),);

		$json_data = json_encode($data);
		$ch = curl_init();
		$response = $this->api_call($ch, 'create', $json_data);
		$decoded = json_decode($response, TRUE);

		curl_close($ch);
		return $decoded;
	}//createServer

/**
 * rebootServer - reboot server
 *
 * @param  serverid	id of cloud server to reboot
 */ 
	public function rebootServer ($serverId) {
		$this->servId = $serverId;
		$ch = curl_init();
		$response = $this->api_call($ch, 'reboot');
	}//rebootServer

/**
 * deleteServer - delete a cloud server
 *
 * @param  serverid		id of server to delete.
 */ 
	public function deleteServer($serverId) {
		$this->servId = $serverId;	
		$ch = curl_init();
		$response = $this->api_call($ch, 'delete');
		curl_close($ch);
	}//deleteServer
	
	public function getServerIdByName($name) {
		for ( $i = 0; $i < sizeof($this->serverList); $i++ ) {
			foreach ( $this->serverList[$i] as $key => $value ) {
				if ( !is_array($value) && preg_match("/$name/i", $value)) {
					$id = $this->serverList[$i]['id'];	
					break;
				}
			}
		}
		return $id;
	}//getServerIdByName
	
	public function getFlavorIdByName($name) {
		for ($i = 0; $i < sizeof($this->flavorList['flavors']); $i++) {
			foreach ( $this->flavorList['flavors'][$i] as $key => $value ) {
				if ( !is_array($value) && preg_match("/$name/i", $value)) {
						$id = $this->flavorList['flavors'][$i]['id'];
						break;
				}
			}
		}
		return $id;
	}//getFlavorIdByName

	public function getImageIdByName($name) {
				for ($i = 0; $i < sizeof($this->imageList['images']); $i++) {
			foreach ( $this->imageList['images'][$i] as $key => $value ) {
				if ( !is_array($value) && preg_match("/$name/i", $value)) {
						$id = $this->imageList['images'][$i]['id'];
						break;
				}
			}
		}
		return $id;
	}//getImageIdByName
	
	public function createSharedIpGroup($id, $name) {
		$data = array(
			"sharedIpGroup" => array(
			"name" => $name,
			"server" => $id,),);

		$data = json_encode($data);
		print_r($data);
		$curlHandle = curl_init();
		$url = $this->server_url . "/shared_ip_groups/";
		curl_setopt($curlHandle, CURLOPT_VERBOSE, true);
        	curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        	curl_setopt($curlHandle, CURLOPT_HEADER, 0);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $this->auth_token));
		curl_setopt($curlHandle, CURLOPT_URL, $url);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($curlHandle);
		print_r($response);
		curl_close($curlHandle);
	}
	
	public function deleteSharedIpGroup($id) {
			$this->ship_id = $id;
			$ch = curl_init();
			$response = $this->api_call($ch, 'shareIp_delete');
			curl_close($ch);
	}
	public function listSharedIpGroups() {
			$ch = curl_init();
			$response = $this->api_call($ch, 'shareIp_detail');
			curl_close($ch);	
			$response = json_decode($response, true);
			return $response;
	}
	public function addToSharedIP($id, $srvid) {
print "hello\n";
		$data = array(
				"sharedIpGroupId" => $id ,
				"configureServer" => "false");

		$data = json_encode($data);
		print_r($data);
                $curlHandle = curl_init();
                $url = $this->server_url . "/servers/" . $srvid . "/ips/public/address";
                curl_setopt($curlHandle, CURLOPT_VERBOSE, true);
                // curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curlHandle, CURLOPT_HEADER, 0);
                curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $this->auth_token));
		curl_setopt( $curlHandle, CURLOPT_HTTPHEADER, array( 'Expect:' ) );
                curl_setopt($curlHandle, CURLOPT_URL, $url);
		curl_setopt($curlHandle, CURLOPT_PUT, true);
		curl_setopt($curlHandle, CURLOPT_INFILE, $data);
		curl_setopt($curlHandle, CURLOPT_INFILESIZE, strlen($data));
                $response = curl_exec($curlHandle);
                print_r($response);
	}
	public function listSpecificGroup( $id ) {
		$ch = curl_init();
		$url = $this->server_url . "/shared_ip_groups/detail"; // . $id;
		$this->set_curl_opts($ch, 'shareIp_list', $url);
		$response = curl_exec($ch);
		print_r($response);
	}
		
	public function limits() {
			$ch = curl_init();
			$response = $this->api_call($ch, 'limits');
			curl_close($ch);
			return $response;
	}
}//class cloud_servers
?>
