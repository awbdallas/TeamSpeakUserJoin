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

$ts3_ip = $ini_array['ts3_ip'];
$ts3_queryport = $ini_array['ts3_queryport'];
$ts3_user = $ini_array['ts3_user'];
$ts3_pass = $ini_array['ts3_pass'];
$ts3_port = $ini_array['ts3_port'];
$library_path = $ini_array['library_path'];
$time_for_checking = $ini_array['time_for_checking'];
$post_url = $ini_array['post_url'];
$slack_channel = $ini_array['slack_channel'];
$users_file = $ini_array['users_file'];
/*----------------------*/

#Include ts3admin.class.php
require("$library_path");
#build a new ts3admin object
$tsAdmin = new ts3admin($ts3_ip, $ts3_queryport);

# Current Users
$new_users = array();
$left_users = array();

# Adding users that we got from last query, oh and is everything in PHP a 
# hack?
$previous_users  = file($users_file, 
	FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);


if($tsAdmin->getElement('success', $tsAdmin->connect())) {

	# login as serveradmin
	$tsAdmin->login($ts3_user, $ts3_pass);
	# select teamspeakserver
	$tsAdmin->selectServer($ts3_port);
	# get clientlist
	$clients = $tsAdmin->clientList();
	
	$current_users_file = fopen($users_file, "w") or die ("Unable to open!");

	foreach($clients['data'] as $client){
		$userinfo = $tsAdmin->clientInfo($client['clid'])['data'];
		// Seconds are nicer to work with
		$user_name = $userinfo['client_nickname'];
		
		// Trying only to get users
		if ($userinfo['client_version'] == 'ServerQuery' ||	$user_name == ""){	
			continue;
		}

		fwrite($current_users_file, "$user_name\n");

		if(in_array($user_name, $previous_users)){
			# Don't have to worry about it now
			unset($previous_users, $user_name);
		}else{
			# New User
			$new_users[$user_name] = "";
		}

	}
	$tsAdmin->logout();
	
}else{
	echo 'Connection could not be established.';
}

# Someone left : ( 
if (!empty($previous_users)){
	// Note: I do this just to try to make it a little nicer on the names 
	$left_users = $previous_users;		
}


if (!empty($new_users)){
	// Normal Message
	$message = "";

	foreach($new_users as $key => $value){
		$message = $message . "$key has joined the teamspeak\n";
	}

	send_message($message, $slack_channel, $post_url);
}

if (!empty($left_users)){
	$message = "";

	foreach($left_users as $key => $value){
		$message = $message . "$left_users[$key] has left the teamspeak\n";
	}
	
	send_message($message, $slack_channel, $post_url);
}
/**
 * This code retuns all errors from the debugLog
 */

if(count($tsAdmin->getDebugLog()) > 0) {
	foreach($tsAdmin->getDebugLog() as $logEntry) {
		print($logEntry);
	}
}


# I think it's a different namespace? But who knows cause php
function send_message($message, $slack_channel, $post_url){
// Taken from this thread: 
	// http://stackoverflow.com/questions/6213509/send-json-post-using-php
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
?>
