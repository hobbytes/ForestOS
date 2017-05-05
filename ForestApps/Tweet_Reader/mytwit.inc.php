<?php
/**
 * MyTwit PHP Twitter Feed with Oauth
 * ==============================
 *
 * License: MIT-style license http://opensource.org/licenses/MIT
 * Author: Ralph Slooten (www.axllent.org)
 */

class MyTwit {

	public $TwitterUser = false;
	public $TWITTER_CONSUMER_KEY = false;
	public $TWITTER_CONSUMER_SECRET = false;
	public $TWITTER_OAUTH_ACCESS_TOKEN = false;
	public $TWITTER_OAUTH_ACCESS_TOKEN_SECRET = false;

	public $CacheExpire = 600; // Seconds
	public $PostLimit = 10;
	public $ExcludeReplies = false; // include replies to other users?
	public $TmpDir = false; // defaults to
	public $OpenLinksInBlank = false;


	public function UpdateCache() {

		$this->ErrorMessage = false;

		if(
			!$this->TWITTER_CONSUMER_KEY ||
			!$this->TWITTER_CONSUMER_SECRET ||
			!$this->TWITTER_OAUTH_ACCESS_TOKEN ||
			!$this->TWITTER_OAUTH_ACCESS_TOKEN_SECRET ||
			!$this->TwitterUser || !$this->PostLimit
		) return $this->ErrorMessage = 'Client is not configured properly.';

		$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

		$fetchPosts = ($this->PostLimit > 200) ? 200 : $this->PostLimit;
		if($this->ExcludeReplies) {
			$this->ExcludeReplies = 1;
			$fetchPosts = 200;
		} else {
			$this->ExcludeReplies = 0;
		}

		$oauth = array(
			'screen_name' => $this->TwitterUser,
			'count' => $fetchPosts,
			'exclude_replies' => $this->ExcludeReplies,
			'oauth_consumer_key' => $this->TWITTER_CONSUMER_KEY,
			'oauth_nonce' => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_token' => $this->TWITTER_OAUTH_ACCESS_TOKEN,
			'oauth_timestamp' => time(),
			'oauth_version' => '1.0');

		$base_info = $this->buildBaseString($url, 'GET', $oauth);
		$composite_key = rawurlencode($this->TWITTER_CONSUMER_SECRET) . '&' . rawurlencode($this->TWITTER_OAUTH_ACCESS_TOKEN_SECRET);
		$oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
		$oauth['oauth_signature'] = $oauth_signature;

		$header = array($this->buildAuthorizationHeader($oauth), 'Expect:');

		$getURL = $url.'?screen_name='.rawurlencode($this->TwitterUser).
			'&count='.rawurlencode($fetchPosts).
			'&exclude_replies='.rawurlencode($this->ExcludeReplies);

		$options = array( CURLOPT_HTTPHEADER => $header,
			CURLOPT_HEADER => false,
			CURLOPT_URL => $getURL,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false);

		if(!$this->TmpDir)
			$this->TmpDir = sys_get_temp_dir();
		$this->TmpDir = rtrim($this->TmpDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

		$cacheFile = $this->TmpDir.'mytwit_'.md5(__FILE__.$getURL.$composite_key).'.json';

		if(!is_writable($this->TmpDir))
			return $this->ErrorMessage = 'Cannot write cache to '.$this->TmpDir;

		if(!is_file($cacheFile) || filemtime($cacheFile) < (time() - $this->CacheExpire)) {
			// Update Cache file
			$twitter = curl_init();
			curl_setopt_array($twitter, $options);
			$json = curl_exec($twitter);
			curl_close($twitter);
			file_put_contents($cacheFile, $json);
		}

		$this->TwitterResponse = json_decode(file_get_contents($cacheFile), true);

		if(!is_array($this->TwitterResponse))
			return $this->ErrorMessage = 'Unexpected response from server: ' . $conn->getBody();

		if(isset($this->TwitterResponse['errors'])) {
			$this->ErrorMessage = false;
			foreach ($this->TwitterResponse['errors'] as $error)
				$this->ErrorMessage .= $error['message'] . ' (code: ' . $error['code'] . ')';
			return $this->ErrorMessage;
		}

		/* Convert the raw array into a flat ArrayList() */
		$this->Tweets = array_slice($this->returnFlatArray($this->TwitterResponse), 0, $this->PostLimit);
		if(count($this->Tweets) == 0)
			return $this->ErrorMessage = 'There are no tweets for '.$this->TwitterUser;

		for($x = 0; $x < count($this->Tweets); $x++) {
			$this->Tweets[$x]['MyTimeAgo'] = $this->intoRelativeTime($this->Tweets[$x]['created_at']);
			$this->Tweets[$x]['MyText'] = $this->linkURLs($this->Tweets[$x]['text']);
		}

		$this->UserInfo = $this->Tweets[0];
	}

	/* Returns a flat array of a multidimensional arrays
	*  @param array $value
	*  @return array
	*/
	/* Returns a flat array of a multidimensional arrays
	*  @param array $value
	*  @return array
	*/
	protected function returnFlatArray($jsonArray) {
		$output = array();
		foreach ($jsonArray as $rawarr) {
			$data = $this->getRecurseVals($rawarr, $arr);
			array_push($output, $data);
		}
		return $output;
	}

	/* Recursively return all array keys & values
	*  appending the parent key to the child key
	*  @param $input_array, $output_array, $parent_id
	*  @return flat array
	*/
	protected function getRecurseVals($arr, &$data, $parent="") {
		foreach ($arr as $key => $value) {
			$k = ($parent == "") ? (string)$key : $parent . "_" . (string)$key;
			if(is_array($value))
				$data = $this->getRecurseVals($value, $data, $k);
			else
				$data[$k] = $value;
		}
		return $data;
	}

	/* Creates links from the tweets
	*  @param str $raw_text
	*  @return str $html_encoded with links
	*/
	public function linkURLs($text) {
		$targetAppend = ($this->OpenLinksInBlank) ? ' target="_blank"' : false;
		$in=array( '`((?:https?|ftp)://\S+[[:alnum:]]/?)`si', '`((?<!//)(www\.\S+[[:alnum:]]/?))`si' );
		$out=array( '<a href="$1"'.$targetAppend.'>$1</a> ', '<a href="http://$1" rel="nofollow">$1</a>' );
		$text = preg_replace($in,$out,$text);
		$text = preg_replace('/@([a-zA-Z0-9-_]+)/','@<a href="http://twitter.com/$1" rel="nofollow">$1</a>',$text);
		return $text;
	}

	/* Translates posted times into relative time
	*  @param str $raw_datetime
	*  @return str $relative_time ago
	*/
	protected function intoRelativeTime($created_at) {
		$seconds = time() - strtotime($created_at);
		if (($seconds / 60 / 60 / 24) > 1) return $this->formatPlural(round($seconds / 60 / 60 / 24), 'day').' ago';
		elseif (($seconds / 60 / 60) > 1) return 'about '.$this->formatPlural(round($seconds / 60 / 60), 'hour').' ago';
		else if (($seconds / 60 ) > 1) return 'about '.$this->formatPlural(round($seconds / 60), 'minute').' ago';
		else return 'about '.round($seconds).' seconds ago';
	}

	/* Appends an "s" if value > 1
	*  @param int $quantity, str (minute,hour,day)
	*  @return str $formatted_value
	*/
	protected function formatPlural($val, $qty) {
		if ($val > 1) return $val.' '.$qty.'s';
		else return $val.' '.$qty;
	}

	/* Generate the Authorization header string required for OAUTH
	*  @param array $oauth values
	*  @return str
	*/
	protected function buildAuthorizationHeader($oauth) {
		$r = 'Authorization: OAuth ';
		$values = array();
		foreach($oauth as $key=>$value)
			$values[] = "$key=\"" . rawurlencode($value) . "\"";
		$r .= implode(', ', $values);
		return $r;
	}

	/* Returns an encoded base string for OAUTH
	*  @param str $uri, str $method (GET/POST), array $oauth values
	*  @return str
	*/
	protected function buildBaseString($baseURI, $method, $params) {
		$r = array();
		ksort($params);
		foreach($params as $key=>$value)
			$r[] = "$key=" . rawurlencode($value);
		return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r)); //return complete base string
	}

}
