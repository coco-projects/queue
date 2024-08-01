<?php

    namespace Coco\queue\missions;

    use Coco\queue\abstracts\MissionAbstract;

    class PhpMission extends MissionAbstract
    {
        protected array $parameters = [];
        protected       $callback   = null;

        public function __construct()
        {
            parent::__construct();
        }

        public function setParameters(array $parameters): static
        {
            foreach ($parameters as $k => $v)
            {
                $this->parameters[$k] = $v;
            }

            return $this;
        }

        public function setCallback(callable $callback): static
        {
            $this->callback = $callback;

            return $this;
        }

        public function getEvaluateResult(): array
        {
            $this->integration();

            return [
                "parameters" => $this->parameters,
                "callback"   => $this->callback,
            ];
        }

        protected function integration(): void
        {
        }
    }