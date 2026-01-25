<?php

namespace Tests\Unit\Model;

use Fyennyi\AlertsInUa\Model\XmlSerializableTrait;
use PHPUnit\Framework\TestCase;
use JsonSerializable;

class DummyXmlModel implements JsonSerializable
{
    use XmlSerializableTrait;

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function jsonSerialize() : array
    {
        return $this->data;
    }
}

class NestedJsonModel implements JsonSerializable
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function jsonSerialize() : array
    {
        return ['name' => $this->name];
    }
}

class XmlSerializableTraitTest extends TestCase
{
    public function testToXmlSimpleArray() : void
    {
        $model = new DummyXmlModel([
            'id' => 123,
            'title' => 'Test Title'
        ]);

        $xml = $model->toXml('test');

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<test>', $xml);
        $this->assertStringContainsString('<id>123</id>', $xml);
        $this->assertStringContainsString('<title>Test Title</title>', $xml);
        $this->assertStringContainsString('</test>', $xml);
    }

    public function testToXmlNumericKeys() : void
    {
        $model = new DummyXmlModel([
            'items' => ['first', 'second']
        ]);

        $xml = $model->toXml();

        $this->assertStringContainsString('<items>', $xml);
        $this->assertStringContainsString('<item>first</item>', $xml);
        $this->assertStringContainsString('<item>second</item>', $xml);
    }

    public function testToXmlNestedArray() : void
    {
        $model = new DummyXmlModel([
            'parent' => [
                'child' => 'value'
            ]
        ]);

        $xml = $model->toXml();

        $this->assertStringContainsString('<parent><child>value</child></parent>', $xml);
    }

    public function testToXmlWithJsonSerializableObject() : void
    {
        $nested = new NestedJsonModel('Nested Name');
        $model = new DummyXmlModel([
            'object' => $nested
        ]);

        $xml = $model->toXml();

        $this->assertStringContainsString('<object><name>Nested Name</name></object>', $xml);
    }

    public function testToXmlHandlesSpecialCharacters() : void
    {
        $model = new DummyXmlModel([
            'text' => 'Me & You <Test>'
        ]);

        $xml = $model->toXml();

        $this->assertStringContainsString('<text>Me &amp; You &lt;Test&gt;</text>', $xml);
    }

    public function testToXmlHandlesNullAndScalar() : void
    {
        $model = new DummyXmlModel([
            'null_val' => null,
            'bool_true' => true,
            'bool_false' => false,
            'float' => 1.5
        ]);

        $xml = $model->toXml();

        $this->assertStringContainsString('<null_val></null_val>', $xml);
        $this->assertStringContainsString('<bool_true>1</bool_true>', $xml);
        $this->assertStringContainsString('<bool_false></bool_false>', $xml);
        $this->assertStringContainsString('<float>1.5</float>', $xml);
    }
}
