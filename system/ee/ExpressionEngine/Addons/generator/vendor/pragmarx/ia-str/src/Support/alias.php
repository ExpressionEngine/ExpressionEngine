<?php

$aliases = [
    
    Illuminate\Support\Arr::class => IlluminateAgnostic\Str\Support\Arr::class,
    Illuminate\Support\Carbon::class => IlluminateAgnostic\Str\Support\Carbon::class,
    Illuminate\Support\Collection::class => IlluminateAgnostic\Str\Support\Collection::class,
    Illuminate\Support\Debug\Dumper::class => IlluminateAgnostic\Str\Support\Debug\Dumper::class,
    Illuminate\Support\Debug\HtmlDumper::class => IlluminateAgnostic\Str\Support\Debug\HtmlDumper::class,
    Illuminate\Support\HigherOrderCollectionProxy::class => IlluminateAgnostic\Str\Support\HigherOrderCollectionProxy::class,
    Illuminate\Support\HigherOrderTapProxy::class => IlluminateAgnostic\Str\Support\HigherOrderTapProxy::class,
    Illuminate\Support\HtmlString::class => IlluminateAgnostic\Str\Support\HtmlString::class,
    Illuminate\Support\Optional::class => IlluminateAgnostic\Str\Support\Optional::class,
    Illuminate\Support\Pluralizer::class => IlluminateAgnostic\Str\Support\Pluralizer::class,
    Illuminate\Support\Str::class => IlluminateAgnostic\Str\Support\Str::class,
    Illuminate\Support\Enumerable::class => IlluminateAgnostic\Str\Support\Enumerable::class,
    Illuminate\Support\LazyCollection::class => IlluminateAgnostic\Str\Support\LazyCollection::class,

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
