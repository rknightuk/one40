<?php

namespace App\Console\Commands;

use App\Formatter;
use App\Tweet;
use Illuminate\Console\Command;
use Thujohn\Twitter\Facades\Twitter;

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
	 * @var Formatter
	 */
	private $formatter;

	/**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Formatter $formatter)
    {
        parent::__construct();
	    $this->formatter = $formatter;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Fetching new tweets...');

        $this->importTweets();

    }

	private function importTweets()
	{
		$maxCount = 200;
		$tweets   = array();
		$sinceID  = 0;
		$maxID    = 0;

		$screename = 'rmlewisuk';

		$latestTweet = Tweet::orderBy('tweetid', 'desc')->first();

		if ($latestTweet) $sinceID = $latestTweet->id;

		$params = [
			'screen_name' => 'rmlewisuk',
			'since_id' => $sinceID,
			'count' => 200,
			'format' => 'json'
		];

		$data = Twitter::getUsers($params);

		if(is_array($data) && $data[0] === false){
			$this->error('Unable to fetch user. Check your Twitter credentials in .env');
		}
		$total = json_decode($data)->statuses_count;

		if (is_numeric($total)){
			if ($total > 3200) { $total = 3200; } // Twitter limit
			$pages = ceil($total / $maxCount);

			$this->info("Total tweets: " . $total . ". Page total: " . $pages);
		}

		$page = 1;

		// Retrieve tweets
		do {
			// Get data
			$params = array(
				'screen_name'      => $screename,
				'include_rts'      => true,
				'include_entities' => true,
				'count'            => $maxCount
			);

			if($sinceID){
				$params['since_id'] = $sinceID;
				$params['since_id'] = '810223512283652097';
			}
			if($maxID){
				$params['max_id']   = $maxID;
			}

			$data = Twitter::getUserTimeline($params);

			// Drop out on connection error
			if (is_array($data) && isset($data[0]) && $data[0] === false) {
				$this->error('ERROR!!');
				$data = null;
				break;
			}

			if (! empty($data)){
				foreach($data as $i => $tweet){

					// First, let's check if an API error occured
					if(is_array($tweet) && is_object($tweet[0]) && property_exists($tweet[0], 'message')){
						$this->error('An error occured');
					}

					// Create tweet element and add to list
					$tweets[] = $this->formatter->transformTweet($tweet);

					// Determine new max_id
					$maxID = $tweet->id_str;

					// Subtracting 1 from max_id to prevent duplicate, but only if we support 64-bit integer handling
					if ((int)"9223372036854775807" > 2147483647) $maxID = (int) $tweet->id - 1;
				}
			}
			$page++;
		} while (! empty($data));

		$this->info(count($tweets) . ' new tweets found');

		if (count($tweets) > 0){
			// Ascending sort, oldest first
			$tweets = array_reverse($tweets);

			foreach($tweets as $tweet){
				$this->info('Importing tweet...');
				$type = ($tweet['text'][0] == "@") ? 1 : (preg_match("/RT @\w+/", $tweet['text']) ? 2 : 0);

				Tweet::firstOrCreate([
					'userid' => $tweet['userid'],
					'tweetid' => $tweet['tweetid'],
					'type' => $type,
					'time' => $tweet['time'],
					'text' => $this->formatter->entityDecode($tweet['text']),
					'source' => $tweet['source'],
					'extra' => serialize($tweet['extra']),
					'coordinates' => serialize($tweet['coordinates']),
					'geo' => serialize($tweet['geo']),
					'place' => serialize($tweet['place']),
					'contributors' => serialize($tweet['contributors'])
				]);
			}
		} else {
			$this->info('No new tweets found');
		}
	}
}
