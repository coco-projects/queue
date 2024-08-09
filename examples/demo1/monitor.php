<?php

    require "./common.php";

    $queue1 = $manager->initQueue('type');
    $queue  = $manager->initQueue('order');
    /*
        while (1)
        {
            print_r($manager->getAllQueueInfo());
            usleep(300 * 1000);
        }
    */

    $manager->getAllQueueInfoTable();



