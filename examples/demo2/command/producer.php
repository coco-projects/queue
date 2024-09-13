<?php

    use Coco\queue\missions\CommandMission;

    require "./common.php";


    foreach (range(1, 1000) as $k => $v)
    {
        $mission = new CommandMission();
        $mission->setCommand('ls -al');

        $queue->addNewMission($mission);
    }