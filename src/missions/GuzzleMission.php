<?php

    namespace Coco\queue\missions;

    use Coco\queue\abstracts\MissionAbstract;

class GuzzleMission extends MissionAbstract
{
    protected array  $clientOptions  = [];
    protected array  $requestOptions = [];
    protected string $method         = 'get';
    protected string $url;

    public function __construct()
    {
        parent::__construct();
    }

    protected function integration(): void
    {
    }

    public function addClientOptions($key, $clientOption): static
    {
        $this->clientOptions[$key] = $clientOption;

        return $this;
    }

    public function addRequestOptions($key, $clientOption): static
    {
        $this->requestOptions[$key] = $clientOption;

        return $this;
    }

    public function setClientOptions(array $clientOptions): static
    {
        $this->clientOptions = $clientOptions;

        return $this;
    }

    public function setRequestOptions(array $requestOptions): static
    {
        $this->requestOptions = $requestOptions;

        return $this;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getEvaluateResult(): array
    {
        $this->integration();

        return [
            "url"            => $this->url,
            "requestOptions" => $this->requestOptions,
            "method"         => $this->method,
            "clientOptions"  => $this->clientOptions,
        ];
    }
}
