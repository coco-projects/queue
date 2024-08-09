<?php

    namespace Coco\queue\resultProcessor;

    use Coco\queue\abstracts\MissionAbstract;
    use Coco\queue\abstracts\ResultProcessorAbstract;

class CustomResultProcessor extends ResultProcessorAbstract
{
    protected $onSuccessCallback = null;
    protected $onCatchCallback   = null;

    public function __construct($onSuccessCallback = null, $onCatchCallback = null)
    {
        $this->onSuccessCallback = $onSuccessCallback;
        $this->onCatchCallback   = $onCatchCallback;
    }

    public function onSuccess(MissionAbstract $mission): void
    {
        if (is_callable($this->onSuccessCallback)) {
            call_user_func_array($this->onSuccessCallback, [$mission]);
        }
    }

    public function onCatch(MissionAbstract $mission, \Exception $exception): void
    {
        if (is_callable($this->onCatchCallback)) {
            call_user_func_array($this->onCatchCallback, [
                $mission,
                $exception,
            ]);
        }
    }
}
