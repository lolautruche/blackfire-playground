<?php

namespace App\Utils;

class Greetings implements GreetingsInterface
{
    public function phrase(string $greeting, string $extra): string
    {
//        usleep(500000);
        return sprintf('%s %s', $greeting, $extra);
    }
}