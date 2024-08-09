<?php

    namespace Coco\queue\missionProcessors;

    use Coco\queue\abstracts\MissionAbstract;
    use Coco\queue\abstracts\ProcessorAbstract;

class CallableMissionProcessor extends ProcessorAbstract
{
    public function exec(MissionAbstract $mission): mixed
    {
        $evaluateResult = $mission->getEvaluateResult();

        return call_user_func_array($evaluateResult['callback'], $evaluateResult['parameters']);
    }
}
