<?php

    namespace Coco\queue\abstracts;

    use Coco\closure\ClosureUtils;
    use Coco\queue\exceptions\SerializeErrorException;
    use Coco\queue\Queue;

abstract class MissionAbstract
{
    protected ?Queue  $queue        = null;
    protected ?string $id           = null;
    protected int     $times        = 0;
    protected bool    $terminated   = false;
    protected bool    $isSuccessful = false;
    protected ?string $error        = null;
    protected mixed   $result       = null;

    public static function getIns(): static
    {
        return new static();
    }

    public function __construct()
    {
        $this->setId(hrtime(1));
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function restore(): static
    {
        $this->times        = 0;
        $this->isSuccessful = false;
        $this->terminated   = false;
        $this->result       = null;
        $this->queue        = null;
        $this->error        = null;

        return $this;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function setIsSuccessful(bool $isSuccessful): static
    {
        $this->isSuccessful = $isSuccessful;

        return $this;
    }

    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    public function setTerminated(bool $terminated): static
    {
        $this->terminated = $terminated;

        return $this;
    }

    public function isTerminated(): bool
    {
        return $this->terminated;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): static
    {
        $this->error = $error;

        return $this;
    }

    public function getTimes(): int
    {
        return $this->times;
    }

    public function incTimes(): static
    {
        $this->times++;

        return $this;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function setQueue(Queue $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function setResult(mixed $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function __toString(): string
    {
        return $this->serialize();
    }

    public function perpareToSerialize(): void
    {
        $this->queue = null;
    }

    public function serialize(): string
    {
        $this_ = clone $this;
        $this_->perpareToSerialize();
        try {
            $strings = ClosureUtils::serialize($this_);
            $this_   = null;
        } catch (\Exception $exception) {
            throw new SerializeErrorException($exception->getMessage(), $this->getId());
        }

        return $strings;
    }

    abstract protected function integration(): void;

    abstract public function getEvaluateResult(): mixed;
}
