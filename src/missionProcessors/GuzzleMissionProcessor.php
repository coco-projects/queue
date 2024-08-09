<?php

    namespace Coco\queue\missionProcessors;

    use Coco\queue\abstracts\MissionAbstract;
    use Coco\queue\abstracts\ProcessorAbstract;
    use GuzzleHttp\Client;

class GuzzleMissionProcessor extends ProcessorAbstract
{
    public function exec(MissionAbstract $mission): mixed
    {
        $evaluateResult = $mission->getEvaluateResult();

        $client = new Client($evaluateResult['clientOptions']);

        return $client->request($evaluateResult['method'], $evaluateResult['url'], $evaluateResult['requestOptions']);
    }
}
