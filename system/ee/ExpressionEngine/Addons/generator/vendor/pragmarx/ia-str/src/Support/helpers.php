<?php

use IlluminateAgnostic\Str\Support\Arr;
use IlluminateAgnostic\Str\Support\Str;
use IlluminateAgnostic\Str\Support\Collection;
use IlluminateAgnostic\Str\Support\Debug\Dumper;

if (!class_exists(Illuminate\Support\Collection::class)) {
    if (!function_exists('collect')) {
        /**
         * Create a collection from the given value.
         *
         * @param  mixed  $value
         * @return \IlluminateAgnostic\StrAgnostic\Str\Support\Collection|\IlluminateAgnostic\Str\Support\Collection
         */
        function collect($value = null)
        {
            return new Collection($value);
        }
    }

    if (!function_exists('data_get')) {
        /**
         * Get an item from an array or object using "dot" notation.
         *
         * @param  mixed   $target
         * @param  string|array  $key
         * @param  mixed   $default
         * @return mixed
         */
        function data_get($target, $key, $default = null)
        {
            if (is_null($key)) {
                return $target;
            }

            $key = is_array($key) ? $key : explode('.', $key);

            while (!is_null($segment = array_shift($key))) {
                if ($segment === '*') {
                    if ($target instanceof Collection) {
                        $target = $target->all();
                    } elseif (!is_array($target)) {
                        return value($default);
                    }

                    $result = Arr::pluck($target, $key);

                    return in_array('*', $key)
                        ? Arr::collapse($result)
                        : $result;
                }

                if (
                    Arr::accessible($target) &&
                    Arr::exists($target, $segment)
                ) {
                    $target = $target[$segment];
                } elseif (is_object($target) && isset($target->{$segment})) {
                    $target = $target->{$segment};
                } else {
                    return value($default);
                }
            }

            return $target;
        }
    }

    if (!function_exists('value')) {
        /**
         * Return the default value of the given value.
         *
         * @param  mixed  $value
         * @return mixed
         */
        function value($value)
        {
            return $value instanceof Closure ? $value() : $value;
        }
    }

    if (!function_exists('dd')) {
        /**
         * Dump the passed variables and end the script.
         *
         * @param  mixed  $args
         * @return void
         */
        function dd(...$args)
        {
            http_response_code(500);

            foreach ($args as $x) {
                (new Dumper())->dump($x);
            }

            die(1);
        }
    }
}
