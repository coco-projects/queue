<?php

    namespace Coco\queue\missionProcessors;

    use Coco\queue\abstracts\MissionAbstract;
    use Coco\queue\abstracts\ProcessorAbstract;

class CommandMissionProcessor extends ProcessorAbstract
{
    public function exec(MissionAbstract $mission): mixed
    {
        $evaluateResult = $mission->getEvaluateResult();

        $command = $evaluateResult['command'];

        return shell_exec($command);
    }
}
