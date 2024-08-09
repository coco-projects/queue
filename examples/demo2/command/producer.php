<?php

    require "./common.php";


    foreach (range(1, 1000) as $k => $v)
    {
        $mission = new \Coco\queue\missions\CommandMission();
        $mission->setCommand('ls -al');

        $queue->addNewMission($mission);
    }