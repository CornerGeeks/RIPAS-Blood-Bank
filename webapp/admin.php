<?php
require_once("header.php");
require_once('config.php');
require_once("PostToFacebook.php");
require_once("PostToTwitter.php");
session_start();

$bank_id=1;
$latest=get_latest(1);
// update the DB!
if(@$_POST["action"]=="update_blood"){
	foreach($blood_type as $type=>$data){
		$status=@$_POST["status_".$type];
		if(isset($status)){
			if(@$latest[$type]["status"]!=$status){
				DB::query("insert into bloods(bank_id,type,status) values(?,?,?)",array($bank_id,$type,$status));
				$latest[$type]["status"]=$status;
			}
		}
	}
}

$config = array(
	'TWITTER_CONSUMER_KEY' => TWITTER_CONSUMER_KEY,
	'TWITTER_CONSUMER_SECRET'=> TWITTER_CONSUMER_SECRET,
	'appId' => FB_APP_ID,
	'secret' => FB_APP_SECRET,
	'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
);

// handle 3rd party posting
$posting = array(); 
$posting['facebook'] = new PostToFacebook($config);
#$posting['twitter'] = new PostToTwitter($config); // TODO: fix this



// message posting
if (@$_POST['message_preview_input'] != "" ){
	$message = "";
	$message .= $_POST['message_preview_input'] . "\n";
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

	$posting_message = array(
		'message' => $message,
		'attachments' => $attachments,
	);

	$results = array();
	foreach($posting as $id=>$postingObj)
	{
		if(@$_POST["post_to"] == $id){
			$results[$id] = $postingObj->postMessage($posting_message, array('post'=>$_POST, 'get' => $_GET, 'files' => $_FILES));
		}			
	}	
}

?>

<?php if(@$_GET["print"]): ?>
<pre>
$_POST:
<?php print_r($_POST); ?>

DB data:
<?php print_r($latest); ?>
</pre>
<?php endif; ?>


<style>
	label {width:40px;display:block;float:left;}
	label.inline { display: inline; float: none; width: auto; }
	label.block { display: block; float: none; width: auto; }
	.clear { clear: both; }
	#form_blood {
		float: left;
		margin-right: 1em;
	}
	#preview {
		border-left : 1px solid #ddd;
		padding: 0.5em 1em;
		float: left;
	}
		#preview #message_preview{
			width: 12em;
			border : 1px solid #ddd;
			background-color: #ccc;
			padding: 5px;
		}
</style>

<h1>Update Levels</h1>
<?php if(isset($results)) : ?>
	<?php foreach($results as $id=>$messageResponse) : ?>
		<?php if ($messageResponse['STATUS'] == 200) : ?>
			Posted to <?php echo $id ?>: <a href="<?php echo $messageResponse['data']['link'] ?>"><?php echo $messageResponse['data']['link'] ?></a>
		<?php else : ?>
			Failed posting to <?php echo $id ?>.
			<?php foreach($messageResponse['data'] as $err) : ?>
				<div class='error'><?php echo $err; ?></div>
			<?php endforeach; ?>
		<?php endif; ?>
	<?php endforeach; ?>
<?php endif; ?>
<form id="form_blood" method="post">
<input type="hidden" name="action" value="update_blood"/>
<?php $blood_type_count = 0; ?>
<?php foreach($blood_type as $type=>$data): $blood_type_count++; ?>
<div class="blood_row">
	<label><?=$type;?></label>
	<?php foreach($status_level as $i=>$n): ?>
	<input class="updatePreview" type="radio" 
		<?=($i==$latest[$type]["status"]) ? "checked" : "" ; ?>
		name="status_<?=$type;?>" 
		value="<?=$i;?>" 
		id="blood_<?=$blood_type_count?>_<?=$i;?>"/>
	<label class="inline" for="blood_<?=$blood_type_count?>_<?=$i;?>"><?=$n;?></label>
	<?php endforeach; ?>
</div>
<?php endforeach; ?>
<div>
	<label class="block" for="message_header">Message:</label>
	<textarea id="message_header" name="message_header" class="updatePreview"></textarea>
</div>

<!-- <div>
	<label for="message_footer" class="block">Message footer:</label>
	<textarea id="message_footer" name="message_footer" class="updatePreview"></textarea>
</div>
 -->	<div>
	Post to
	<?php foreach($posting as $id=>$postingObj) : ?>
		<div>
			<?php if($postingObj->hasAccess()) : ?>
			<input type="checkbox" name="post_to" id="post_to_<?php echo $id ?>" value="<?php echo $id ?>" /><label class="inline" for="post_to_<?php echo $id ?>"><?php echo $postingObj->getName() ?></label>
			<?php else : ?>
			<?php endif; ?>
			<?php echo $postingObj->toString(); ?>
		</div>
	<?php endforeach; ?>
</div>
<textarea id="message_preview_input" name="message_preview_input" style="display: none"></textarea>
<input type="submit"/>
</form>
<div id="preview">
	<strong>Preview of message:</strong>
	<div id="message_preview">
	</div>
</div>
<br class="clear" />
<script src="js/jquery-1.10.2.min.js"></script>
<script>
$(document).ready(function(){
	var form = $("#form_blood");
	var messageHeader = $("#message_header");
	//var messageFooter = $("#message_footer");

	$(".updatePreview").on('keyup', function(){ updatePreview(); }).trigger('keyup');
	$(".updatePreview").on('change', function(){ updatePreview(); });
	function updatePreview(){

		var msg = "";
		var blood = "";
		form.find(".updatePreview:checked").each(function(){
			var obj = jQuery(this);
			var blood_type = obj.attr("name").replace("status_", "").toUpperCase();
			var status = obj.val();
			var statusText = "";
			switch(status){
				case "1":
					statusText = "Very Low";
					break;
				case "2":
					statusText = "Low";
					break;
			}
			if(status == "1" || status == "2")
				blood += "  " + blood_type + ": " + statusText + "\n";

		});
		msg +=  "<div>" + messageHeader.val()  + "</div>";
		msg +=  "<div>" + blood.replace(/\n/g, "<br>")  + "</div>";
//		msg +=  "<div>" + messageFooter.val() + "</div>";
		$("#message_preview").html(msg);
		$("#message_preview_input").val(messageHeader.val() + "\n" + blood);
	}
});
</script>
<?php

?>
