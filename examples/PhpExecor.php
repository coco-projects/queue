<?php

    namespace Coco\examples;

    use Coco\queue\abstracts\MissionAbstract;
    use Coco\queue\abstracts\ProcessorAbstract;

    class PhpExecor extends ProcessorAbstract
    {
        public function exec(MissionAbstract $mission): mixed
        {
            $evaluateResult = $mission->getEvaluateResult();

            $result = call_user_func_array($evaluateResult['callback'], $evaluateResult['parameters']);

            return $result;
        }
    }