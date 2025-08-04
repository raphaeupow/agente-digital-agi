<?php

namespace robot\components\decision;


class Alternative
{
    private $nextId;
    private $words;

    public function __construct($nextId, $words)
    {
        $this->nextId = $nextId;
        $this->words = $words;
    }

    public function getNextId()
    {
        return $this->nextId;
    }

    public function getWords() {
        return $this->words;
    }

}
