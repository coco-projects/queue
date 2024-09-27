<?php

    use Coco\queue\MissionManager;
    use DI\Container;

    require '../../../vendor/autoload.php';

    $container = new Container();

    $container->set('redisClient', function(Container $container) {
        return (new \Redis());
    });

    $manager = new MissionManager($container);

    $manager->setStandardLogger('queue');
    $manager->addStdoutHandler(callback: function(\Monolog\Handler\StreamHandler $handler, MissionManager $_this) {
        $handler->setFormatter(new \Coco\logger\MyFormatter());
    });

    $manager->initRedisClient(function(MissionManager $missionManager) {
        $redis = $missionManager->getContainer()->get('redisClient');
        $redis->select(5);

        return $redis;
    });

    $queue = $manager->initQueue('commandTest');
