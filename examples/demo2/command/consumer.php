<?php

    require "./common.php";

    $queue->setExitOnfinish(!true);
    $queue->setContinuousRetry(true);
    $queue->setDelayMs(1);
    $queue->setEnable(true);
    $queue->setMaxTimes(5);
    $queue->setIsRetryOnError(true);

    $queue->setMissionProcessor(new \Coco\queue\missionProcessors\CommandMissionProcessor());
    $queue->addResultProcessor(new \Coco\queue\resultProcessor\EchoResultProcessor());
    $queue->addResultProcessor(new \Coco\queue\resultProcessor\EchoResultProcessor());

    $queue->listen();