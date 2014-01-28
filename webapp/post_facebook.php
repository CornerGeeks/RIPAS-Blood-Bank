<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mohdlee
 */

//Facebook SDK taken from https://github.com/facebook/facebook-php-sdk
require( './libs/facebook/facebook.php' );
require( 'config.php' );


$config = array(
	'appId' => FB_APP_ID,
	'secret' => FB_APP_SECRET,
	'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
);

$facebook = new Facebook($config);
$user_id = @$facebook->getUser();

?>

<html>
<head></head>
<body>

<?php
if($user_id && has_permissions()) {
	try {
		$access_token = $facebook->getAccessToken();
		// Give the user a logout link
		# echo '<br /><a href="' . $facebook->getLogoutUrl() . '">Logout Facebook</a>';

		//Retreive Pages administered by this user
		//https://graph.facebook.com/me/accounts?access_token=
		$user_pages = $facebook->api('/me/accounts', 'GET');

		if (null != $user_pages && array_key_exists('data', $user_pages)) {
			?>
			<form name="form_message" method="POST" action="<?php echo $_SERVER['PHP_SELF'] ."?". $_SERVER['QUERY_STRING'] ?>">

				<div class='row'>
					<div><label for="code_page_id">Post to:</label></div>
					<select name="code_page_id">
					<?php
					foreach($user_pages['data'] as $user_page_detail) {
						?>
							<option value="code=<?php echo urlencode($user_page_detail['access_token']) ?>&page_id=<?php echo urlencode($user_page_detail['id']) ?>"><?php echo $user_page_detail['name'] ?></option>
						<?php
					}
					?>
					</select>
				</div>
				<div class='row'>
					<div><label for="new_message">Message:</label></div>
					<textarea name="new_message"></textarea>
				</div>
				<input type="submit" value="post" />
			</form>


			<?php


			if (array_key_exists('new_message', $_POST)  && array_key_exists('code_page_id', $_POST)) {
				//Post the message
				parse_str($_POST['code_page_id'], $code_page_arr);
				$code = $code_page_arr['code'];
				$page_id = $code_page_arr['page_id'];
				$new_message = strip_tags($_POST['new_message']);
				// date_default_timezone_set('Asia/Brunei');
				// $new_message .= "\r\n\r\nPosted from Brunei Blood Bank Status App: " . date('d/m/Y h:i:s a', time());
				$user_page_post = $facebook->api("/$page_id/feed", 'POST', 
					array(
						"message" => $new_message, 
						"access_token" => $code )
				);
				if($user_page_post && !empty($user_page_post['id']))
					echo "<p><a href='http://facebook.com/" . $user_page_post['id'] . "'>Message  posted</a></p>";
				else
					echo "<p>Problem posting message</p>";
			}

		}
		else{
			echo "<p>You don't have any Facebook page associated with your Facebook account</p>";
		}


	} catch(FacebookApiException $e) {
		// If the user is logged out, you can have a
		// user ID even though the access token is invalid.
		// In this case, we'll get an exception, so we'll
		// just ask the user to login again here.
		echo "Something went wrong: " . $e->getMessage();
		error_log($e->getType());
		error_log($e->getMessage());
	}
} else {

	// No user, so print a link for the user to login
	// To post to a user's wall, we need publish_stream permission
	// We'll use the current URL as the redirect_uri, so we don't
	// need to specify it here.
	// offline_access = non-expiring access token
	// manage_pages = posting to page
	show_login();
}

function permissions(){
	return array('manage_pages', 'publish_stream');
}

function show_login() {
	global $facebook;
	$login_url = $facebook->getLoginUrl( array( 'scope' => implode(",",permissions()) ));
	echo '<a href="' . $login_url . '">Login to Facebook and Grant Necessary Permissions</a>';
}

function has_permissions() {
	global $facebook;
	$permissions = $facebook->api("/me/permissions");
	foreach(permissions() as $perm){
		if( !array_key_exists($perm, $permissions['data'][0]) ) {	
			return false;
		}
	}
	return true;
}

?>

</body>
</html>
