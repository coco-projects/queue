<?php

    require "./common.php";


    foreach (range(1, 1000) as $k => $v)
    {
        $mission = new \Coco\queue\missions\HttpMission();
        $mission->setUrl('http://192.168.0.201:6004');

        $queue->addNewMission($mission);
    }