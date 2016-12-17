<?php

namespace App\Console\Commands;

use App\Archive\Importer;
use App\Archive\LogRepository;
use App\Tweet;
use Illuminate\Console\Command;

class One40Import extends Command
{
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

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'one40:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import your Twitter archive';
	/**
	 * @var Importer
	 */
	private $importer;
	/**
	 * @var LogRepository
	 */
	private $logRepo;

	public function __construct(LogRepository $logRepo, Importer $importer)
    {
        parent::__construct();
	    $this->logRepo = $logRepo;
	    $this->importer = $importer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
	    $this->info('Running importer...');

	    $archiveLog = $this->logRepo->all()->pluck('filename')->toArray();

	    $files = glob(base_path() . '/resources/archive/[0-9][0-9][0-9][0-9]_[0-1][0-9].js');

	    if (! count($files))
	    {
	    	$this->info('No archive files found. Aborting...');
	    	return;
	    }

	    $tweets = [];

	    foreach($files as $filename ) {

	    	$this->info('---');

            if (in_array(basename($filename), $archiveLog)) {
			    $this->info(basename($filename) . ' already imported, skipping...');
			    continue;
		    }

		    $this->info('Found archive file ' . basename($filename));

		    $fileLines = file($filename);
		    array_shift($fileLines); // remove first line
		    $data = json_decode(implode( '', $fileLines));

		    if (! is_array($data)) {
		    	$this->info('Error: Could not parse JSON for ' . basename($filename) . '. Aborting...');
		    	continue;
		    }

		    $this->info(count($data) . ' tweets found');

		    if (! empty($data)) {
			    foreach($data as $i => $tweet) {
				    // Create tweet element and add to list
				    $tweetX = $this->normalizeTweet($tweet);
				    $tweets[] = $this->transformTweet($tweetX);
			    }
			    // Ascending sort, oldest first
			    $tweets = array_reverse($tweets);

			    foreach($tweets as $tweet) {
				    $type = ($tweet['text'][0] == "@") ? 1 : (preg_match("/RT @\w+/", $tweet['text']) ? 2 : 0);

					Tweet::firstOrCreate([
						'userid' => $tweet['userid'],
						'tweetid' => $tweet['tweetid'],
						'type' => $type,
						'time' => $tweet['time'],
						'text' => $this->entityDecode($tweet['text']),
						'source' => $tweet['source'],
						'extra' => serialize($tweet['extra']),
						'coordinates' => serialize($tweet['coordinates']),
						'geo' => serialize($tweet['geo']),
						'place' => serialize($tweet['place']),
						'contributors' => serialize($tweet['contributors'])
					]);
			    }
		    }

		    $tweets = [];
		    $this->logRepo->markImported(basename($filename));
	    }
    }

	private function entityDecode($str){
		return str_replace("&amp;", "&", str_replace("&lt;", "<", str_replace("&gt;", ">", $str)));
	}

    private function normalizeTweet($tweet) {
	    foreach ($tweet as $k => $v) {
		    // replace empty objects with null
		    if (is_object($v) && count( get_object_vars($v)) === 0) {
			    $tweet->$k = null;
		    }
	    }
	    foreach(['geo', 'coordinates', 'place', 'contributors'] as $property ) {
		    if(! property_exists( $tweet, $property ) ) {
			    $tweet->$property = null;
		    }
	    }
	    return $tweet;
    }

	private function transformTweet($tweet) {
		$t = array(); $e = array();
		foreach(get_object_vars($tweet) as $k => $v) {
			if(array_key_exists($k, self::DB_MAP)) {
				$key = self::DB_MAP[$k];
				$val = $v;
				if(in_array($key, array("text", "source", "tweetid", "id", "id_str"))) {
					$val = (string) $v;
				} elseif($key == "time") {
					$val = strtotime($v);
				}
				$t[$key] = $val;
			} elseif($k == "user") {
				$t['userid'] = (string)$v->id_str;
			} elseif($k == "retweeted_status") {
				$rt = array(); $rte = array();
				foreach(get_object_vars($v) as $kk => $vv) {
					if(array_key_exists($kk, self::DB_MAP)) {
						$kkey = self::DB_MAP[$kk];
						$vval = $vv;
						if(in_array($kkey, array("text", "source", "tweetid", "id", "id_str"))) {
							$vval = (string)$vv;
						} elseif($kkey == "time") {
							$vval = strtotime($vv);
						}
						$rt[$kkey] = $vval;
					} elseif($kk == "user") {
						$rt['userid']     = (string)$vv->id_str;
						$rt['screenname'] = (string)$vv->screen_name;
					} else {
						$rte[$kk] = $vv;
					}
				}
				$rt['extra'] = $rte;
				$e['rt']     = $rt;
			} else {
				$e[$k] = $v;
			}
		}
		$t['extra'] = $e;
		$tt = $this->enhanceTweet($t);
		if(!empty($tt) && is_array($tt) && $tt['text']) {
			$t = $tt;
		}
		return $t;
	}

	public function enhanceTweet($tweet) {
		// Finding entities
		$tweetextra = array();
		if(!empty($tweet['extra'])) {
			if(is_array($tweet['extra'])) {
				$tweetextra = $tweet['extra'];
			} else {
				@$tweetextra = unserialize($tweet['extra']);
			}
		}
		$rt = (array_key_exists("rt", $tweetextra) && !empty($tweetextra['rt']));
		$entities = $rt ? $tweetextra['rt']['extra']['entities'] : $tweetextra['entities'];

		// Let's go
		$imgs    = array();
		$text    = $rt ? $tweetextra['rt']['text'] : $tweet['text'];
		$mtext   = $this->mediaLinkTweetText($text, $entities);
		$links   = $this->findURLs($mtext); // Two link lists because media links might be different from public URLs
		$flinks  = $this->findURLs($text);

		if(! empty($links) && ! empty($flinks)) { // connection between the two
			$linkmap = array_combine(array_keys($links), array_keys($flinks));
		}

		foreach($links as $link => $l) {
			if(is_array($l) && array_key_exists("host", $l) && array_key_exists("path", $l)) {
				$domain = $this->domain($l['host']);
				$imgid  = $this->imgid($l['path']);
				if($imgid) {
					if($domain == "twimg.com") {
						$displaylink = $linkmap ? $linkmap[$link] : $link;
						$imgs[$displaylink] = "//pbs.twimg.com" . $l['path'] . ":thumb";
					}
					if($domain == "twitpic.com") {
						$imgs[$link] = "//twitpic.com/show/thumb/" . $imgid;
					}
					if($domain == "imgur.com") {
						$imgs[$link] = "//i.imgur.com/" . $imgid . "s.jpg";
					}
					if($domain == "moby.to") {
						$imgs[$link] = "http://moby.to/" . $imgid . ":square";
					}
					if($domain == "instagr.am" || $domain == "instagram.com") {
						$html = (string) $this->getURL($link);
						preg_match('/<meta property="og:image" content="([^"]+)"\s*\/>/i', $html, $matches);
						if(isset($matches[1])) {
							$imgs[$link] = $matches[1];
						}
					}
				}
			}
		}

		if(count($imgs) > 0) $tweet['extra']['imgs'] = $imgs;

		return $tweet;
	}

	// Replace t.co links with full links, for internal use
	private function fullLinkTweetText($text, $entities, $mediaUrl = false) {
		if(!$entities) { return $text; }
		$sources = property_exists($entities, 'media') ? array_merge($entities->urls, $entities->media) : $entities->urls;
		$replacements = array();
		foreach($sources as $entity) {
			if(property_exists($entity, 'expanded_url')) {
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
		$urls = array();
		preg_match_all("/\b(((https*:\/\/)|www\.).+?)(([!?,.\"\)]+)?(\s|$))/", $str, $m);
		foreach($m[1] as $url) {
			$u = ($url[0] == "w") ? "//" . $url : $url;
			$urls[$u] = parse_url($u);
		}
		return $urls;
	}

	private function domain($host) {
		if(empty($host) || !is_string($host)) { return false; }
		if(preg_match("/^[0-9\.]+$/", $host)) { return $host; } // IP
		if(substr_count($host, ".") <= 1) {
			return $host;
		} else {
			$h = explode(".", $host, 2);
			return $h[1];
		}
	}

	private function imgid($path) {
		$m = array();
		preg_match("@/([a-z0-9]+).*@i", $path, $m);
		if(count($m) > 0) {
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
		if(is_array($auth) && count($auth) == 2) {
			$o[CURLOPT_USERPWD] = $auth[0] . ":" . $auth[1];
		}
		curl_setopt_array($conn, $o);
		$file = curl_exec($conn);
		if(!curl_errno($conn)) {
			curl_close($conn);
			return $file;
		} else {
			$a = array(false, curl_errno($conn), curl_error($conn));
			curl_close($conn);
			return $a;
		}
	}
}
