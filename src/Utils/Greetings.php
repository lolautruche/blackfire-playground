<?php

namespace App\Utils;

class Greetings implements GreetingsInterface
{
    public function phrase(string $greeting, string $extra): string
    {
        \BlackfireProbe::addMarker("Greetings with '$greeting' and '$extra'");
        usleep(500000);
        return sprintf('%s %s', $greeting, $extra);
    }
}