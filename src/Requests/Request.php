<?php

namespace Rackbeat\TolgeeSync\Requests;

use Rackbeat\TolgeeSync\Exceptions\TolgeeException;

class Request
{
    protected $path;
    protected $baseUrl;
    protected $headers = [];
    protected $queryParams = [];
    protected $requestData = [];
    protected $timeout = 30;

    public function __construct(string $baseUrl, array $headers = [])
    {
        $this->baseUrl = $baseUrl;
        $this->headers = $headers;
    }

    public function addHeader(string $header)
    {
        $this->headers[] = $header;
    }

    public function addQueryParam(string $key, $value)
    {
        $this->queryParams[$key] = $value;
    }

    public function addData(array $data): void
    {
        $this->requestData = json_encode($data);
    }

    public function get()
    {
        $url = $this->buildUrl();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);

        $output   = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw TolgeeException::apiRequestFailed($error);
        }

        curl_close($curl);

        if ($httpCode >= 400) {
            throw TolgeeException::apiRequestFailed("HTTP {$httpCode}: {$output}", $httpCode);
        }

        return json_decode($output);
    }

    public function post(array $data = [])
    {
        $this->addData($data);
        $headers = $this->getHeaders();
        if (! in_array('Content-Type: application/json', $headers, true)) {
            $headers[] = 'Content-Type: application/json';
        }
        $curl = curl_init($this->buildUrl());

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->requestData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw TolgeeException::apiRequestFailed($error);
        }

        curl_close($curl);

        // 409 = key already exists in Tolgee — return decoded so caller can handle gracefully
        if ($httpCode >= 400 && $httpCode !== 409) {
            throw TolgeeException::apiRequestFailed("HTTP {$httpCode}: {$response}", $httpCode);
        }

        return json_decode($response);
    }

    public function put(array $data = [])
    {
        $url = $this->buildUrl();

        $this->addData($data);
        $headers = $this->getHeaders();
        if (! in_array('Content-Type: application/json', $headers, true)) {
            $headers[] = 'Content-Type: application/json';
        }
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->requestData);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw TolgeeException::apiRequestFailed($error);
        }

        curl_close($curl);

        if ($httpCode >= 400) {
            throw TolgeeException::apiRequestFailed("HTTP {$httpCode}: {$response}", $httpCode);
        }

        return json_decode($response);
    }

    protected function buildUrl()
    {
        if (!empty($this->queryParams)) {
            return $this->getURL() . '?' . http_build_query($this->queryParams);
        }

        return $this->getURL();
    }

    protected function getURL(): string
    {
        return $this->baseUrl . $this->path;
    }

    protected function getHeaders()
    {
        return $this->headers;
    }
}
