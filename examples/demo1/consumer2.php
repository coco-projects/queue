<?php

    require "./common.php";

    /*********************************/
    //消费

    $queue1 = $manager->initQueue('type');
    $queue = $manager->initQueue('order');
    $queue->setExitOnfinish(!true);

    $queue->setMissionProcessor(new \Coco\examples\PhpExecor());
    $queue->addResultProcessor(new \Coco\examples\PhpResultProcessor());
//    $queue->addResultProcessor(new \Coco\examples\PhpResultProcessor());

    $queue->listen();