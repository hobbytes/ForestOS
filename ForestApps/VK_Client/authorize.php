<?
$redirect=$_GET['redirect'];
$access_token=$_GET['access_token'];
$user_id=$_GET['user_id'];
echo $access_token.$user_id;

?>
<script>
var after=location.href.match(/#.*/);
</script>
<?$string='<script>document.writeln(after)</script>'; echo $string;?>
