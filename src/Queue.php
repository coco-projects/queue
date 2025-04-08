<?php

    namespace Coco\queue;

    use Coco\closure\ClosureUtils;
    use Coco\queue\abstracts\MissionAbstract;
    use Coco\queue\abstracts\ProcessorAbstract;
    use Coco\queue\abstracts\ResultProcessorAbstract;
    use Coco\queue\exceptions\MissionExecErrorException;
    use Coco\queue\exceptions\SerializeErrorException;
    use Coco\queue\trait\MissionManagerTrait;
    use Coco\timer\Timer;

class Queue
{
    use MissionManagerTrait;

    protected string             $name;
    protected bool               $exitOnfinish           = true;
    protected int                $statisticsListNum      = 20;
    protected array              $resultProcessor        = [];
    protected ?ProcessorAbstract $missionProcessor       = null;
    protected ?Timer             $timer                  = null;
    protected                    $onFinish               = null;
    protected                    $onEachMissionStartExec = null;

    public function __construct(string $name, MissionManager $manager)
    {
        $this->name  = $name;
        $this->timer = new Timer();
        $this->timer->start();
        $this->setManager($manager);
        $this->initQueueConfig();
    }

    protected function initQueueConfig(): void
    {
        if (false === $this->getManager()->getRedisClient()->get($this->queueSuccessTimesName())) {
            $this->getManager()->getRedisClient()->set($this->queueSuccessTimesName(), 0);
        }

        if (false === $this->getManager()->getRedisClient()->get($this->queueErrorTimesName())) {
            $this->getManager()->getRedisClient()->set($this->queueErrorTimesName(), 0);
        }

        if (false === $this->getManager()->getRedisClient()->get($this->queueTotalMissionName())) {
            $this->getManager()->getRedisClient()->set($this->queueTotalMissionName(), 0);
        }

        if (false === $this->getManager()->getRedisClient()->get($this->queueLockName())) {
            $this->getManager()->getRedisClient()->set($this->queueLockName(), 0);
        }

        if (false === $this->getManager()->getRedisClient()->get($this->queueEnableName())) {
            $this->getManager()->getRedisClient()->set($this->queueEnableName(), 1);
        }

        if (false === $this->getManager()->getRedisClient()->get($this->queuePauseName())) {
            $this->getManager()->getRedisClient()->set($this->queuePauseName(), 0);
        }

        if (false === $this->getManager()->getRedisClient()->get($this->queueRetryOnErrorName())) {
            $this->getManager()->getRedisClient()->set($this->queueRetryOnErrorName(), 1);
        }

        if (false === $this->getManager()->getRedisClient()->get($this->queueDelayMsName())) {
            $this->getManager()->getRedisClient()->set($this->queueDelayMsName(), 0);
        }

        if (false === $this->getManager()->getRedisClient()->get($this->queueMaxTimesName())) {
            $this->getManager()->getRedisClient()->set($this->queueMaxTimesName(), 3);
        }

        if (false === $this->getManager()->getRedisClient()->get($this->queueContinuousRetryName())) {
            $this->getManager()->getRedisClient()->set($this->queueContinuousRetryName(), 1);
        }
    }

    public function reset(): void
    {
        $this->getManager()->getRedisClient()->set($this->queueSuccessTimesName(), 0);
        $this->getManager()->getRedisClient()->set($this->queueErrorTimesName(), 0);
        $this->getManager()->getRedisClient()->set($this->queueTotalMissionName(), 0);
        $this->getManager()->getRedisClient()->set($this->queueLockName(), 0);
        $this->getManager()->getRedisClient()->set($this->queueEnableName(), 1);
        $this->getManager()->getRedisClient()->set($this->queuePauseName(), 0);
        $this->getManager()->getRedisClient()->set($this->queueRetryOnErrorName(), 1);
        $this->getManager()->getRedisClient()->set($this->queueDelayMsName(), 0);
        $this->getManager()->getRedisClient()->set($this->queueMaxTimesName(), 3);
        $this->getManager()->getRedisClient()->set($this->queueContinuousRetryName(), 1);

        $keysToDelete = $this->getManager()->getRedisClient()->keys($this->name . ':missions:*');

        foreach ($keysToDelete as $key) {
            $this->getManager()->getRedisClient()->del($key);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setOnFinish(callable $onFinish): static
    {
        $this->onFinish = $onFinish;

        return $this;
    }

    public function setOnEachMissionStartExec(callable $onEachMissionStartExec): static
    {
        $this->onEachMissionStartExec = $onEachMissionStartExec;

        return $this;
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueSuccessTimesName(): string
    {
        return $this->name . ':statistics:successTimes';
    }

    public function getSuccessTimes(): string
    {
        return (int)$this->getManager()->getRedisClient()->get($this->queueSuccessTimesName());
    }

    public function incSuccessTimes(): static
    {
        $this->getManager()->getRedisClient()->incr($this->queueSuccessTimesName());

        return $this;
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueErrorTimesName(): string
    {
        return $this->name . ':statistics:errorTimes';
    }

    public function getErrorTimes(): string
    {
        return (int)$this->getManager()->getRedisClient()->get($this->queueErrorTimesName());
    }

    public function incErrorTimes(): static
    {
        $this->getManager()->getRedisClient()->incr($this->queueErrorTimesName());

        return $this;
    }

    /**
     * -----------------------------------------------------------------
     */
    public function queueTotalMissionName(): string
    {
        return $this->name . ':statistics:totalMission';
    }

    public function getTotalMission(): string
    {
        return (int)$this->getManager()->getRedisClient()->get($this->queueTotalMissionName());
    }

    public function incTotalMissionTimes(): static
    {
        $this->getManager()->getRedisClient()->incr($this->queueTotalMissionName());

        return $this;
    }


    /**
     * -----------------------------------------------------------------
     */

    public function queueLockName(): string
    {
        return $this->name . ':config:isLocked';
    }

    public function queueIsLocked(): bool
    {
        return !!$this->manager->getRedisClient()->get($this->queueLockName());
    }

    public function lockQueue(): static
    {
        $this->manager->getRedisClient()->psetex($this->queueLockName(), $this->getDelayMs(), 1);

        return $this;
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueEnableName(): string
    {
        return $this->name . ':config:isEnable';
    }

    public function setEnable(bool $value): static
    {
        $this->getManager()->getRedisClient()->set($this->queueEnableName(), (int)$value);

        return $this;
    }

    public function isEnable(): bool
    {
        return '0' !== $this->getManager()->getRedisClient()->get($this->queueEnableName());
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queuePauseName(): string
    {
        return $this->name . ':config:isPause';
    }

    public function setPause(bool $value): static
    {
        $this->getManager()->getRedisClient()->set($this->queuePauseName(), (int)$value);

        return $this;
    }

    public function isPause(): bool
    {
        return 1 == (int)$this->getManager()->getRedisClient()->get($this->queuePauseName());
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueRetryOnErrorName(): string
    {
        return $this->name . ':config:isRetryOnError';
    }

    public function setIsRetryOnError(bool $value): static
    {
        $this->getManager()->getRedisClient()->set($this->queueRetryOnErrorName(), (int)$value);

        return $this;
    }

    public function isRetryOnError(): bool
    {
        return '0' !== $this->getManager()->getRedisClient()->get($this->queueRetryOnErrorName());
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueContinuousRetryName(): string
    {
        return $this->name . ':config:continuousRetry';
    }

    public function setContinuousRetry(bool $value): static
    {
        $this->getManager()->getRedisClient()->set($this->queueContinuousRetryName(), (int)$value);

        return $this;
    }

    public function isContinuousRetry(): bool
    {
        return '0' !== $this->getManager()->getRedisClient()->get($this->queueContinuousRetryName());
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueDelayMsName(): string
    {
        return $this->name . ':config:delayMs';
    }

    public function setDelayMs(int $value): static
    {
        $this->getManager()->getRedisClient()->set($this->queueDelayMsName(), $value);

        return $this;
    }

    public function getDelayMs(): int
    {
        return (int)$this->getManager()->getRedisClient()->get($this->queueDelayMsName());
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueMaxTimesName(): string
    {
        return $this->name . ':config:maxTimes';
    }

    public function setMaxTimes(int $value): static
    {
        $this->getManager()->getRedisClient()->set($this->queueMaxTimesName(), $value);

        return $this;
    }

    public function getMaxTimes(): int
    {
        return (int)$this->getManager()->getRedisClient()->get($this->queueMaxTimesName());
    }


    /**
     * -----------------------------------------------------------------
     */

    public function queueRunningName(): string
    {
        return $this->name . ':missions:running';
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueErrorName(): string
    {
        return $this->name . ':missions:error';
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueTimesReachedName(): string
    {
        return $this->name . ':missions:timesReached';
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueTerminatedName(): string
    {
        return $this->name . ':missions:terminated';
    }

    public function queueTempName(): string
    {
        return $this->name . ':missions:temp';
    }

    /**
     * -----------------------------------------------------------------
     */

    public function setExitOnfinish(bool $exitOnfinish): static
    {
        $this->exitOnfinish = $exitOnfinish;

        return $this;
    }

    /**
     * -----------------------------------------------------------------
     */

    public function addResultProcessor(ResultProcessorAbstract $resultProcessor): static
    {
        $this->resultProcessor[] = $resultProcessor;

        return $this;
    }

    public function setMissionProcessor(ProcessorAbstract $missionProcessor): static
    {
        $this->missionProcessor = $missionProcessor;

        return $this;
    }

    public function isMissionTimesReached(MissionAbstract $mission): bool
    {
        return $mission->getTimes() >= $this->getMaxTimes();
    }

    /**
     * -----------------------------------------------------------------
     */

    public function getConfig(): array
    {
        return [
            "isLocked"          => $this->queueIsLocked(),
            "isEnable"          => $this->isEnable(),
            "isPause"           => $this->isPause(),
            "isRetryOnError"    => $this->isRetryOnError(),
            "isContinuousRetry" => $this->isContinuousRetry(),
            "delayMs"           => $this->getDelayMs(),
            "maxTimes"          => $this->getMaxTimes(),
        ];
    }

    public function getStatistics(): array
    {
        $rate           = $this->execMissionPerSec();
        $remainMissions = $this->countRunning();

        return [
            "rate"   => $rate,
            "remain" => max(bcdiv($remainMissions, $rate, 3), -1),

            "successTimes" => $this->getSuccessTimes(),
            "errorTimes"   => $this->getErrorTimes(),
            "totalMission" => $this->getTotalMission(),

            "countRunning"      => $this->countRunning(),
            "countTerminated"   => $this->countTerminated(),
            "countTimesReached" => $this->countTimesReached(),
            "countError"        => $this->countError(),
        ];
    }

    /**
     * -----------------------------------------------------------------
     */

    public function queueNodeName(): string
    {
        return $this->name . ':statistics:nodes';
    }

    protected function pushNode(): static
    {
        $queueName = $this->queueNodeName();

        $this->manager->getRedisClient()->lpush($queueName, json_encode([
            microtime(true),
            (string)(hrtime(true) / 1e9),
        ]));

        $len = $this->getManager()->getRedisClient()->lLen($queueName);

        if ($len > $this->statisticsListNum) {
            $this->getManager()->getRedisClient()->rPop($queueName);
        }

        return $this;
    }


    public function execMissionPerSec(): int|string
    {
        $queueName = $this->queueNodeName();

        $nodes = $this->getNodeList();
        $len   = count($nodes);

        if ($len == 0) {
            return -1;
        }

        //最后一次执行的时间戳
        $lastTime = $nodes[0][0];

        //最后一次执行距离当前时间过去几秒
        $toCurrentTime = microtime(true) - $lastTime;

        //如果超过多少秒，就清空队列，相当于任务已经停下了
        if ($toCurrentTime > 5) {
            $this->getManager()->getRedisClient()->del($queueName);
        }

        $intervalAll = $toCurrentTime;

        for ($i = 0; $i < $len; $i++) {
            if (isset($nodes[$i + 1])) {
                //获取两个任务间隔时间
                $interval = bcsub($nodes[$i][1], $nodes[$i + 1][1], 9);

                //累加到总间隔时间
                $intervalAll = bcadd($intervalAll, $interval, 9);
            }
        }

        //计算平均间隔时间（每个任务要多少秒）
        $avg = bcdiv($intervalAll, $len, 9);

        if ($avg == 0) {
            return 0;
        }

        //计算每秒可以跑几个任务
        return bcdiv(1, $avg, 3);
    }

    public function getNodeList(): array
    {
        $queueName = $this->queueNodeName();

        $data   = [];
        $result = $this->manager->getRedisClient()->lrange($queueName, 0, -1);

        foreach ($result as $k => $v) {
            $data[] = json_decode($v);
        }

        return $data;
    }

    /**
     * -----------------------------------------------------------------
     */

    public function listen(): void
    {
        $queueName = $this->queueRunningName();

        while (true) {
            //手动关闭队列
            if (!$this->isEnable()) {
                $this->getManager()->logInfo("[$queueName]:队列已被关闭...");

                break 1;
            }

            while (true) {
                //手动关闭队列
                if (!$this->isEnable()) {
                    $this->getManager()->logInfo("[$queueName]:队列已被关闭...");

                    break 2;
                }

                $mission = $this->popMissionFormRunning();

                if (!$mission) {
                    usleep(1000 * 2);
                    break 1;
                }

                $msg = [
                    '[O]队列:' . $this->getName(),
                    ', 当前历时:' . $this->timer->lastMarkToNowTime() . ' S',
                    ', 总历时:' . $this->timer->totalTime() . ' S',
                    ', 内存:' . $this->timer->getTotalMemory() . ' / ' . $this->timer->getTotalMemoryPeak(),
                ];
                $this->getManager()->logInfo(implode('', $msg));

                $this->execMissionWithQueue($mission);

                $this->pushNode();

                usleep(1000 * 2);
            }

            if ($this->exitOnfinish) {
                if (is_callable($this->onFinish)) {
                    call_user_func_array($this->onFinish, [$this]);
                }

                break;
            }

            $msg = [
                '[-]队列:' . $this->getName(),
                ', 当前历时:' . $this->timer->lastMarkToNowTime() . ' S',
                ', 总历时:' . $this->timer->totalTime() . ' S',
                ', 内存:' . $this->timer->getTotalMemory() . ' / ' . $this->timer->getTotalMemoryPeak(),
            ];
            $this->getManager()->logInfo(implode('', $msg));
        }

        $msg = [
            '【--队列执行结束--】' . $this->getName(),
            ', 当前历时:' . $this->timer->lastMarkToNowTime() . ' S',
            ', 总历时:' . $this->timer->totalTime() . ' S',
            ', 内存:' . $this->timer->getTotalMemory() . ' / ' . $this->timer->getTotalMemoryPeak(),
        ];
        $this->getManager()->logInfo(implode('', $msg));
    }

    /**
     * -----------------------------------------------------------------
     */
    public function execMissionDirect(MissionAbstract $mission): void
    {
        $this->incTotalMissionTimes();

        $onSuccess = function (MissionAbstract $mission, Queue $queue) {
            $this->incSuccessTimes();
        };

        $onError = function (MissionAbstract $mission, Queue $queue) {
            $this->incErrorTimes();
        };

        $this->execMission($mission, $onSuccess, $onError);
    }

    protected function execMissionWithQueue(MissionAbstract $mission): void
    {
        $onSuccess = function (MissionAbstract $mission, Queue $queue) {
            $this->incSuccessTimes();
        };

        $onError = function (MissionAbstract $mission, Queue $queue) {
            $this->incErrorTimes();

            //如果开启错误重试
            if ($queue->isRetryOnError()) {
                //如果达到重试次数，写入次数满队列
                if ($this->isMissionTimesReached($mission)) {
                    $msg = "重试次数达到上限，写入次满队列:[{$mission->getId()}]:{$mission->getTimes()}" ;
                    $mission->setError($msg);
                    $queue->getManager()->logError($msg);

                    $queue->pushMissionToTimesReached($mission);
                }
                //如果没达到重试次数，写到执行队列，继续执行
                else {
                    //连续重试
                    if ($this->isContinuousRetry()) {
                        $msg = "写入队头，立即重试:[{$mission->getId()}]:第{$mission->getTimes()}次" ;
                        $mission->setError($msg);
                        $queue->getManager()->logError($msg);
                        $queue->unshiftMissionToRunning($mission);
                    } else {
                        $msg = "写入队尾:[{$mission->getId()}]:第{$mission->getTimes()}次" ;
                        $mission->setError($msg);
                        $queue->getManager()->logError($msg);
                        $queue->pushMissionToRunning($mission);
                    }
                }
            }
            //如果关闭错误重试，直接写入error队列
            else {
                $queue->pushMissionToError($mission);
            }
        };

        $this->execMission($mission, $onSuccess, $onError);
    }

    protected function execMission(MissionAbstract $mission, callable $onSuccess, callable $onError): void
    {
        $mission->setQueue($this);

        if (is_callable($this->onEachMissionStartExec)) {
            call_user_func_array($this->onEachMissionStartExec, [$mission]);
        }

        if (!$this->missionProcessor instanceof ProcessorAbstract) {
            $mission->setError('队列未指定 missionProcessor...');
            throw new MissionExecErrorException($mission);
        }

        //不指定也可以
        /*
        if (!count($this->resultProcessor)) {
            $mission->setError('至少指定一个 ResultProcessor...');
            throw new MissionExecErrorException($mission);
        }
        */

        if ($mission->isTerminated()) {
            $msg = "任务已被终止:[{$mission->getId()}]" ;

            $mission->setError($msg);
            $this->getManager()->logError($msg);

            $this->pushMissionToTerminated($mission);

            return;
        }

        try {
            $mission->incTimes();

            $msg = "执行任务:[{$mission->getId()}] , 第{$mission->getTimes()}次" ;
            $this->getManager()->logInfo($msg);

            $result = $this->missionProcessor->exec($mission);
            $mission->setResult($result);
            $mission->setIsSuccessful(true);

            $msg = "执行成功:[{$mission->getId()}] " ;
            $this->getManager()->logInfo($msg);

            $onSuccess($mission, $this);

            /**
             * @var ResultProcessorAbstract $resultProcessor
             */
            foreach ($this->resultProcessor as $k => $resultProcessor) {
                $resultProcessor->onSuccess($mission);
            }
        } catch (\Exception $exception) {
            $onError($mission, $this);

            $msg = "执行出错:[{$mission->getId()}] " . $exception->getMessage() ;
            $this->getManager()->logError($msg);

            /**
             * @var ResultProcessorAbstract $resultProcessor
             */
            foreach ($this->resultProcessor as $k => $resultProcessor) {
                $resultProcessor->onCatch($mission, $exception);
            }
        }
    }

    /**
     * -----------------------------------------------------------------
     */

    public function restoreErrorMission(): static
    {
        while ($mission = $this->popMissionFormError()) {
            $mission->restore();
            $this->pushMissionToRunning($mission);
        }

        return $this;
    }

    public function restoreTerminatedMission(): static
    {
        while ($mission = $this->popMissionFormTerminated()) {
            $mission->restore();
            $this->pushMissionToRunning($mission);
        }

        return $this;
    }

    public function restoreTimesReachedMission(): static
    {
        while ($mission = $this->popMissionFormTimesReached()) {
            $mission->restore();
            $this->pushMissionToRunning($mission);
        }

        return $this;
    }

    /**
     * -----------------------------------------------------------------
     */
    public function countRunning(): int
    {
        $queueName = $this->queueRunningName();

        return (int)$this->getManager()->getRedisClient()->lLen($queueName);
    }

    public function countTerminated(): int
    {
        $queueName = $this->queueTerminatedName();

        return (int)$this->getManager()->getRedisClient()->lLen($queueName);
    }

    public function countTimesReached(): int
    {
        $queueName = $this->queueTimesReachedName();

        return (int)$this->getManager()->getRedisClient()->lLen($queueName);
    }

    public function countError(): int
    {
        $queueName = $this->queueErrorName();

        return (int)$this->getManager()->getRedisClient()->lLen($queueName);
    }

    /**
     * -----------------------------------------------------------------
     */

    //写入到正常执行队列尾部
    public function addNewMission(MissionAbstract $mission): void
    {
        $this->incTotalMissionTimes();
        $this->pushMissionToRunning($mission);
    }

    //写入到正常执行队列尾部
    public function pushMissionToRunning(MissionAbstract $mission): void
    {
        $queueName = $this->queueRunningName();

        $res = $this->pushMission($mission, $queueName);

        if ($res) {
            $msg = "任务写入成功:[$queueName]:" . $mission->getId() ;
            $this->getManager()->logInfo($msg);
        } else {
            $msg = "任务写入失败:[$queueName]:" . $mission->getId() ;
            $this->getManager()->logError($msg);
        }
    }

    //写入到正常执行队列头部
    public function unshiftMissionToRunning(MissionAbstract $mission): void
    {
        $queueName = $this->queueRunningName();

        $res = $this->unshiftMission($mission, $queueName);

        if ($res) {
            $msg = "任务抢占成功:[$queueName]:" . $mission->getId() ;
            $this->getManager()->logInfo($msg);
        } else {
            $msg = "任务抢占失败:[$queueName]:" . $mission->getId() ;
            $this->getManager()->logError($msg);
        }
    }

    //获取一个正常执行的任务
    public function popMissionFormRunning(): ?MissionAbstract
    {
        $queueName = $this->queueRunningName();

        //暂停了
        if ($this->isPause()) {
            $this->getManager()->logInfo("[$queueName]:暂停中...");

            while (1) {
                if (!$this->isPause()) {
                    break;
                }

                //隔200毫秒查一次是否到时间
                usleep(200 * 1000);
            }

            $this->getManager()->logInfo("[$queueName]:恢复执行");
        }

        $missionObj = $this->popMission($queueName);

        if (!$missionObj) {
            return null;
        }

        //如果设置了延时
        if ($this->getDelayMs()) {
            $this->getManager()->logInfo("[$queueName]:延时中:" . $this->getDelayMs() . ' ms');

            //延时多久再取队
            while (1) {
                if ($this->queueIsLocked()) {
                    //隔100毫秒查一次是否到时间
                    usleep(max(($this->getDelayMs() / 10), 20) * 1000);
                } else {
                    //加延时锁
                    $this->lockQueue();
                    break;
                }
            }
        }

        return $missionObj;
    }

    /*---------------------*/

    //写入到终止队列尾部
    public function pushMissionToTerminated(MissionAbstract $mission): void
    {
        $queueName = $this->queueTerminatedName();

        $res = $this->pushMission($mission, $queueName);

        if ($res) {
            $msg = "任务写入成功:[$queueName]:" . $mission->getId() ;
            $this->getManager()->logInfo($msg);
        } else {
            $msg = "任务写入失败:[$queueName]:" . $mission->getId() ;
            $this->getManager()->logError($msg);
        }
    }

    //获取一个被终止的任务
    public function popMissionFormTerminated(): ?MissionAbstract
    {
        $queueName = $this->queueTerminatedName();

        //手动关闭队列
        if (!$this->isEnable()) {
            $this->getManager()->logInfo("[$queueName]:队列已被关闭...");

            return null;
        }

        $missionObj = $this->popMission($queueName);

        return $missionObj;
    }

    /*---------------------*/

    //获取一个达到重试次数的任务
    public function popMissionFormTimesReached(): ?MissionAbstract
    {
        $queueName = $this->queueTimesReachedName();

        //手动关闭队列
        if (!$this->isEnable()) {
            $this->getManager()->logInfo("[$queueName]:队列已被关闭...");

            return null;
        }

        $missionObj = $this->popMission($queueName);

        return $missionObj;
    }

    //写入到达到重试次数队列尾部
    public function pushMissionToTimesReached(MissionAbstract $mission): void
    {
        $queueName = $this->queueTimesReachedName();

        $res = $this->pushMission($mission, $queueName);

        if ($res) {
            $msg = "任务写入成功:[$queueName]:" . $mission->getId() ;
            $this->getManager()->logInfo($msg);
        } else {
            $msg = "任务写入失败:[$queueName]:" . $mission->getId() ;
            $this->getManager()->logError($msg);
        }
    }

    /*---------------------*/

    //写入到执行出错队列尾部
    public function pushMissionToError(MissionAbstract $mission): void
    {
        $queueName = $this->queueErrorName();

        $res = $this->pushMission($mission, $queueName);

        if ($res) {
            $msg = "错误任务写入成功:[$queueName]:" . $mission->getId() ;
            $this->getManager()->logInfo($msg);
        } else {
            $msg = "错误任务写入失败:[$queueName]:" . $mission->getId() ;
            $this->getManager()->logError($msg);
        }
    }

    //获取一个执行出错的任务
    public function popMissionFormError(): ?MissionAbstract
    {
        $queueName = $this->queueErrorName();

        //手动关闭队列
        if (!$this->isEnable()) {
            $this->getManager()->logInfo("[$queueName]:队列已被关闭...");

            return null;
        }

        $missionObj = $this->popMission($queueName);

        return $missionObj;
    }


    /**
     * -----------------------------------------------------------------
     */

    //头进
    protected function unshiftMission(MissionAbstract $mission, string $queueName): bool
    {
        return $this->manager->getRedisClient()->rpush($queueName, $mission->serialize());
    }

    //尾进
    protected function pushMission(MissionAbstract $mission, string $queueName): bool
    {
        return $this->manager->getRedisClient()->lpush($queueName, $mission->serialize());
    }

    //头出
    protected function shiftMission(string $queueName): ?MissionAbstract
    {
        $missionArr = $this->manager->getRedisClient()->bLPop($queueName, 1);

        //没有任务
        if (!$missionArr) {
            return null;
        }

        $missionString = $missionArr[1];

        try {
            $missionObj = ClosureUtils::unserialize($missionString);
        } catch (\Exception $exception) {
            throw new SerializeErrorException($exception->getMessage(), $missionString);
        }

        return $missionObj;
    }

    //尾出
    protected function popMission(string $queueName): ?MissionAbstract
    {
        $missionArr = $this->manager->getRedisClient()->bRPop($queueName, 1);

        //没有任务
        if (!$missionArr) {
            return null;
        }

        $missionString = $missionArr[1];

        try {
            $missionObj = ClosureUtils::unserialize($missionString);
        } catch (\Exception $exception) {
            throw new SerializeErrorException($exception->getMessage(), $missionString);
        }

        return $missionObj;
    }
}
