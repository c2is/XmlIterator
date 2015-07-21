<?php

namespace C2iS\Component\Xml;

/**
 * Iterate over XML from a string or a DomDocument. Uses XPath to identify which nodes should be iterated upon.
 *
 * Class XmlIterator
 * @package C2iS\Component\Xml
 */
class XmlIterator implements \Iterator, \Countable
{
    /** @var string */
    protected $content;

    /** @var \DOMDocument */
    protected $currentContent;

    /** @var string */
    protected $xpath;

    /** @var array */
    protected $namespaces;

    /**
     * @param string|\DOMDocument $xml XML to to iterate over
     * @param string $xpath XPath defining which nodes to iterate over
     * @param array $namespaces Associative array used to define namespaces for XPath
     */
    public function __construct($xml, $xpath, $namespaces = array())
    {
        if ($xml instanceof \DOMDocument) {
            $this->content = $xml->saveXML();
        } else {
            $this->content = $xml;
        }

        $this->xpath = $xpath;
        $this->namespaces = $namespaces;
        $this->rewind();
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $dom = new \DOMDocument();
        set_error_handler(function() { /* ignore warning errors from DomDocument::loadXML*/ }, E_WARNING);
        @$dom->loadXML($this->content);
        restore_error_handler();
        $this->currentContent = $dom;
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        $currentElement = $this->getCurrentElement();

        return $currentElement ? $this->currentContent->saveXML($currentElement) : null;
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $currentElement = $this->getCurrentElement();
        $currentElement->parentNode->removeChild($currentElement);
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return (boolean) $this->getCurrentElement();
    }

    /**
     * @return \DOMNode|null
     */
    public function getCurrentElement()
    {
        $xpath = new \DOMXPath($this->currentContent);

        foreach ($this->namespaces as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }

        $nodes = $xpath->query($this->getXPath());

        return $nodes->length ? $nodes->item(0) : null;
    }

    /**
     * @return string
     */
    public function getXPath()
    {
        $xpath  = $this->xpath;
        $suffix = '[1]';

        if (strlen($xpath) - strlen($suffix) !== strrpos($xpath, $suffix)) {
            $xpath .= $suffix;
        }

        return $xpath;
    }

    /**
     * @return integer
     */
    public function count()
    {
        $xpath = new \DOMXPath($this->currentContent);

        foreach ($this->namespaces as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }

        $nodes = $xpath->query(rtrim($this->xpath, '[1]'));

        return $nodes->length;
    }

    /**
     * @param array $namespaces
     * @return $this
     */
    public function setNamespaces(array $namespaces)
    {
        $this->namespaces = $namespaces;

        return $this;
    }

    /**
     * @param string $prefix
     * @param string $namespace
     * @return $this
     */
    public function addNamespace($prefix, $namespace)
    {
        $this->namespaces[$prefix] = $namespace;

        return $this;
    }
}
