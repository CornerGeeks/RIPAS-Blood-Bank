<h3>Post to Twitter</h3>
<form name="form_message" method="POST" action="<?php echo $_SERVER['PHP_SELF'] ."?". $_SERVER['QUERY_STRING'] ?>" enctype="multipart/form-data">
	<div class='row'>
		<div>
			<div><label for="new_message">Message:</label></div>
			<textarea name="new_message"></textarea>
		</div>
		<div>
			<label for="image[]">Picture:</label>
			<input type="file" name="image[]" />
		</div>
	</div>
	<input type="submit" value="post" />
</form>
<?php
require_once ('libs/twitter-codebird/src/codebird.php');
require( 'config.php' );
\Codebird\Codebird::setConsumerKey(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET); // static, see 'Using multiple Codebird instances'

$cb = \Codebird\Codebird::getInstance();
session_start();

if (! isset($_SESSION['oauth_token'])) {
    // get the request token
    $reply = $cb->oauth_requestToken(array(
        'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
    ));

    // store the token
    $cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
    $_SESSION['oauth_token'] = $reply->oauth_token;
    $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
    $_SESSION['oauth_verify'] = true;

    // redirect to auth website
    $auth_url = $cb->oauth_authorize();
    header('Location: ' . $auth_url);
    die();

} elseif (isset($_GET['oauth_verifier']) && isset($_SESSION['oauth_verify'])) {
    // verify the token
    $cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
    unset($_SESSION['oauth_verify']);

    // get the access token
    $reply = $cb->oauth_accessToken(array(
        'oauth_verifier' => $_GET['oauth_verifier']
    ));

    // store the token (which is different from the request token!)
    $_SESSION['oauth_token'] = $reply->oauth_token;
    $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;

    // send to same URL, without oauth GET parameters
    header('Location: ' . basename(__FILE__));
    die();
}

// assign access token on each page load
$cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);



#$reply = (array) $cb->statuses_homeTimeline();
#$reply = $cb->statuses_update('status=Whohoo, I just tweeted!');
#print_r($reply);


$message = "";
if(!empty($_POST)){
	$message = $_POST['new_message'];
}
/*
Array ( [image] => Array ( 
		[name] => Array ( [0] => Lenovo.png [1] => Dell - Listing.png ) 
		[type] => Array ( [0] => image/png [1] => image/png ) 
		[tmp_name] => Array ( [0] => /Applications/mampstack-5.4.23-0/php/tmp/phpc3zBqm 
			                  [1] => /Applications/mampstack-5.4.23-0/php/tmp/php4wOLhh ) 
		[error] => Array ( [0] => 0 [1] => 0 ) 
		[size] => Array ( [0] => 86957 [1] => 103734 ) 
	) 
)
//*/
$attachments = array();
$attachments_str = "";
if(!empty($_FILES)){
	foreach($_FILES['image']['tmp_name'] as $k=>$v){
		if($v == '') continue;
		$imageData = getimagesize($v);
		if(!empty($imageData)){
			$attachments[] = $v;
			if($attachments_str != "") $attachments_str .= ",";
			$attachments_str .= $v;
		}
	}
}
if(!empty($message) || !empty($attachments)){
	$params = array(
		'status' => $message,
		'media[]' => !empty($attachments) ? $attachments[0] : ""
	);
	if(empty($params['media[]']))
		$reply = $cb->statuses_update('status=' . $message);
	else
		$reply = $cb->statuses_updateWithMedia($params);

	if(!empty($reply->id)){
		$post_id = $reply->id;
		$user_id = $reply->user->id;
		$user_name = $reply->user->screen_name;
		$link = "https://twitter.com/$user_name/status/$post_id";
		echo "Status Posted: <a href=\"$link\">$link</a>";
	}
	else{
		?>
		<h3>ERROR!</h3>
		<?php
		foreach($reply->errors as $i=>$error){
			echo "<h4>". $error->message ."</h4>";
		}
	}
}

?>
