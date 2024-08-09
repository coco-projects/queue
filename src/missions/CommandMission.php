<?php

    namespace Coco\queue\missions;

    use Coco\queue\abstracts\MissionAbstract;

class CommandMission extends MissionAbstract
{
    protected string $command;

    public function __construct()
    {
        parent::__construct();
    }

    protected function integration(): void
    {
    }

    public function getEvaluateResult(): array
    {
        $this->integration();

        return [
            "command" => $this->command,
        ];
    }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }
}
