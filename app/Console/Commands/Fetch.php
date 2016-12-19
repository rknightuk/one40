<?php

namespace App\Console\Commands;

use App\Fetcher\FetchLog;
use App\Tweets\Formatter;
use App\Tweets\Api;
use App\Tweets\TweetRepository;
use App\User;
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
		$sinceId  = $this->tweets->getLatestId();
		$maxId    = 0;

		if (! $user = User::first()) {
			$this->error('No user found, run one40:setup');
			return;
		}

		$screename = env('TWITTER_USERNAME');

		$page = 1;

		do {
			try {
				$data = $this->api->getUserTimeline($screename, $sinceId, $maxId);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
				break;
			}

			if (empty($data)) continue;

			foreach ($data as $i => $tweet) {

				if (is_array($tweet) && is_object($tweet[0]) && property_exists($tweet[0], 'message')) {
					$this->error('Error: ' . $tweet[0]->message);
				}

				$tweets[] = $this->formatter->transformTweet($tweet);

				$maxId = $tweet->id_str;

				// Subtracting 1 from max_id to prevent duplicate, but only if we support 64-bit integer handling
				if ((int) "9223372036854775807" > 2147483647) $maxId = (int) $tweet->id - 1;
			}
			$page++;
		} while (! empty($data));

		$tweetCount = count($tweets);

		if (! $tweetCount) {
			$this->info('No new tweets found');
			$this->log($tweetCount);
			return;
		}

		$this->info($tweetCount . ' new tweets found');

		// Ascending sort, oldest first
		$tweets = array_reverse($tweets);

		$this->tweets->addTweets($tweets);

		$this->log($tweetCount);
	}

	/**
	 * @param $tweetCount
	 */
	private function log($tweetCount)
	{
		FetchLog::create(['count' => $tweetCount]);
	}
}
