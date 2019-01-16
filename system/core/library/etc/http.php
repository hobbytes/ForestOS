<?

class http{

  function makeNewRequest($url, $agent, $data){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => $agent,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTREDIR => 3,
        CURLOPT_POSTFIELDS => $data
    ));
    $resp = curl_exec($curl);
    curl_close($curl);
    return $resp;
  }

}

?>
