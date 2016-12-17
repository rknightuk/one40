<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Fetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'one40:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch new tweets';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Fetching new tweets...');


    }

	private function importTweets($p){
		global $twitterApi, $db, $config, $access, $search;
		$p = trim($p);
		if(!$twitterApi->validateUserParam($p)){ return false; }
		$maxCount = 200;
		$tweets   = array();
		$sinceID  = 0;
		$maxID    = 0;

//		// Check for authentication
//		if(!isset($config['consumer_key']) || !isset($config['consumer_secret'])){
//			die("Consumer key and secret not found. These are required for authentication to Twitter. \n" .
//				"Please point your browser to the authorize.php file to configure these.\n");
//		}

		list($userparam, $uservalue) = explode('=', $p);

		echo l("Importing:\n");

		// Do we already have tweets?
		$pd = $twitterApi->getUserParam($p);
		if($pd['name'] == "screen_name"){
			$uid        = $twitterApi->getUserId($pd['value']);
			$screenname = $pd['value'];
		} else {
			$uid        = $pd['value'];
			$screenname = $twitterApi->getScreenName($pd['value']);
		}
		$tiQ = $db->query("SELECT `tweetid` FROM `".DTP."tweets` WHERE `userid` = '" . $db->s($uid) . "' ORDER BY `time` DESC LIMIT 1");
		if($db->numRows($tiQ) > 0){
			$ti      = $db->fetch($tiQ);
			$sinceID = $ti['tweetid'];
		}

		echo l("User ID: " . $uid . "\n");

		// Find total number of tweets
		$total = totalTweets($p);
		if(is_numeric($total)){
			if($total > 3200){ $total = 3200; } // Due to current Twitter limitation
			$pages = ceil($total / $maxCount);

			echo l("Total tweets: <strong>" . $total . "</strong>, Approx. page total: <strong>" . $pages . "</strong>\n");
		}

		if($sinceID){
			echo l("Newest tweet I've got: <strong>" . $sinceID . "</strong>\n");
		}

		$page = 1;

		// Retrieve tweets
		do {
			// Announce
			echo l("Retrieving page <strong>#" . $page . "</strong>:\n");
			// Get data
			$params = array(
				$userparam         => $uservalue,
				'include_rts'      => true,
				'include_entities' => true,
				'count'            => $maxCount
			);

			if($sinceID){
				$params['since_id'] = $sinceID;
			}
			if($maxID){
				$params['max_id']   = $maxID;
			}

			$data = $twitterApi->query('statuses/user_timeline', $params);
			// Drop out on connection error
			if(is_array($data) && $data[0] === false){ dieout(l(bad("Error: " . $data[1] . "/" . $data[2]))); }

			// Start parsing
			echo l("<strong>" . ($data ? count($data) : 0) . "</strong> new tweets on this page\n");
			if(!empty($data)){
				echo l("<ul>");
				foreach($data as $i => $tweet){

					// First, let's check if an API error occured
					if(is_array($tweet) && is_object($tweet[0]) && property_exists($tweet[0], 'message')){
						dieout(l(bad('A Twitter API error occured: ' . $tweet[0]->message)));
					}

					// Shield against duplicate tweet from max_id
					if(!IS64BIT && $i == 0 && $maxID == $tweet->id_str){ unset($data[0]); continue; }
					// List tweet
					echo l("<li>" . $tweet->id_str . " " . $tweet->created_at . "</li>\n");
					// Create tweet element and add to list
					$tweets[] = $twitterApi->transformTweet($tweet);
					// Determine new max_id
					$maxID    = $tweet->id_str;
					// Subtracting 1 from max_id to prevent duplicate, but only if we support 64-bit integer handling
					if(IS64BIT){
						$maxID = (int)$tweet->id - 1;
					}
				}
				echo l("</ul>");
			}
			$page++;
		} while(!empty($data));

		if(count($tweets) > 0){
			// Ascending sort, oldest first
			$tweets = array_reverse($tweets);
			echo l("<strong>All tweets collected. Reconnecting to DB...</strong>\n");
			$db->reconnect(); // Sometimes, DB connection times out during tweet loading. This is our counter-action
			echo l("Inserting into DB...\n");
			$error = false;
			foreach($tweets as $tweet){
				$q = $db->query($twitterApi->insertQuery($tweet));
				if(!$q){
					dieout(l(bad("DATABASE ERROR: " . $db->error())));
				}
				$text = $tweet['text'];
				$te   = $tweet['extra'];
				if(is_string($te)){ $te = @unserialize($tweet['extra']); }
				if(is_array($te)){
					// Because retweets might get cut off otherwise
					$text = (array_key_exists("rt", $te) && !empty($te['rt']) && !empty($te['rt']['screenname']) && !empty($te['rt']['text']))
						? "RT @" . $te['rt']['screenname'] . ": " . $te['rt']['text']
						: $tweet['text'];
				}
				$search->index($db->insertID(), $text);
			}
			echo !$error ? l(good("Done!\n")) : "";
		} else {
			echo l(bad("Nothing to insert.\n"));
		}

		// Checking personal favorites -- scanning all
		echo l("\n<strong>Syncing favourites...</strong>\n");
		// Resetting these
		$favs  = array(); $maxID = 0; $sinceID = 0; $page = 1;
		do {
			echo l("Retrieving page <strong>#" . $page . "</strong>:\n");

			$params = array(
				$userparam => $uservalue,
				'count'    => $maxCount
			);

			if($maxID){
				$params['max_id']   = $maxID;
			}

			$data = $twitterApi->query('favorites/list', $params);

			if(is_array($data) && $data[0] === false){ dieout(l(bad("Error: " . $data[1] . "/" . $data[2]))); }
			echo l("<strong>" . ($data ? count($data) : 0) . "</strong> total favorite tweets on this page\n");

			if(!empty($data)){
				echo l("<ul>");
				foreach($data as $i => $tweet){

					// First, let's check if an API error occured
					if(is_array($tweet) && is_object($tweet[0]) && property_exists($tweet[0], 'message')){
						dieout(l(bad('A Twitter API error occured: ' . $tweet[0]->message)));
					}

					if(!IS64BIT && $i == 0 && $maxID == $tweet->id_str){ unset($data[0]); continue; }
					if($tweet->user->id_str == $uid){
						echo l("<li>" . $tweet->id_str . " " . $tweet->created_at . "</li>\n");
						$favs[] = $tweet->id_str;
					}
					$maxID = $tweet->id_str;
					if(IS64BIT){
						$maxID = (int)$tweet->id - 1;
					}
				}
				echo l("</ul>");
			}
			echo l("<strong>" . count($favs) . "</strong> favorite own tweets so far\n");
			$page++;
		} while(!empty($data));

		// Blank all favorites
		$db->query("UPDATE `".DTP."tweets` SET `favorite` = '0'");
		// Insert favorites into DB
		$db->query("UPDATE `".DTP."tweets` SET `favorite` = '1' WHERE `tweetid` IN ('" . implode("', '", $favs) . "')");
		echo l(good("Updated favorites!"));
	}
}
