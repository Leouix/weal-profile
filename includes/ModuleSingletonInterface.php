<?php

interface ModuleSingletonInterface
{
    public static function instance();
    public function __wakeup();
}