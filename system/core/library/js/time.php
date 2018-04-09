<?
$timezone = $_SESSION['timezone'];
date_default_timezone_set("$timezone");
$h = date('H');
$m = date('i');
$s = date('s');
?>
<script>
function showTime()
{
  var dat = new Date();
  var H = <?echo $h?>;
  H = H.length<2 ? '0' + H:H;
  var M = <?echo $m?>;
  M = M.length<2 ? '0' + M:M;
  var S = <?echo $s?>;
  S =S.length<2 ? '0' + S:S;
  var clock = H + ':' + M;
  document
    .getElementById('time')
      .innerHTML=clock;
  setTimeout(showTime,1000); // перерисовать 1 раз в сек.
}


function showFullTime()
{
  var date = new Date();
  var H = <?echo $h?>;
  H = H.length<2 ? '0' + H:H;
  var M = <?echo $m?>;
  M = M.length<2 ? '0' + M:M;
  var S = <?echo $s?>;
  S =S.length<2 ? '0' + S:S;
  var clock = H + ':' + M + ':' + S;
  $("#fulltime").text(clock);
  setTimeout(showFullTime,1000); // перерисовать 1 раз в сек.
}
showFullTime();
</script>
