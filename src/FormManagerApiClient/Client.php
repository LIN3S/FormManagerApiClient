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

    public function __construct(GuzzleClient $client, string $uri, string $username, string $password)
    {
        $this->client = $client;
        $this->uri = $uri;
        $this->username = $username;
        $this->password = $password;
    }

    public function postFormRecord(string $formSlug, array $fields) : array
    {
        $config = [
            'form_params' => $fields
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

    public function getFormRecords(string $formSlug) : array
    {
        return $this->get('/api/forms/' . $formSlug . '/records');
    }

    private function post(string $endPoint, array $config) : array
    {
        try {
            $config = array_merge($config, $this->auth());
            $response = $this->client->post($this->uri . $endPoint, $config);
        } catch (TransferException $exception) {
            $response = $exception->getResponse();
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    private function get(string $endPoint) : array
    {
        try {
            $response = $this->client->get($this->uri . $endPoint, $this->auth());
        } catch (TransferException $exception) {
            $response = $exception->getResponse();
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    private function auth() : array
    {
        return ['auth' => [
            $this->username,
            $this->password
        ]];
    }
}
