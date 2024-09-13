<?php

    namespace Coco\queue;

    use Symfony\Component\Console\Helper\Table;
    use Coco\queue\exceptions\QueueNotFoundException;
    use DI\Container;
    use Psr\Log\LoggerInterface;
    use Symfony\Component\Console\Helper\TableSeparator;
    use Symfony\Component\Console\Output\ConsoleOutput;

class MissionManager
{
    use \Coco\logger\Logger;

    protected Container $container;
    protected \Redis    $redisClient;
    protected array     $queues;
    protected string    $prefix = 'CocoQueue';

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function setPrefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getQueue(string $queueName): Queue
    {
        if (isset($this->queues[$queueName])) {
            return $this->queues[$queueName];
        } else {
            throw new QueueNotFoundException($queueName);
        }
    }

    public function initQueue(string $queueName): Queue
    {
        $queue = new Queue($this->makeQueueName($queueName), $this);
        $this->redisClient->hSet($this->makeQueueNamesKey(), $queueName, 1);

        return $this->queues[$queueName] = $queue;
    }

    public function initRedisClient(callable $callback): static
    {
        $this->redisClient = $callback($this);

        return $this;
    }

    public function getRedisClient(): \Redis
    {
        return $this->redisClient;
    }

    public function getQueueList(): array
    {
        return array_keys($this->queues);
    }

    public function scanQueueList(): array
    {
        $res = $this->redisClient->hGetAll($this->makeQueueNamesKey());

        return array_keys($res);
    }

    public function makeQueueNamesKey(): string
    {
        return $this->prefix . ':queueNames';
    }

    public function getAllQueueInfo(): array
    {
        $queues = $this->getQueueList();
        $info   = [];
        foreach ($queues as $k => $queueName) {
            $queue       = $this->getQueue($queueName);
            $config      = $queue->getConfig();
            $statistics  = $queue->getStatistics();
            $tmp['name'] = $queueName;
            $tmp         = array_merge($tmp, $statistics);
            $tmp         = array_merge($tmp, $config);
            $info[]      = $tmp;
        }

        return $info;
    }


    public function getAllQueueInfoTable(): void
    {
        $output = new ConsoleOutput();

        $table = new Table($output);
        //            $table->setStyle('default');
        //            $table->setStyle('box');
        $table->setStyle('box-double');
        //            $table->setStyle('borderless');
        //            $table->setStyle('compact');
        //            $table->setStyle('symfony-style-guide');

        $table->setHeaders([
            'name',
            'isEnable',
            'isPause',
            'isRetryOnError',
            'isContinuousRetry',
            'rate',
            'maxTimes',
            'delayMs',
            'isLocked',
            'totalMission',
            'countRunning',
            'countTimesReached',
            'countTerminated',
            'countError',
            'successTimes',
            'errorTimes',
        ]);

        while (true) {
            $info = $this->getAllQueueInfo();
            $rows = [];

            foreach ($info as $k => $v) {
                $rows[] = [
                    $v['name'],
                    $v['isEnable'] ? '<info>Yes</info>' : '<error>No</error>',
                    $v['isPause'] ? '<info>Yes</info>' : '<error>No</error>',
                    $v['isRetryOnError'] ? '<info>Yes</info>' : '<error>No</error>',
                    $v['isContinuousRetry'] ? '<info>Yes</info>' : '<error>No</error>',
                    $v['rate'] . '/S',
                    $v['maxTimes'],
                    $v['delayMs'],
                    $v['isLocked'] ? '<info>Yes</info>' : '<error>No</error>',
                    $v['totalMission'],
                    $v['countRunning'],
                    $v['countTimesReached'],
                    $v['countTerminated'],
                    $v['countError'],
                    $v['successTimes'],
                    $v['errorTimes'],
                ];
                ($k !== count($info) - 1) && $rows[] = new TableSeparator();
            }

            $table->setRows($rows);

            // 清除屏幕并渲染新的表格
            // 清屏并移动光标到左上角
            echo "\033[H\033[J";
            $table->render();
            usleep(300 * 1000);
        }
    }

    public function destroyQueue(string $queueName): void
    {
        $this->redisClient->hDel($this->prefix . ':queueNames', $queueName);
        $keysToDelete = $this->redisClient->keys($this->makeQueueName($queueName) . ':*');

        foreach ($keysToDelete as $key) {
            $this->redisClient->del($key);
        }
    }

    public function resetQueue(string $queueName): void
    {
        $this->getQueue($queueName)->reset();
    }

    public function clearAll(): void
    {
        $keysToDelete = $this->redisClient->keys($this->prefix . ':*');
        foreach ($keysToDelete as $key) {
            $this->redisClient->del($key);
        }
    }

    public function __destruct()
    {
        $this->redisClient->close();
    }


    /**
     * -----------------------------------------------------------------
     */
    public function makeQueueName(string $queueName): string
    {
        return $this->prefix . ':queues:' . $queueName;
    }
}
