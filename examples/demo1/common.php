<?php

    use Coco\queue\MissionManager;
    use DI\Container;

    require '../../vendor/autoload.php';

    $container = new Container();

    $container->set('redisClient', function(Container $container) {
        return (new \Redis());
    });

    $manager = new MissionManager($container);

    $manager->setStandardLogger('queue');
    $manager->addStdoutHandler(callback: $manager::getStandardFormatter());

    $manager->initRedisClient(function(MissionManager $missionManager) {
        $redis = $missionManager->getContainer()->get('redisClient');
        $redis->select(5);

        return $redis;
    });
