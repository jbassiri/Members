<?php
# slack integration to welcome members to channels
require('rchain/initwelcome.php'); # define apptoken
#set verbose error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

//TODO check API token to be sure slack is sending the event
 // Retrieve the request body from slack and parse it as JSON
    $input = @file_get_contents("php://input");
     file_put_contents("rchainwelcomedata", "this stuff\n".$input);
    $json = json_decode($input);
    if (isset($json->challenge)) {
        echo "challenge=".$json->challenge."\n";
        header('Content-Type: application/x-www-form-urlencoded');
        http_response_code(200);
        exit();
    }
    $token = $json->token;
    echo $input."\n\n";
    $event = $json->event;

// get the username from user object id
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"https://slack.com/api/users.info");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
        'token' =>  $apptoken,
        'user' => $event->user)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);
curl_close ($ch);
echo $server_output."\n\n";
$username = json_decode($server_output)->user->name;
echo "username=$username\n\n";

// get the channel name from channel object id
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"https://slack.com/api/channels.info");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
        'token' =>  $apptoken,
        'channel' => $event->channel)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);
curl_close ($ch);
echo $server_output."\n\n";
$channel = json_decode($server_output)->channel->name;
echo "channel=$channel\n\n";

// see if user was invited by someone
    if (isset($event->inviter))   {
        $start="You have been invited to join #{$channel}.";
    } else {
        $start="Welcome to #{$channel}.";
}
// set channel specific message
switch ($channel) {
    // the general message never gets sent because new users are auto-added
    // which doesn't trigger an event but it works if they leave and rejoin
    case "general":
        $end="Nice to see you joined #general again. More info is available at <http://rchain.coop|our website>...";
        break;
    case "identity":
        $end="Hi {$username}, we detected that you joined the #{$channel} channel. Maybe you want to check out the <https://docs.google.com/document/d/1y0uoduAO3qMs9cJ7hmO8jmlvlPDBLm8es85b_wKDB2Q/edit|BYOID (Bring Your Own Identity) Project>. Also there's a weekly meeting, every saturday at 11am New York time, in this <https://zoom.us/j/6853551826|Zoom room>. You can contact @kitblake if you have questions.";
        break;
    case "rholang":
        $end="Hi {$username}, we detected that you joined the #{$channel} channel. If you're new to Rholang and/or Pi Calculus maybe you want to check out the paper <http://mobile-process-calculi-for-programming-the-new-blockchain.readthedocs.io/en/latest/|Mobile process calculi for programming the blockchain>. In any case you can contact @jimscarver if you have questions.";
        break;
default:
        // in the future this should be 'do nothing' but we keep it for testing
        $end="More info is available at <http://rchain.coop|our website>...";
        //http_response_code(200); // always succeed
        //exit();
}
$text = $start." ".$end;


// post the message to user on slack
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"https://slack.com/api/chat.postMessage");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
        'token' =>  $apptoken,
        'channel' => "@".$name,
        'text' => $text)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);
curl_close ($ch);
echo $server_output."\n\n";

http_response_code(200); // always succeed
?>
