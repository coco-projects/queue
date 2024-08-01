<?php

    namespace Coco\examples;

    use Coco\queue\abstracts\MissionAbstract;
    use Coco\queue\abstracts\ResultProcessorAbstract;

    class PhpResultProcessor extends ResultProcessorAbstract
    {
        public function onSuccess(MissionAbstract $mission): void
        {
            echo("onSuccess【{$mission->getResult()}】" . PHP_EOL);
        }

        public function onCatch(MissionAbstract $mission, \Exception $exception): void
        {
            echo("onCatch【{$exception->getMessage()}】" . PHP_EOL);
        }
    }