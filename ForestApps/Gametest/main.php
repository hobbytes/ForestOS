<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" style="background-color:#ebebeb; height:300px; width:400px; color:#000; max-height:95%; max-width:100%; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*Twitter Reader*/
//Инициализируем переменные
$click=$_GET['mobile'];
$folder=$_GET['destination'];
//Логика
?>
<canvas id="canvas" width="100%" height="100%">
               <p>Your browser doesn't support HTML5 canvas.</p>
</canvas>
</div>
<script>
function finduser<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?tweetuser="+$("#tweetuser<?echo $appid;?>").val()+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")};
</script>

<?
unset($appid);
?>
