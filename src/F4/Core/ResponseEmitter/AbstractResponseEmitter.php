<?php

declare(strict_types=1);

namespace F4\Core\ResponseEmitter;

use ErrorException;

use F4\Core\CoreApiInterface;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\ResponseEmitter\ResponseEmitterInterface;

use function array_find;
use function array_keys;
use function header;
use function headers_sent;
use function in_array;
use function mb_strtolower;
use function ob_get_level;
use function ob_get_length;
use function sprintf;
use function ucwords;

abstract class AbstractResponseEmitter implements ResponseEmitterInterface
{
    public function __construct(protected CoreApiInterface $f4) {}
    private function checkForNoPreviousOutput(): void
    {
        $filename = null;
        $line = null;
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
        $statusCode = $response->getStatusCode();

        $this->header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $statusCode,
            $reasonPhrase ? " {$reasonPhrase}" : ''
        ), true, $statusCode);
    }
    public function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        foreach ($response->getHeaders() as $header => $values) {
            $name = $this->filterHeaderName($header);
            $first = $name !== 'Set-Cookie';
            foreach ($values as $value) {
                $this->header(sprintf(
                    '%s: %s',
                    $name,
                    $value,
                ), $first, $statusCode);
                $first = false;
            }
        }
    }
    public function shouldEmitBody(ResponseInterface $response): bool
    {
        return !in_array($response->getStatusCode(), [100, 101, 102, 103, 204, 205, 304])
            && (null === array_find(array_keys($response->getHeaders()), function ($name): bool{
                return mb_strtolower($name) === 'location';
            }));
    }
    protected function filterHeaderName(string $header): string
    {
        return ucwords($header, '-');
    }
    protected function headersSent(?string &$filename = null, ?int &$line = null): bool
    {
        return headers_sent($filename, $line);
    }
    protected function header(string $headerName, bool $replace, int $statusCode): void
    {
        header($headerName, $replace, $statusCode);
    }
    protected function emitBody(ResponseInterface $response): void
    {
        echo $response->getBody();
    }
    public function emit(ResponseInterface $response, ?RequestInterface $request = null): bool
    {
        $this->checkForNoPreviousOutput();
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        if ($this->shouldEmitBody($response)) {
            $this->emitBody($response);
        }
        return true;
    }
}