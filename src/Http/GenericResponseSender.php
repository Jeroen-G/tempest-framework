<?php

declare(strict_types=1);

namespace Tempest\Http;

use Generator;

final readonly class GenericResponseSender implements ResponseSender
{
    public function send(Response $response): Response
    {
        $response = $this->prepareResponse($response);

        ob_start();

        $this->sendHeaders($response);
        ob_flush();

        $this->sendContent($response);
        ob_end_flush();

        return $response;
    }

    private function sendHeaders(Response $response): void
    {
        // TODO: Handle SAPI/FastCGI
        if (headers_sent()) {
            return;
        }

        foreach ($response->getHeaders() as $key => $header) {
            foreach ($header->values as $value) {
                header("{$key}: {$value}");
            }
        }

        http_response_code($response->getStatus()->value);
    }

    private function sendContent(Response $response): void
    {
        $body = $response->getBody();

        if ($body instanceof Generator) {
            foreach ($body as $content) {
                echo $content;
                ob_flush();
            }
        } else {
            echo $body;
        }
    }

    private function prepareResponse(Response $response): Response
    {
        $body = $response->getBody();

        if (is_array($body)) {
            $response->addHeader('Content-Type', 'application/json');
            $response->setBody(json_encode($body));
        }

        return $response;
    }
}
