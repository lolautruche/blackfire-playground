<?php

namespace App\Utils;

class Greetings
{
    public function phrase($greeting, $extra)
    {
        usleep(500000);
        return sprintf('%s %s', $greeting, $extra);
    }
}