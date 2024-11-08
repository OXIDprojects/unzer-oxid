<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\Unzer\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use UnzerSDK\Services\ValueService;

/**
 * We only use this for requests the unzer sdk currently does not cover
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class ApiClient
{
    private Client $client;

    private DebugHandler $logger;

    private ModuleSettings $moduleSettings;

    /**
     * @var string[]
     */
    private array $headers;

    private string $baseUrl = 'https://api.unzer.com/v1/';

    /**
     * @param ModuleSettings $moduleSettings
     * @param DebugHandler $logger
     */
    public function __construct(
        ModuleSettings $moduleSettings,
        DebugHandler $logger
    ) {
        $this->client = new Client();
        $this->logger = $logger;
        $this->moduleSettings = $moduleSettings;

        $this->headers = [
            'Accept' => '*/*',
            'Authorization' => 'Basic ' . base64_encode($this->moduleSettings->getStandardPrivateKey() . ':')
        ];
    }

    /**
     * @throws GuzzleException|JsonException
     */
    public function requestApplePayPaymentCert(string $certificateId): ?ResponseInterface
    {
        return $this->request('keypair/applepay/certificates/' . $certificateId);
    }

    /**
     * @throws GuzzleException
     */
    public function requestApplePayPaymentKey(string $keyId): ?ResponseInterface
    {
        return $this->request('keypair/applepay/privatekeys/' . $keyId);
    }

    public function uploadApplePayPaymentKey(string $key): ?ResponseInterface
    {
        return $this->request('keypair/applepay/privatekeys', 'POST', [
            'format' => 'PEM',
            'type' => 'private-key',
            'certificate' => $key,
        ]);
    }

    /**
     * @param string $privateKeyId (getting from upload of ApplePayPaymentKey)
     * @throws GuzzleException
     * @throws JsonException
     */
    public function uploadApplePayPaymentCertificate(string $certificate, string $privateKeyId): ?ResponseInterface
    {
        return $this->request('keypair/applepay/certificates', 'POST', [
            'format' => 'PEM',
            'type' => 'certificate',
            'private-key' => $privateKeyId,
            'certificate' => $certificate,
        ]);
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function activateApplePayPaymentCertificate(string $certificateId): ?ResponseInterface
    {
        return $this->request('keypair/applepay/certificates/' . $certificateId . '/activate', 'POST');
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $body
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \JsonException
     */
    private function request(
        string $url,
        string $method = 'GET',
        array $body = []
    ): ?ResponseInterface {
        $response = null;
        $options = [];
        $options['headers'] = $this->headers;
        if ($body) {
            $options['headers']['Content-Type'] = 'application/json';
            $options['body'] = json_encode($body, JSON_THROW_ON_ERROR);
        }

        try {
            $response = $this->client->request($method, $this->baseUrl . $url, $options);
        } catch (GuzzleException $e) {
            if ($this->moduleSettings->isDebugMode()) {
                // mask auth string
                $authHeader = explode(' ', $options['headers']['Authorization']);
                $authHeader[1] = ValueService::maskValue($authHeader[1]);
                $options['headers']['Authorization'] = implode(' ', $authHeader);

                // log request
                $this->logger->log($method . ': ' . $url);
                $this->logger->log(
                    'Headers: ' . json_encode($options['headers'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
                );
                $this->logger->log('ErrorMessage: ' . $e->getMessage());
            }
        }

        return $response;
    }
}
