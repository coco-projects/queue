<?php

    namespace Coco\queue\abstracts;

    abstract class ProcessorAbstract
    {
        abstract public function exec(MissionAbstract $mission): mixed;
    }