<?php

    require "./common.php";

    /*********************************/

    $queue1 = $manager->initQueue('type');
    $queue = $manager->initQueue('order');
    $queue->setExitOnfinish(!true);

    $queue->setEnable(!!true);
