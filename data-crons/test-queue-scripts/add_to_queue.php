<?php
// filename: add_to_queue.php
namespace Queue;
 
//creating a queue requires we come up with an arbitrary number
define('QUEUE', 21671);

include_once "Publisher.php";

$publish = new Publisher(['queue' =>QUEUE]);
$publish->send("another job queue"); // This could be an array, a class name, etc.


