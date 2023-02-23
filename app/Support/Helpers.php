<?php

if (! function_exists('home_path')) {
    /**
     * @param  string $path
     * @return string
     */
    function home_path(string $path = null) : string
    {
        $home = getenv('HOME');

        if (! $path) {
            return $home;
        }

        return $home . '/' . ltrim($path, '/');
    }
}
