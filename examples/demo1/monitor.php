<?php

    require "./common.php";

    $queue1 = $manager->initQueue('type');
    $queue = $manager->initQueue('order');

//    print_r($manager->getAllQueueInfo());
    $manager->getAllQueueInfoTable();



