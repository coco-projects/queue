<?php

    namespace Coco\queue\abstracts;

abstract class ResultProcessorAbstract
{
    abstract public function onSuccess(MissionAbstract $mission): void;

    abstract public function onCatch(MissionAbstract $mission, \Exception $exception): void;
}
