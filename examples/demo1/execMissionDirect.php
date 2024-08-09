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

    $mission = new \Coco\queue\missions\CallableMission();
    $mission->setCallback(function($id) {

//        throw new Exception('test Exception');

        return $id * 10;
    });

    $mission->setParameters([
        "id" => 1,
    ]);

    $queue->execMissionDirect($mission);

