<?php

use IlluminateAgnostic\Arr\Support\Arr;
use IlluminateAgnostic\Arr\Support\Collection;
use IlluminateAgnostic\Arr\Support\Debug\Dumper;

if (!class_exists(Illuminate\Support\Collection::class)) {
    if (!function_exists('collect')) {
        /**
         * Create a collection from the given value.
         *
         * @param  mixed  $value
         * @return \IlluminateAgnostic\ArrAgnostic\Arr\Support\Collection
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

                    return in_array('*', $key) ? Arr::collapse($result) : $result;
                }

                if (Arr::accessible($target) && Arr::exists($target, $segment)) {
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

    if (!function_exists('data_set')) {
        /**
         * Set an item on an array or object using dot notation.
         *
         * @param  mixed  $target
         * @param  string|array  $key
         * @param  mixed  $value
         * @param  bool  $overwrite
         * @return mixed
         */
        function data_set(&$target, $key, $value, $overwrite = true)
        {
            $segments = is_array($key) ? $key : explode('.', $key);

            if (($segment = array_shift($segments)) === '*') {
                if (!Arr::accessible($target)) {
                    $target = [];
                }

                if ($segments) {
                    foreach ($target as &$inner) {
                        data_set($inner, $segments, $value, $overwrite);
                    }
                } elseif ($overwrite) {
                    foreach ($target as &$inner) {
                        $inner = $value;
                    }
                }
            } elseif (Arr::accessible($target)) {
                if ($segments) {
                    if (!Arr::exists($target, $segment)) {
                        $target[$segment] = [];
                    }

                    data_set($target[$segment], $segments, $value, $overwrite);
                } elseif ($overwrite || !Arr::exists($target, $segment)) {
                    $target[$segment] = $value;
                }
            } elseif (is_object($target)) {
                if ($segments) {
                    if (!isset($target->{$segment})) {
                        $target->{$segment} = [];
                    }

                    data_set($target->{$segment}, $segments, $value, $overwrite);
                } elseif ($overwrite || !isset($target->{$segment})) {
                    $target->{$segment} = $value;
                }
            } else {
                $target = [];

                if ($segments) {
                    data_set($target[$segment], $segments, $value, $overwrite);
                } elseif ($overwrite) {
                    $target[$segment] = $value;
                }
            }

            return $target;
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
}
