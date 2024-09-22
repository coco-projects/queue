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
            '队列名称',
            '启用状态',
            '暂停状态',
            '一共写入',
            '剩余任务',
            '消费速率',
            '剩余时间',
            '超次任务',
            '出错任务',
            '终止任务',
            '锁定状态',
            '出错重试',
            '连续重试',
            '最大重试',
            '延迟微秒',
            '成功次数',
            '失败次数',
        ]);

        while (true) {
            $info = $this->getAllQueueInfo();
            $rows = [];

            foreach ($info as $k => $v) {
                $rows[] = [
                    "<info>" . $v['name'] . "</info>",
                    $v['isEnable'] ? '<info>Y</info>' : '<error>N</error>',
                    $v['isPause'] ? '<info>Y</info>' : '<error>N</error>',
                    $v['totalMission'],
                    $v['countRunning'],
                    $v['rate'] . '/S',
                    static::formatTime($v['remain']) ,
                    $v['countTimesReached'] > 0 ? "<error>" . $v['countTimesReached'] . "</error>" : "<info>" . $v['countTimesReached'] . "</info>",
                    $v['countError'] > 0 ? "<error>" . $v['countError'] . "</error>" : "<info>" . $v['countError'] . "</info>",
                    $v['countTerminated'] > 0 ? "<error>" . $v['countTerminated'] . "</error>" : "<info>" . $v['countTerminated'] . "</info>",
                    $v['isLocked'] ? '<info>Y</info>' : '<error>N</error>',
                    $v['isRetryOnError'] ? '<info>Y</info>' : '<error>N</error>',
                    $v['isContinuousRetry'] ? '<info>Y</info>' : '<error>N</error>',
                    $v['maxTimes'],
                    $v['delayMs'] . '<info>ms</info>',
                    $v['successTimes'] > 0 ? "<error>" . $v['successTimes'] . "</error>" : "<info>" . $v['successTimes'] . "</info>",
                    $v['errorTimes'] > 0 ? "<error>" . $v['errorTimes'] . "</error>" : "<info>" . $v['errorTimes'] . "</info>",
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

    public static function formatTime(int $seconds): string
    {
        $days             = floor($seconds / 86400);
        $hours            = floor(($seconds % 86400) / 3600);
        $minutes          = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        $result = [];
        if ($days > 0) {
            $result[] = "{$days}天";
        }

        if ($hours > 0 || !empty($result)) {
            $result[] = "{$hours}时";
        }

        // 有天数时也显示小时
        if ($minutes > 0 || !empty($result)) {
            $result[] = "{$minutes}分";
        }

        // 有小时或天数时显示分钟
        $result[] = "{$remainingSeconds}秒";

        return implode('', $result);
    }
}
