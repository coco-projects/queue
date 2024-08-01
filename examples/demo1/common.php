<?php

    use Coco\queue\MissionManager;
    use DI\Container;

    require '../../vendor/autoload.php';

    $container = new Container();

    $container->set('redisClient', function(Container $container) {
        return (new \Redis());
    });

    $manager = new MissionManager($container);

//    $manager->addLogger(new \Psr\Log\NullLogger());
    $manager->addLogger(new \Coco\queue\EchoLogger());

    $manager->initRedisClient(function(MissionManager $missionManager) {
        $redis = $missionManager->getContainer()->get('redisClient');
        $redis->select(5);

        return $redis;
    });
