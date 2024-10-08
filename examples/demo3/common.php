<?php

    require '../../vendor/autoload.php';

    $script = '../../examples/demo1/consumer.php';

    $launcher = new \Coco\commandRunner\PhpLauncher($script);

    $launcher->setStandardLogger('test');
    $launcher->addStdoutHandler(callback: $launcher::getStandardFormatter());
