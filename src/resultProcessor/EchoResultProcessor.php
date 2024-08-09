<?php

    namespace Coco\queue\resultProcessor;

    use Coco\queue\abstracts\MissionAbstract;
    use Coco\queue\abstracts\ResultProcessorAbstract;
    use function Amp\call;

class EchoResultProcessor extends ResultProcessorAbstract
{
    protected $callback = null;

    public function __construct($callback = null)
    {
        $this->callback = $callback;
    }

    public function onSuccess(MissionAbstract $mission): void
    {
        $result = $mission->getResult();

        if (is_callable($this->callback)) {
            $result = call_user_func_array($this->callback, [$result]);
        }

        echo("onSuccess【{$result}】" . PHP_EOL);
    }

    public function onCatch(MissionAbstract $mission, \Exception $exception): void
    {
        echo("onCatch【{$exception->getMessage()}】" . PHP_EOL);
    }
}
