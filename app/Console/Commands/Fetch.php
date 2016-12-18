<?php

namespace App\Console\Commands;

use App\Formatter;
use App\Tweets\Api;
use App\Tweets\Tweet;
use App\Tweets\TweetRepository;
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
	 * @var TweetRepository
	 */
	private $tweets;
	/**
	 * @var Api
	 */
	private $api;

	/**
	 * Create a new command instance.
	 *
	 * @param Formatter $formatter
	 * @param TweetRepository $tweets
	 */
    public function __construct(Formatter $formatter, TweetRepository $tweets, Api $api)
    {
        parent::__construct();
	    $this->formatter = $formatter;
	    $this->tweets = $tweets;
	    $this->api = $api;
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
		$tweets   = [];
		$sinceID  = $this->tweets->getLatestId();
		$maxID    = 0;

		$screename = 'rmlewisuk';

		$page = 1;

		do {
			$data = $this->api->getUserTimeline($screename, $sinceID, $maxID);

			if (is_array($data) && isset($data[0]) && $data[0] === false) {
				$this->error('ERROR!!');
				$data = null;
				break;
			}

			if (! empty($data)) {
				foreach ($data as $i => $tweet) {

					if (is_array($tweet) && is_object($tweet[0]) && property_exists($tweet[0], 'message')) {
						$this->error('Error: ' . $tweet[0]->message);
					}

					$tweets[] = $this->formatter->transformTweet($tweet);

					$maxID = $tweet->id_str;

					// Subtracting 1 from max_id to prevent duplicate, but only if we support 64-bit integer handling
					if ((int) "9223372036854775807" > 2147483647) $maxID = (int) $tweet->id - 1;
				}
			}
			$page++;
		} while (! empty($data));

		if (! count($tweets)) {
			$this->info('No new tweets found');
			return;
		}

		$this->info(count($tweets) . ' new tweets found');

		// Ascending sort, oldest first
		$tweets = array_reverse($tweets);

		foreach ($tweets as $tweet) {
			$this->info('Importing tweet ' . $tweet['tweetid']);

			$this->tweets->create($tweet);
		}
	}
}
