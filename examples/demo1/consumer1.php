<?php

    require "./common.php";

    /*********************************/
    //消费

    $queue1 = $manager->initQueue('type');
    $queue = $manager->initQueue('order');
    $queue->setExitOnfinish(!true);

    $queue->setMissionProcessor(new \Coco\examples\PhpExecor());
    $queue->addResultProcessor(new \Coco\examples\PhpResultProcessor());
    $queue->addResultProcessor(new \Coco\examples\PhpResultProcessor());

    $mission = new \Coco\queue\missions\PhpMission();
    $mission->setCallback(function($id) {

        throw new Exception('test Exception');

        return $id * 10;
    });

    $mission->setParameters([
        "id" => 1,
    ]);

    $queue->execMissionDirect($mission);

