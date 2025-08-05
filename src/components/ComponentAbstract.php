<?php
namespace robot\components;

use robot\Tools\Debug;


abstract class ComponentAbstract implements ComponentInterface
{
    private $id;
    private $nextId;

    public function __construct($id, $nextId)
    {
        $this->setId($id);
        $this->setNextId($nextId);
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setNextId($nextId)
    {
        $this->nextId = $nextId;
    }

    public function getNextId()
    {
        return $this->nextId;
    }

    public function getComponentName()
    {
        return strtolower(str_replace('\\','',substr(get_class($this), strrpos(get_class($this), '\\'))));
    }

    public function getParameters($name)
    {
        return isset($this->$name)?$this->$name:"";
    }

}