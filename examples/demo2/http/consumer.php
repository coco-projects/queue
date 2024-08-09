<?php

    require "./common.php";

    $queue->setExitOnfinish(!true);
    $queue->setContinuousRetry(true);
    $queue->setDelayMs(1);
    $queue->setEnable(true);
    $queue->setMaxTimes(5);
    $queue->setIsRetryOnError(true);

    $queue->setMissionProcessor(new \Coco\queue\missionProcessors\GuzzleMissionProcessor());
    $queue->addResultProcessor(new \Coco\queue\resultProcessor\EchoResultProcessor(function(GuzzleHttp\Psr7\Response $response) {
        return $response->getBody()->getContents();
    }));

    $queue->listen();