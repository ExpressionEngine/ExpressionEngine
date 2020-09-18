<?php

$aliases = [
    
    Illuminate\Support\Arr::class => IlluminateAgnostic\Arr\Support\Arr::class,
    Illuminate\Support\Collection::class => IlluminateAgnostic\Arr\Support\Collection::class,
    Illuminate\Support\Carbon::class => IlluminateAgnostic\Arr\Support\Carbon::class,
    Illuminate\Support\HigherOrderCollectionProxy::class => IlluminateAgnostic\Arr\Support\HigherOrderCollectionProxy::class,
    Illuminate\Support\HtmlString::class => IlluminateAgnostic\Arr\Support\HtmlString::class,
    Illuminate\Support\Debug\Dumper::class => IlluminateAgnostic\Arr\Support\Debug\Dumper::class,
    Illuminate\Support\Debug\HtmlDumper::class => IlluminateAgnostic\Arr\Support\Debug\HtmlDumper::class,
    Illuminate\Support\Enumerable::class => IlluminateAgnostic\Arr\Support\Enumerable::class,
    Illuminate\Support\LazyCollection::class => IlluminateAgnostic\Arr\Support\LazyCollection::class,

];

foreach ($aliases as $illuminate => $tighten) {
    if (
        class_exists($illuminate) &&
        !interface_exists($illuminate) &&
        !trait_exists($illuminate)
    ) {
        class_alias($illuminate, $tighten);
    }
}
