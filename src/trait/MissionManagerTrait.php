<?php

    namespace Coco\queue\trait;

    use Coco\queue\MissionManager;

trait MissionManagerTrait
{
    protected MissionManager $manager;

    public function getManager(): MissionManager
    {
        return $this->manager;
    }

    public function setManager(MissionManager $manager): static
    {
        $this->manager = $manager;

        return $this;
    }
}
