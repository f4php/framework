<?php

declare(strict_types=1);

namespace F4\Core\ResponseEmitter;

use ErrorException;

use F4\Core\CoreApiInterface;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\ResponseEmitter\ResponseEmitterInterface;

abstract class AbstractResponseEmitter implements ResponseEmitterInterface
{
    public function __construct(protected CoreApiInterface $f4) {}
    private function checkForNoPreviousOutput(): void
    {
        $filename = null;
        $line     = null;
        if ($this->headersSent($filename, $line)) {
            throw new ErrorException("Headers already set {$filename}:{$line}");
        }

        if (ob_get_level() > 0 && ob_get_length() > 0) {
            throw new ErrorException("Output buffer not clean");
        }
    }
    protected function emitStatusLine(ResponseInterface $response): void
    {
        $reasonPhrase = $response->getReasonPhrase();
        $statusCode   = $response->getStatusCode();

        $this->header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $statusCode,
            $reasonPhrase ? " {$reasonPhrase}" : ''
        ), true, $statusCode);
    }
    protected function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            $name  = $this->filterHeader($header);
            $first = $name !== 'Set-Cookie';
            foreach ($values as $value) {
                $this->header(sprintf(
                    '%s: %s',
                    $name,
                    $value
                ), $first, $statusCode);
                $first = false;
            }
        }
    }
    protected function filterHeader(string $header): string
    {
        return \ucwords($header, '-');
    }
    protected function headersSent(?string &$filename = null, ?int &$line = null): bool
    {
        return \headers_sent($filename, $line);
    }
    protected function header(string $headerName, bool $replace, int $statusCode): void
    {
        \header($headerName, $replace, $statusCode);
    }
    protected function emitBody(ResponseInterface $response): void
    {
        echo $response->getBody();
    }
    public function emit(ResponseInterface $response, ?RequestInterface $request=null): bool {
        $this->checkForNoPreviousOutput();
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        $this->emitBody($response);
        return true;
    }
}