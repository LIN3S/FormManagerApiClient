<?php
/*
 * This file is part of the FormManagerAPIClient project
 *
 * Copyright (c) 2017-present LIN3S <info@lin3s.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace LIN3S\FormManagerApiClient;

use \GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\TransferException;

class Client
{
    private $client;
    private $uri;
    private $username;
    private $password;
    private $token;
    private $tokenRenewTries;

    public function __construct(GuzzleClient $client, string $uri, string $username, string $password)
    {
        $this->client = $client;
        $this->uri = $uri;
        $this->username = $username;
        $this->password = $password;
        $this->token = null;
        $this->tokenRenewTries = 0;

        $this->generateAuthenticationToken();
    }

    public function postFormRecord(string $formSlug, array $fields) : array
    {
        $config = [
            'form_params' => $fields,
        ];

        return $this->post('/api/forms/' . $formSlug . '/records', $config);
    }

    public function getForm(string $formSlug) : array
    {
        return $this->get('/api/forms/' . $formSlug);
    }

    public function getFormRecord(string $formSlug, string $formRecordId) : array
    {
        return $this->get('/api/forms/' . $formSlug . '/records/' . $formRecordId);
    }

    private function post(string $endPoint, array $config) : array
    {
        $config = array_merge($config, $this->authToken());
        try {
            $response = $this->client->post($this->uri . $endPoint, $config);
        } catch (TransferException $exception) {
            if ($exception->getCode() === '401') {
                $this->renewAuthenticationToken();

                return $this->post($endPoint, $config);
            }

            $response = $exception->getResponse();
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    private function get(string $endPoint) : array
    {
        try {
            $response = $this->client->get($this->uri . $endPoint, $this->authToken());
        } catch (TransferException $exception) {
            if ($exception->getCode() === '401') {
                $this->renewAuthenticationToken();

                return $this->get($endPoint);
            }

            $response = $exception->getResponse();
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    private function generateAuthenticationToken() : void
    {
        $response = $this->post('/api/login_check', [
            'json' => [
                'username' => $this->username,
                'password' => $this->password,
            ],
        ]);

        if (!array_key_exists('token', $response)) {
            throw new \Exception('Bad credentials');
        }

        $this->token = $response['token'];
        $this->tokenRenewTries = 0;
    }

    private function renewAuthenticationToken() : void
    {
        $this->tokenRenewTries++;
        if ($this->tokenRenewTries === 2) {
            throw new \Exception('Authentication token cannot be renew, check that the credentials are valid');
        }

        $this->generateAuthenticationToken();
    }

    private function authToken() : array
    {
        return [
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $this->token),
            ],
        ];
    }
}
