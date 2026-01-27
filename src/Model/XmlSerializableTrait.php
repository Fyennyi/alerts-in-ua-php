<?php

/*
 *
 *     _    _           _       ___       _   _
 *    / \  | | ___ _ __| |_ ___|_ _|_ __ | | | | __ _
 *   / _ \ | |/ _ \ '__| __/ __|| || '_ \| | | |/ _` |
 *  / ___ \| |  __/ |  | |_\__ \| || | | | |_| | (_| |
 * /_/   \_\_|\___|_|   \__|___/___|_| |_|\___/ \__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author Serhii Cherneha
 * @link https://chernega.eu.org/
 *
 *
 */

namespace Fyennyi\AlertsInUa\Model;

use SimpleXMLElement;

/**
 * Trait for XML serialization of models
 */
trait XmlSerializableTrait
{
    /**
     * Converts the object to an XML string
     *
     * @param  string  $root_element  The name of the root XML element
     * @return string                 XML representation
     */
    public function toXml(string $root_element = 'data') : string
    {
        $data = $this->jsonSerialize();
        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><$root_element/>");

        $this->arrayToXml($data, $xml);

        return (string) $xml->asXML();
    }

    /**
     * Recursively adds array data to a SimpleXMLElement
     *
     * @param  array<string|int, mixed>  $data  The data array
     * @param  SimpleXMLElement          $xml   The XML element to add to
     * @return void
     */
    private function arrayToXml(array $data, SimpleXMLElement &$xml) : void
    {
        foreach ($data as $key => $value) {
            $node_key = is_numeric($key) ? 'item' : (string) $key;

            if ($value instanceof \JsonSerializable) {
                $value = $value->jsonSerialize();
            }

            if (is_array($value)) {
                $subnode = $xml->addChild($node_key);
                if ($subnode instanceof SimpleXMLElement) {
                    $this->arrayToXml($value, $subnode);
                }
            } else {
                $text_value = is_scalar($value) || $value === null ? (string) $value : '';
                $xml->addChild($node_key, htmlspecialchars($text_value));
            }
        }
    }
}
