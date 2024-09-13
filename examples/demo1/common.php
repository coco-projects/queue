<?php

    use Coco\queue\MissionManager;
    use DI\Container;

    require '../../vendor/autoload.php';

    $container = new Container();

    $container->set('redisClient', function(Container $container) {
        return (new \Redis());
    });

    $manager = new MissionManager($container);

    // 生产中如果要输出日志，必须手动安装 Monolog
    $logger = new \Monolog\Logger('my_logger');
    $manager->setLogger($logger);
    $manager->addStdoutLogger();

    $manager->initRedisClient(function(MissionManager $missionManager) {
        $redis = $missionManager->getContainer()->get('redisClient');
        $redis->select(5);

        return $redis;
    });
