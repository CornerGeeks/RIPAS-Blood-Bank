<?php
require_once("header.php");
require_once('config.php');
require_once("PostToFacebook.php");
require_once("PostToTwitter.php");
session_start();

$bank_id=1;
$latest=get_latest(1);
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
$posting['twitter'] = new PostToTwitter($config);
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
</style>

<h1>Update Levels</h1>
<form method="post">
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

<div>
	<label for="message_footer" class="block">Message footer:</label>
	<textarea id="message_footer" name="message_footer" class="updatePreview"></textarea>
</div>
	<div>
	Post to
	<?php foreach($posting as $id=>$postingObj) : ?>
		<div>
			<?php if($postingObj->hasAccess()) : ?>
			<input type="checkbox" name="post_to" id="post_to_<?php echo $id ?>" value="<?php echo $id ?>" /><label class="inline" for="post_to_<?php echo $id ?>"><?php echo $postingObj->getName() ?></label>
			<?php echo $postingObj->toString(); ?>
			<?php else : ?>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>



<!-- 	<div>
		<?php if($posting['twitter']->haveAccess()) : ?>
		<input type="checkbox" name="post_to" id="post_to_twitter" value="twitter" /><label class="inline" for="post_to_twitter">Twitter</label>
		<?php else : ?>
		<?php endif; ?>
	</div>
 -->	<div>
		<input type="checkbox" name="post_to" id="post_to_instagram" value="linked" /><label class="inline" for="post_to_instagram">Instagram</label>
	</div>
</div>
<input type="submit"/>
</form>
<div>
	<strong>Preview of message:</strong>
	<div id="message_preview">
	</div>
</div>
<script src="js/jquery-1.10.2.min.js"></script>
<script>
$(document).ready(function(){
	var form = $("#form_blood");
	var messageHeader = $("#message_header");
	var messageFooter = $("#message_footer");

	$(".updatePreview").on('keyup', function(){ updatePreview(); });
	$(".updatePreview").on('change', function(){ updatePreview(); });
	function updatePreview(){

		var msg = "";
		var blood = "";
		form.find(":checked").each(function(){
			var obj = jQuery(this);
			var blood_type = obj.attr("name").replace("blood_", "").toUpperCase();
			var status = obj.val();
			blood += "<li>" + blood_type + ": " + status + "</li>";

		});
		msg +=  "<div>" + messageHeader.val()  + "</div>";
		msg +=  "<ul>" + blood  + "</ul>";
		msg +=  "<div>" + messageFooter.val() + "</div>";
		$("#message_preview").html(msg);
	}
});
</script>
<?php

?>
