<?php

    namespace Coco\queue\missions;

    use Spatie\Url\Url;
    use GuzzleHttp\Psr7\Utils;

class HttpMission extends GuzzleMission
{
    //127.0.0.1:1080
    protected string $proxy      = '';
    protected int    $timeout    = 10;
    protected array  $queryData  = [];
    protected array  $postData   = [];
    protected array  $jsonData   = [];
    protected array  $uploadData = [];
    protected array  $headers    = [];
    protected array  $cookie     = [];

    public function __construct()
    {
        parent::__construct();
    }

    protected function integration(): void
    {
        parent::integration();

        if (!$this->url) {
            throw new \Exception('未设定请求url');
        }

        $urlObj = Url::fromString($this->url);

        $this->requestOptions['timeout'] = $this->timeout;

        if (isset($this->requestOptions['query'])) {
            $this->requestOptions['query'] = array_merge($this->requestOptions['query'], $this->queryData);
        } else {
            $this->requestOptions['query'] = $this->queryData;
        }

        if (isset($this->requestOptions['headers'])) {
            $this->requestOptions['headers'] = array_merge($this->requestOptions['headers'], $this->headers);
        } else {
            $this->requestOptions['headers'] = $this->headers;
        }

        if (count($this->cookie)) {
            $jar                             = \GuzzleHttp\Cookie\CookieJar::fromArray($this->cookie, $urlObj->getHost());
            $this->requestOptions['cookies'] = $jar;
        }

        if ($this->proxy) {
            $this->requestOptions['proxy'] = "socks5h://" . $this->proxy;
        }

        $toPostData = 0;
        if (count($this->uploadData)) {
            $toPostData++;
        }

        if (count($this->postData)) {
            $toPostData++;
        }

        if (count($this->jsonData)) {
            $toPostData++;
        }

        if ($toPostData > 1) {
            throw new \Exception('参数错误 : 同一次请求中 postData,uploadData,jsonData 仅可配置其中一项',);
        }

        if (count($this->uploadData)) {
            $options = [];
            foreach ($this->uploadData as $k => $v) {
                $options[] = [
                    'contents' => Utils::tryFopen(realpath($v['contents']), 'r'),
                    'name'     => $v['name'],
                    'filename' => $v['filename'],
                    'headers'  => $v['headers'],
                ];
            }
            $this->requestOptions['multipart'] = $options;
            $this->setMethod('POST');
        }

        if (count($this->postData)) {
            if (isset($this->requestOptions['form_params'])) {
                $this->requestOptions['form_params'] = array_merge($this->requestOptions['form_params'], $this->postData);
            } else {
                $this->requestOptions['form_params'] = $this->postData;
            }
            $this->setMethod('POST');
        }

        if (count($this->jsonData)) {
            $this->setMethod('POST');
            $this->requestOptions['json'] = $this->jsonData;
        }
    }

    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function addUploadFile(string $localfile, string $field, string $fileSaveName = '', array $header = []): static
    {
        $options            = [
            'contents' => $localfile,
            'name'     => $field,
            'filename' => $fileSaveName ? $fileSaveName : pathinfo($localfile, PATHINFO_BASENAME),
            'headers'  => $header,
        ];
        $this->uploadData[] = $options;

        return $this;
    }

    public function addQuery($kv): static
    {
        foreach ($kv as $k => $v) {
            $this->queryData[$k] = $v;
        }

        return $this;
    }

    public function removeQuery($name): static
    {
        if (isset($this->queryData[$name])) {
            unset($this->queryData[$name]);
        }

        return $this;
    }

    public function addPostData($kv): static
    {
        foreach ($kv as $k => $v) {
            $this->postData[$k] = $v;
        }

        return $this;
    }

    public function removePostData($name): static
    {
        if (isset($this->postData[$name])) {
            unset($this->postData[$name]);
        }

        return $this;
    }

    public function setJsonData($data): static
    {
        $this->jsonData = $data;

        return $this;
    }

    public function addHeader($kv): static
    {
        foreach ($kv as $k => $v) {
            $this->headers[$k] = $v;
        }

        return $this;
    }

    public function removeHeader($name): static
    {
        if (isset($this->headers[$name])) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    public function addCookie($kv): static
    {
        foreach ($kv as $k => $v) {
            $this->cookie[$k] = $v;
        }

        return $this;
    }

    public function removeCookie($name): static
    {
        if (isset($this->cookie[$name])) {
            unset($this->cookie[$name]);
        }

        return $this;
    }

    public function setProxy(string $proxy): static
    {
        $this->proxy = $proxy;

        return $this;
    }
}
