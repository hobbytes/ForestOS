function showTime()
{
  var dat = new Date();
  var H = '' + dat.getHours();
  H = H.length<2 ? '0' + H:H;
  var M = '' + dat.getMinutes();
  M = M.length<2 ? '0' + M:M;
  var S = '' + dat.getSeconds();
  S =S.length<2 ? '0' + S:S;
  var clock = H + ':' + M;
  document
    .getElementById('time')
      .innerHTML=clock;
  setTimeout(showTime,1000); // перерисовать 1 раз в сек.
}
