<?php
namespace Queue;
require '/var/www/composer/vendor/autoload.php';

use Pheanstalk\Pheanstalk;

class Consumer
{

	private $queue;

	public function __construct(array $args)
	{
		$this->config = ['queue' => ['host' => 'localhost']]; // Don't mind this. I typically have a config file, but I moved it here for purposes of this tutorial. Use IP or Domain for the host.

		$this->queue = $args['queue'];
		$this->client = new Pheanstalk($this->config['queue']['host']);
	}

	public function listen()
	{
		$this->client->watch($this->queue); // Pass the name of the queue again.

		while ($job = $this->client->reserve()) { // Do this forever... so it's always listening.
			$message = json_decode($job->getData(), true); // Decode the message

			$status = $this->process($message);

			if($status)
				$this->client->delete($job);
			else
				$this->client->delete($job); // Delete anyway. You could burry it, meaning it gets re-tried later.

		}
	}

	public function process($msg)
	{
		// Do some operation and return true if success or false
		file_put_contents(str_replace(' ','-',$msg).'.txt', "Message pulled from queue - name:{$msg} \n");
	}
}
