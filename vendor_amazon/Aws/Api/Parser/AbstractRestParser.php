<?php

namespace YaySMTP\Aws3\Aws\Api\Parser;

use YaySMTP\Aws3\Aws\Api\DateTimeResult;
use YaySMTP\Aws3\Aws\Api\Shape;
use YaySMTP\Aws3\Aws\Api\StructureShape;
use YaySMTP\Aws3\Aws\Result;
use YaySMTP\Aws3\Aws\CommandInterface;
use YaySMTP\Aws3\Psr\Http\Message\ResponseInterface;
/**
 * @internal
 */
abstract class AbstractRestParser extends \YaySMTP\Aws3\Aws\Api\Parser\AbstractParser
{
    use PayloadParserTrait;
    /**
     * Parses a payload from a response.
     *
     * @param ResponseInterface $response Response to parse.
     * @param StructureShape    $member   Member to parse
     * @param array             $result   Result value
     *
     * @return mixed
     */
    protected abstract function payload(\YaySMTP\Aws3\Psr\Http\Message\ResponseInterface $response, \YaySMTP\Aws3\Aws\Api\StructureShape $member, array &$result);
    public function __invoke(\YaySMTP\Aws3\Aws\CommandInterface $command, \YaySMTP\Aws3\Psr\Http\Message\ResponseInterface $response)
    {
        $output = $this->api->getOperation($command->getName())->getOutput();
        $result = [];
        if ($payload = $output['payload']) {
            $this->extractPayload($payload, $output, $response, $result);
        }
        foreach ($output->getMembers() as $name => $member) {
            switch ($member['location']) {
                case 'header':
                    $this->extractHeader($name, $member, $response, $result);
                    break;
                case 'headers':
                    $this->extractHeaders($name, $member, $response, $result);
                    break;
                case 'statusCode':
                    $this->extractStatus($name, $response, $result);
                    break;
            }
        }
        if (!$payload && $response->getBody()->getSize() > 0 && count($output->getMembers()) > 0) {
            // if no payload was found, then parse the contents of the body
            $this->payload($response, $output, $result);
        }
        return new \YaySMTP\Aws3\Aws\Result($result);
    }
    private function extractPayload($payload, \YaySMTP\Aws3\Aws\Api\StructureShape $output, \YaySMTP\Aws3\Psr\Http\Message\ResponseInterface $response, array &$result)
    {
        $member = $output->getMember($payload);
        if ($member instanceof StructureShape) {
            // Structure members parse top-level data into a specific key.
            $result[$payload] = [];
            $this->payload($response, $member, $result[$payload]);
        } else {
            // Streaming data is just the stream from the response body.
            $result[$payload] = $response->getBody();
        }
    }
    /**
     * Extract a single header from the response into the result.
     */
    private function extractHeader($name, \YaySMTP\Aws3\Aws\Api\Shape $shape, \YaySMTP\Aws3\Psr\Http\Message\ResponseInterface $response, &$result)
    {
        $value = $response->getHeaderLine($shape['locationName'] ?: $name);
        switch ($shape->getType()) {
            case 'float':
            case 'double':
                $value = (double) $value;
                break;
            case 'long':
                $value = (int) $value;
                break;
            case 'boolean':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'blob':
                $value = base64_decode($value);
                break;
            case 'timestamp':
                try {
                    $value = new \YaySMTP\Aws3\Aws\Api\DateTimeResult($value);
                    break;
                } catch (\Exception $e) {
                    // If the value cannot be parsed, then do not add it to the
                    // output structure.
                    return;
                }
            case 'string':
                if ($shape['jsonvalue']) {
                    $value = $this->parseJson(base64_decode($value));
                }
                break;
        }
        $result[$name] = $value;
    }
    /**
     * Extract a map of headers with an optional prefix from the response.
     */
    private function extractHeaders($name, \YaySMTP\Aws3\Aws\Api\Shape $shape, \YaySMTP\Aws3\Psr\Http\Message\ResponseInterface $response, &$result)
    {
        // Check if the headers are prefixed by a location name
        $result[$name] = [];
        $prefix = $shape['locationName'];
        $prefixLen = strlen($prefix);
        foreach ($response->getHeaders() as $k => $values) {
            if (!$prefixLen) {
                $result[$name][$k] = implode(', ', $values);
            } elseif (stripos($k, $prefix) === 0) {
                $result[$name][substr($k, $prefixLen)] = implode(', ', $values);
            }
        }
    }
    /**
     * Places the status code of the response into the result array.
     */
    private function extractStatus($name, \YaySMTP\Aws3\Psr\Http\Message\ResponseInterface $response, array &$result)
    {
        $result[$name] = (int) $response->getStatusCode();
    }
}
