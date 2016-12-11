<?php

namespace LucasVscn\Snowflake;

interface IdGenerator
{
    /**
     * @return string - biginteger that represents generated id.
     */
    public function nextId();
}
