<?php

    require "./common.php";

    /*********************************/

    $queue = $manager->initQueue('order');
    $queue->setExitOnfinish(!true);

    $queue->setMaxTimes(4);
