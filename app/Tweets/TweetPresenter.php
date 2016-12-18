<?php namespace App\Tweets;

use Laracodes\Presenter\Presenter;
use Twitter_Autolink;

class TweetPresenter extends Presenter {

	public function tweet()
	{

		$this->text = Twitter_Autolink::create()->autolink($this->text);

		if ($this->extra['entities'])
		{
			if (property_exists($this->extra['entities'], 'urls')) {
				foreach($this->extra['entities']->urls as $url)
				{
					$this->text = str_replace($url->url, $url->expanded_url, $this->text);
				}
			}

			if (property_exists($this->extra['entities'], 'media')) {
				foreach( $this->extra['entities']->media as $url)
				{
					$this->text = str_replace($url->url, $url->display_url, $this->text);
				}
			}
		}

		if ($this->type == '1')
			$this->text = '<i class="fa fa-reply"></i> ' . $this->text;
		else if ($this->type == '2')
			$this->text = str_replace('RT ', '<i class="fa fa-retweet"></i> ', $this->text);

		return $this->text;
	}

	public function metadata()
	{
		$url = "https://twitter.com/statuses/".$this->tweetid;
		$metadata = '<a href="'.$url.'">'.$this->time.'</a>';

		if ($this->place)
			$metadata = $metadata . ' from <a href="http://maps.google.com/?q='.urlencode($this->place->full_name)	.'">' . $this->place->full_name . '</a>';

		return $metadata;
	}
}