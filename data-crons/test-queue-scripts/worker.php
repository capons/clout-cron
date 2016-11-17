<?php
// filename: worker.php

namespace Queue;
 
//getting our queue requires we supply the id number we created it with
define('QUEUE', 21671);
 
include_once "Consumer.php";
$consumer = new Consumer(['queue' => QUEUE]);
$consumer->listen();


