<?php

    use Coco\queue\Launcher;

    require '../../vendor/autoload.php';

    $script = '../../examples/demo1/consumer.php';

    $launcher = new Launcher($script);

