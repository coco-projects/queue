<?php

    require "./common.php";

    /*********************************/
    //消费

    $queue1 = $manager->initQueue('type');
    $queue  = $manager->initQueue('order');
    $queue->setExitOnfinish(!true);

    $queue->setMissionProcessor(new \Coco\queue\missionProcessors\CallableMissionProcessor());
    $queue->addResultProcessor(new \Coco\queue\resultProcessor\EchoResultProcessor());
    $queue->addResultProcessor(new \Coco\queue\resultProcessor\EchoResultProcessor());

    $queue->listen();