<?php

    require "./common.php";

    /*********************************/

    $queue = $manager->initQueue('order');
    $queue->setExitOnfinish(!true);


//    $queue->restoreErrorMission();
    $queue->restoreTimesReachedMission();
