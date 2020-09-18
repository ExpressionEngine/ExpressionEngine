# IlluminateAgnostic \ Arr
 
<p align="center">
    <a href="https://packagist.org/packages/pragmarx/ia-arr"><img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/pragmarx/ia-arr.svg?style=flat-square"></a>
    <a href="LICENSE"><img alt="License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square"></a>
    <a href="https://scrutinizer-ci.com/g/antonioribeiro/ia-arr/?branch=master"><img alt="Code Quality" src="https://img.shields.io/scrutinizer/g/antonioribeiro/ia-arr.svg?style=flat-square"></a>
    <a href="https://travis-ci.org/antonioribeiro/ia-arr"><img alt="Build" src="https://img.shields.io/travis/antonioribeiro/ia-arr.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/pragmarx/ia-arr"><img alt="Downloads" src="https://img.shields.io/packagist/dt/pragmarx/ia-arr.svg?style=flat-square"></a>
</p>
<p align="center">
    <a href="https://scrutinizer-ci.com/g/antonioribeiro/ia-arr/?branch=master"><img alt="Coverage" src="https://img.shields.io/scrutinizer/coverage/g/antonioribeiro/ia-arr.svg?style=flat-square"></a>
    <a href="https://styleci.io/repos/119604199"><img alt="StyleCI" src="https://styleci.io/repos/119604199/shield"></a>
    <!-- <a href="https://insight.sensiolabs.com/projects/156fbef1-b03f-4fca-ba97-57874b7a35bf"><img alt="SensioLabsInsight" src="https://img.shields.io/sensiolabs/i/156fbef1-b03f-4fca-ba97-57874b7a35bf.svg?style=flat-square"></a> -->
    <a href="https://travis-ci.org/antonioribeiro/ia-arr"><img alt="PHP" src="https://img.shields.io/badge/PHP-7.0%20--%207.3-brightgreen.svg?style=flat-square"></a>
</p>

This package is an extraction of the [Laravel's Illuminate\Support\Arr](https://github.com/laravel/framework/blob/5.5/src/Illuminate/Support/Arr.php) class, including all helpers, repackaged to be agnostic and available to any PHP project. 

It has its own namespace **(IlluminateAgnostic\Arr)**, so you can use it even on Laravel apps without risking a namespace conflict.

You can find some documentation on the available helpers here: https://laravel.com/docs/5.5/helpers.

## Install

Via Composer

``` bash
$ composer require pragmarx/ia-arr
```

## Usage

``` php
use IlluminateAgnostic\Arr\Support\Arr;

$array = ['joe@example.com' => 'Joe', 'jane@localhost' => 'Jane'];

echo Arr::pull($array, 'joe@example.com');
``` 

Should return 

```
Joe
``` 

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email acr@antoniocarlosribeiro.com instead of using the issue tracker.

## Credits

- This package is an extraction of The Laravel Framework, created by [Taylor Otwell](https://twitter.com/taylorotwell)
- Package creator [Antonio Carlos Ribeiro](https://twitter.com/iantonioribeiro)
- [Contributors](https://github.com/antonioribeiro/ia-arr/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
