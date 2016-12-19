<?php

namespace App\Tweets;

class Formatter {

	const DB_MAP = array(
		"id_str"       => "tweetid",
		"created_at"   => "time",
		"text"         => "text",
		"source"       => "source",
		"coordinates"  => "coordinates",
		"geo"          => "geo",
		"place"        => "place",
		"contributors" => "contributors",
		"user.id"      => "userid"
	);

	public function transformTweet($tweet) {
		$formattedTweet = [];
		$extra = [];

		foreach(get_object_vars($tweet) as $key => $tweetValue) {
			if (array_key_exists($key, self::DB_MAP)) {
				$formattedTweet = $this->formatEntities($key, $tweetValue, $formattedTweet);
			} elseif ($key == 'user') {
				$formattedTweet['userid'] = (string) $tweetValue->id_str;
			} elseif ($key == 'retweeted_status') {
				$retweet = [];
				$retweetExtra = [];
				foreach(get_object_vars($tweetValue) as $kk => $vv) {
					if (array_key_exists($kk, self::DB_MAP)) {
						$kkey = self::DB_MAP[$kk];
						$vval = $vv;
						if (in_array($kkey, ['text', 'source', 'tweetid', 'id', 'id_str'])) {
							$vval = (string) $vv;
						} elseif ($kkey == "time") {
							$vval = strtotime($vv);
						}
						$retweet[$kkey] = $vval;
					} elseif ($kk == "user") {
						$retweet['userid']     = (string) $vv->id_str;
						$retweet['screenname'] = (string) $vv->screen_name;
					} else {
						$retweetExtra[$kk] = $vv;
					}
				}
				$retweet['extra'] = $retweetExtra;
				$extra['rt']     = $retweet;
			} else {
				$extra[$key] = $tweetValue;
			}
		}
		$formattedTweet['extra'] = $extra;
		$tt = $this->enhanceTweet($formattedTweet);
		if (!empty($tt) && is_array($tt) && $tt['text']) {
			$formattedTweet = $tt;
		}

		$type = ($formattedTweet['text'][0] == "@") ? 1 : (preg_match("/RT @\w+/", $formattedTweet['text']) ? 2 : 0);

		return [
			'userid' => $formattedTweet['userid'],
			'tweetid' => $formattedTweet['tweetid'],
			'type' => $type,
			'time' => $formattedTweet['time'],
			'text' => $this->entityDecode($formattedTweet['text']),
			'source' => $formattedTweet['source'],
			'extra' => serialize($formattedTweet['extra']),
			'coordinates' => serialize($formattedTweet['coordinates']),
			'geo' => serialize($formattedTweet['geo']),
			'place' => serialize($formattedTweet['place']),
			'contributors' => serialize($formattedTweet['contributors'])
		];
	}

	public function enhanceTweet($tweet) {
		// Finding entities
		$tweetextra = [];
		if (!empty($tweet['extra'])) {
			if (is_array($tweet['extra'])) {
				$tweetextra = $tweet['extra'];
			} else {
				@$tweetextra = unserialize($tweet['extra']);
			}
		}
		$rt = (array_key_exists("rt", $tweetextra) && !empty($tweetextra['rt']));
		$entities = $rt ? $tweetextra['rt']['extra']['entities'] : $tweetextra['entities'];

		$imgs    = [];
		$text    = $rt ? $tweetextra['rt']['text'] : $tweet['text'];
		$mtext   = $this->mediaLinkTweetText($text, $entities);
		$links   = $this->findURLs($mtext); // Two link lists because media links might be different from public URLs
		$flinks  = $this->findURLs($text);

		if (! empty($links) && ! empty($flinks)) { // connection between the two
			$linkmap = array_combine(array_keys($links), array_keys($flinks));
		}

		foreach($links as $link => $l) {
			if (is_array($l) && array_key_exists("host", $l) && array_key_exists("path", $l)) {
				$domain = $this->domain($l['host']);
				$imgid  = $this->imgid($l['path']);
				if ($imgid) {
					if ($domain == "twimg.com") {
						$displaylink = $linkmap ? $linkmap[$link] : $link;
						$imgs[$displaylink] = "//pbs.twimg.com" . $l['path'] . ":thumb";
					}
					if ($domain == "twitpic.com") {
						$imgs[$link] = "//twitpic.com/show/thumb/" . $imgid;
					}
					if ($domain == "imgur.com") {
						$imgs[$link] = "//i.imgur.com/" . $imgid . "s.jpg";
					}
					if ($domain == "moby.to") {
						$imgs[$link] = "http://moby.to/" . $imgid . ":square";
					}
					if ($domain == "instagr.am" || $domain == "instagram.com") {
						$html = (string) $this->getURL($link);
						preg_match('/<meta property="og:image" content="([^"]+)"\s*\/>/i', $html, $matches);
						if (isset($matches[1])) {
							$imgs[$link] = $matches[1];
						}
					}
				}
			}
		}

		if (count($imgs) > 0) $tweet['extra']['imgs'] = $imgs;

		return $tweet;
	}

	// Replace t.co links with full links, for internal use
	private function fullLinkTweetText($text, $entities, $mediaUrl = false) {
		if (!$entities) { return $text; }
		$sources = property_exists($entities, 'media') ? array_merge($entities->urls, $entities->media) : $entities->urls;
		$replacements = [];
		foreach($sources as $entity) {
			if (property_exists($entity, 'expanded_url')) {
				$replacements[$entity->indices[0]] = array(
					'end'     => $entity->indices[1],
					'content' => $mediaUrl && property_exists($entity, 'media_url_https') ? $entity->media_url_https : $entity->expanded_url
				);
			}
		}
		$out = '';
		$lastEntityEnded = 0;
		ksort($replacements);
		foreach($replacements as $position => $replacement) {
			$out .= mb_substr($text, $lastEntityEnded, $position - $lastEntityEnded);
			$out .= $replacement['content'];
			$lastEntityEnded = $replacement['end'];
		}
		$out .= mb_substr($text, $lastEntityEnded);
		return $out;
	}

	// Same as above, but prefer media urls
	private function mediaLinkTweetText($text, $entities) {
		return $this->fullLinkTweetText($text, $entities, true);
	}

	private function findURLs($str) {
		$urls = [];
		preg_match_all("/\b(((https*:\/\/)|www\.).+?)(([!?,.\"\)]+)?(\s|$))/", $str, $m);
		foreach($m[1] as $url) {
			$u = ($url[0] == "w") ? "//" . $url : $url;
			$urls[$u] = parse_url($u);
		}
		return $urls;
	}

	private function domain($host) {
		if (empty($host) || !is_string($host)) { return false; }
		if (preg_match("/^[0-9\.]+$/", $host)) { return $host; } // IP
		if (substr_count($host, ".") <= 1) {
			return $host;
		} else {
			$h = explode(".", $host, 2);
			return $h[1];
		}
	}

	private function imgid($path) {
		$m = [];
		preg_match("@/([a-z0-9]+).*@i", $path, $m);
		if (count($m) > 0) {
			return $m[1];
		}
		return false;
	}

	function getURL($url, $auth = NULL) {
		// HTTP grabbin' cURL options, also exsecror
		$httpOptions = array(
			CURLOPT_FORBID_REUSE   => true,
			CURLOPT_POST           => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_USERAGENT      => "Mozilla/5.0 (Compatible; libCURL)",
			CURLOPT_VERBOSE        => false,
			CURLOPT_SSL_VERIFYPEER => false // Insecurity?
		);
		$conn = curl_init($url);
		$o    = $httpOptions;
		if (is_array($auth) && count($auth) == 2) {
			$o[CURLOPT_USERPWD] = $auth[0] . ":" . $auth[1];
		}
		curl_setopt_array($conn, $o);
		$file = curl_exec($conn);
		if (!curl_errno($conn)) {
			curl_close($conn);
			return $file;
		} else {
			$a = array(false, curl_errno($conn), curl_error($conn));
			curl_close($conn);
			return $a;
		}
	}

	public function entityDecode($str){
		return str_replace("&amp;", "&", str_replace("&lt;", "<", str_replace("&gt;", ">", $str)));
	}

	/**
	 * @param $key
	 * @param $tweetValue
	 * @param $formattedTweet
	 * @return array
	 */
	private function formatEntities($key, $tweetValue, $formattedTweet): array
	{
		$key = self::DB_MAP[$key];
		$val = $tweetValue;
		if (in_array($key, ['text', 'source', 'tweetid', 'id', 'id_str'])) {
			$val = (string)$tweetValue;
		} elseif ($key == 'time') {
			$val = strtotime($tweetValue);
		}
		$formattedTweet[$key] = $val;
		return $formattedTweet;
	}

}