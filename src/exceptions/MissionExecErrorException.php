<?php

    namespace Coco\queue\exceptions;

    use Coco\queue\abstracts\MissionAbstract;

    class MissionExecErrorException extends \RuntimeException
    {
        public function __construct(MissionAbstract $mission)
        {
            $error = '[任务执行出错] : ' . "[{$mission->getQueue()->getName()}][{$mission->getId()}][{$mission->getError()}]";
            parent::__construct($error);
        }
    }
