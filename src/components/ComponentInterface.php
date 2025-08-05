<?php
namespace robot\components;

use robot\Bot;

interface ComponentInterface
{
    public function setId($id);    
    public function getId();
    public function setNextId($nextId);
    public function getNextId();
    public function do(Bot &$bot):int;
    public function getComponentName();
    public function getParameters($name);

}

