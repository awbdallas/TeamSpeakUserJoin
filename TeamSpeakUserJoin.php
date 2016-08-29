<?PHP
/**
 * 	Note: The original Author is par0noid. I forked this. I'm including the original header
 * 	I only made minor changes to make it useful for my afk situation, but I still wanted 
 * 	to give proper credit.
 *
 * ORIGINAL
  * clientlist.php
  *
  * Is a small script to demonstrate how to get a clientlist via ts3admin.class
  *
  * by par0noid solutions - ts3admin.info
  *
  * NEW:
  *  TeamSpeakUserJoin.php
  *
  *  small script to post to slack when people join teamspeak
  *
  *  Original by par0noid, modified by Austin Briggs :D
  *
*/

/*
 * TODO 
 * Command Line Options to override config options
 * Check for certain options
 *
*/ 
/*-------SETTINGS-------*/

$ini_array = parse_ini_file("config.ini");

//Wanted to put them in a file so I don't commit with passwords here
$ts3_ip = $ini_array['ts3_ip'];
$ts3_queryport = $ini_array['ts3_queryport'];
$ts3_user = $ini_array['ts3_user'];
$ts3_pass = $ini_array['ts3_pass'];
$ts3_port = $ini_array['ts3_port'];
$library_path = $ini_array['library_path'];
$time_for_checking = $ini_array['time_for_checking'];
$post_url = $ini_array['post_url'];
$slack_channel = $ini_array['slack_channel'];
/*----------------------*/

#Include ts3admin.class.php
require("$library_path");
#build a new ts3admin object
$tsAdmin = new ts3admin($ts3_ip, $ts3_queryport);

if($tsAdmin->getElement('success', $tsAdmin->connect())) {
	#login as serveradmin
	$tsAdmin->login($ts3_user, $ts3_pass);
	
	#select teamspeakserver
	$tsAdmin->selectServer($ts3_port);
	
	#get clientlist
	$clients = $tsAdmin->clientList();

	foreach($clients['data'] as $client){
		$userinfo = $tsAdmin->clientInfo($client['clid'])['data'];
		// In seconds
		$time_since_login = $userinfo['connection_connected_time'] / 1000;
		
		// Trying only to get users
		if ($userinfo['client_version'] == 'ServerQuery'){
			continue;
		}

		if ($time_since_login < $time_for_checking){
			// Taken from this thread: http://stackoverflow.com/questions/6213509/send-json-post-using-php
			$user_name = $userinfo['client_nickname'];
			if ($user_name == ""){
				continue;
			}
			$message = "" . $user_name . " has joined the TeamSpeak server";
			$data = array(
				'channel'	=> $slack_channel,
				'username'	=> 'webhookbot',
				'text'		=> $message,
				"icon_emoji"=> ":ghost:"
			);

			$content = json_encode ( $data );
			$curl = curl_init($post_url);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER,
					array("Content-type: application/json"));
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

			$json_response = curl_exec($curl);

		}	
	} 
	$tsAdmin->logout();
}else{
	echo 'Connection could not be established.';
}

/**
 * This code retuns all errors from the debugLog
 */

if(count($tsAdmin->getDebugLog()) > 0) {
	foreach($tsAdmin->getDebugLog() as $logEntry) {
		echo '<script>alert("'.$logEntry.'");</script>';
	}
}
?>
