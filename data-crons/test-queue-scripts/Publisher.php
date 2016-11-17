<?php
namespace Queue;
require '/var/www/composer/vendor/autoload.php';

use Pheanstalk\Pheanstalk;

class Publisher
{
	private $queue;

	public function __construct(array $args)
	{
		$this->config = ['queue' => ['host' => 'localhost']]; // Don't mind this. I typically have a config file, but I moved it here for purposes of this tutorial. Use IP or Domain for the host.

		$this->queue = $args['queue']; // Just a name for the queue. I pass this as a parameter. Beanstalkd calls the queues "tubes" but this really is just how you want to call it. If it doesn't exist, it gets created.
		$this->client = new Pheanstalk($this->config['queue']['host']); // Instantiate an object.

	}

	public function send($request)
	{
		return $this->client
		->useTube($this->queue)
		->put(json_encode($request)); // Send anything you want, json encoded and that's it!
	}
}

