<?php
/**
 * sysMonDash
 *
 * @author    nuxsmin
 * @link      http://cygnux.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@cygnux.org
 *
 * This file is part of sysMonDash.
 *
 * sysMonDash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysMonDash is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysMonDash.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SMD\Storage;

use DOMDocument;
use ReflectionObject;

/**
 * Class XmlHandler para manejo básico de documentos XML
 * @package SMD\Storage
 */
class XmlHandler implements StorageInterface
{
    /**
     * @var mixed
     */
    protected $items = null;
    /**
     * @var string
     */
    protected $file;
    /**
     * @var DOMDocument
     */
    private $Dom;

    /**
     * XmlHandler constructor.
     * @param $file
     */
    public function __construct($file)
    {
        $this->file = $file;
        $this->setDOM();
    }

    /**
     * Crear un nuevo documento XML
     */
    private function setDOM()
    {
        $this->Dom = new DOMDocument();
    }

    /**
     * Cargar un archivo XML
     *
     * @param string $tag
     * @return bool|void
     * @throws \Exception
     */
    public function load($tag = 'root')
    {
        if (!$this->checkSourceFile()) {
            throw new \Exception('No es posible leer/escribir el archivo');
        }

        $this->Dom->load($this->file);

        $nodes = $this->Dom->getElementsByTagName($tag)->item(0)->childNodes;

        foreach ($nodes as $node) {
            /** @var $node \DOMNode*/
            if (is_object($node->childNodes) && $node->childNodes->length > 1){
                foreach($node->childNodes as $child){
                    /** @var $child \DOMNode */

                    if ($child->nodeType == XML_ELEMENT_NODE) {
                        $this->items[$node->nodeName][] = $child->nodeValue;
                    }
                }
            } else {
                $this->items[$node->nodeName] = $node->nodeValue;
            }
        }

        return $this;
    }

    /**
     * Comprobar que el archivo existe y se puede leer/escribir
     *
     * @return bool
     */
    protected function checkSourceFile()
    {
        return is_writable($this->file);
    }

    /**
     * Obtener un elemento del array
     *
     * @param $id
     * @return mixed
     */
    public function __get($id)
    {
        return $this->items[$id];
    }

    /**
     * Guardar el archivo XML
     *
     * @param string $tag
     * @return bool|void
     * @throws \Exception
     */
    public function save($tag = 'root')
    {
        if (is_null($this->items)) {
            throw new \Exception('No hay elementos para guardar');
        }

        $this->Dom->formatOutput = true;

        $root = $this->Dom->createElement($tag);
        $this->Dom->appendChild($root);

        foreach ($this->analyzeItems() as $key => $value) {
            $keyNode = $this->Dom->createElement($key);

            if (is_array($value)){
                foreach($value as $arrayVal){
                    $arrayNode = $this->Dom->createElement('item');
                    $arrayNode->appendChild($this->Dom->createTextNode(trim($arrayVal)));
                    $keyNode->appendChild($arrayNode);
                }
            } else {
                $keyNode->appendChild($this->Dom->createTextNode($value));
            }

            $root->appendChild($keyNode);
        }

        $this->Dom->save($this->file);

        return $this;
    }

    /**
     * Devolver los elementos cargados
     *
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Establecer los elementos
     *
     * @param $items
     * @return mixed
     */
    public function setItems($items)
    {
        $this->items = $items;
    }

    /**
     * Analizar el tipo de elementos
     *
     * @return array|mixed
     */
    protected function analyzeItems (){
        if (is_array($this->items)){
            return $this->items;
        } elseif (is_object($this->items)){
            return $this->analyzeObject();
        }

        return [];

    }

    /**
     * Analizar un elemento del tipo objeto
     *
     * @return array
     */
    protected function analyzeObject()
    {
        $items = [];
        $Reflection = new ReflectionObject($this->items);

        foreach($Reflection->getProperties() as $property){
            $property->setAccessible(true);
            $items[$property->getName()] = $property->getValue($this->items);
            $property->setAccessible(false);
        }

        return $items;
    }
}