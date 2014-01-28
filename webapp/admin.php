<?php
require_once("header.php");

$bank_id=1;
$latest=get_latest(1);
if(@$_POST["action"]=="update_blood"){
	foreach($blood_type as $type=>$data){
		$status=@$_POST["status_".$type];
		if($status){
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
</style>

<h1>Update Levels</h1>
<form method="post">
<input type="hidden" name="action" value="update_blood"/>
<?php foreach($blood_type as $type=>$data): ?>
<label><?=$type;?></label>
<select name="status_<?=$type;?>">
<?php foreach($status_level as $i=>$n): ?>
<option <?=($i==$latest[$type]["status"]) ? "selected" : "" ; ?> value="<?=$i;?>"><?=$n;?></option>
<?php endforeach; ?>
</select><br/>
<?php endforeach; ?>
<input type="submit"/>
</form>
<?php

?>
