<?php

namespace YaySMTP\Aws3\Aws\Api;

/**
 * Represents a list shape.
 */
class ListShape extends \YaySMTP\Aws3\Aws\Api\Shape
{
    private $member;
    public function __construct(array $definition, \YaySMTP\Aws3\Aws\Api\ShapeMap $shapeMap)
    {
        $definition['type'] = 'list';
        parent::__construct($definition, $shapeMap);
    }
    /**
     * @return Shape
     * @throws \RuntimeException if no member is specified
     */
    public function getMember()
    {
        if (!$this->member) {
            if (!isset($this->definition['member'])) {
                throw new \RuntimeException('No member attribute specified');
            }
            $this->member = \YaySMTP\Aws3\Aws\Api\Shape::create($this->definition['member'], $this->shapeMap);
        }
        return $this->member;
    }
}
