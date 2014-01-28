<?php
require_once("header.php");

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
<input type="submit"/>
</form>
<?php

?>
