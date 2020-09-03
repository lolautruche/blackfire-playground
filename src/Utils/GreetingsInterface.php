<?php

namespace App\Utils;

interface GreetingsInterface
{
    public function phrase(string $greeting, string $extra): string;
}