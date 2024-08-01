<?php

    require "./common.php";

    /*********************************/

    $queue = $manager->initQueue('order');
    $queue->setExitOnfinish(!true);

    $manager->resetQueue('order');
