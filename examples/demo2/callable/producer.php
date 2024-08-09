<?php

    require "./common.php";


    foreach (range(1, 1000) as $k => $v)
    {
        $mission = new \Coco\queue\missions\CallableMission();
        $mission->setCallback(function($id, $obj) {

            if (rand(1, 100) % 3)
            {
//                throw new Exception('test Exception');
            }

            echo get_class($obj);
            echo PHP_EOL;

            return $id * 1;
        });

        $mission->setParameters([
            $v,
            new \SplQueue(),
        ]);

        $queue->addNewMission($mission);
    }