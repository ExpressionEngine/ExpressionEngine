# ExpressionEngine

**These repositories must remain private and all work under NDA.**

## Naming Branches

ExpressionEngine follows the Git Flow naming conventions. The primary
branches are:

* stability - development for bug fixes (next minor release)
* develop - development for features (the next major release)
* master - no development, matches the last release

Feature development should take place in feature branches. These should
be prefixed with `feature/`:

* feature/commerce
* feature/pandora-accessory

Feature branches should be turned into pull-requests before they are
merged into develop.

When code for a release is frozen, a branch prefixed with `release/`
should be created. Version numbers should follow
[semver](http://semver.org) conventions.

* release/2.9.0
* release/2.22.0-dp.15+intrepid-earwig

## Installing / Getting up and running

Modify installer conditional in `system/ee/EllisLab/ExpressionEngine/Boot/boot.php` around line 60 from:

```
if (FALSE && defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'installer/'))
```

to

```
if (defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'installer/'))
```

Create an empty `config.php` file in `/system/user/config/`

Run the installer.

Set the development configuration items below.

## Development Configuration

Use uncompressed JavaScript:

```
$config['use_compressed_js'] = 'n';
```

Force MySQL strict mode:

```
$db['expressionengine']['stricton'] = TRUE;
```

Turn debug on:

```
$debug = 1;
```

## Updating from the repo

Modify installer conditional in `system/ee/EllisLab/ExpressionEngine/Boot/boot.php` around line 60 from:

```
if (FALSE && defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'installer/'))
```

to

```
if (defined('REQ') && REQ == 'CP' && is_dir(SYSPATH.'installer/'))
```

Modify config version variable in `system/user/config/config.php` around line 14 from:

```
$config['app_version'] = '3.0.0';
```

to

```
$config['app_version'] = '2.9.0';
```

Run updater, login.


## Unit Testing

In order to run unit tests you will need Composer and PHPUnit. In the
system/Tests directory run:

```
composer install
```

This will install the versions listed in the composer.lock file. If you
wish to update phpunit, mockery, or any of the others, run `composer
update` instead and commit the new lock file after testing.

From there you can run the ExpressionEngine tests with:

```
phpunit ExpressionEngine/
```

Alternatively you can install phing and run all current unit tests from
the project root using:

```
phing tests
```


### Writing Unit Tests

Before beginning to write tests, please read the documentation for:

* Mockery: https://github.com/padraic/mockery#documentation, especially
  the sections on expectation declarations, argument validation, and
  partial mocking.

* PHPUnit: http://phpunit.de/manual/current/en/index.html, especially
  the sections on assertions and database testing.

General Guidelines:

 - Use mockery for mocks
 - Prefer the matchesPattern hamcrest matcher instead of the mockery
   regex default for clarity.
 - Use array datasets for database tests

Each test case should look as close to a minimal production or
documentation code sample as possible and should be self documenting.
They should be easy to find, quick to add, and on the rare occasion that
they are edited it should be very difficult to make the test unclear or
nullify the assertion. As a result, slightly different rules apply to
test writing than what you might be used to in regular code.

The Good:

* Write tests for bugs
* Write tests while building a feature
* Mirror the existing folder structure for your test
* Name your class test files <ClassName>Test.php
* Name your test methods tests<MethodName>
* Use annotations wherever possible.
* Use separate methods to test exceptions
  * Call them tests<MethodName>ThrowsException<condition> and use the
    @expectedException annotation.
* Use the `$message` parameter on assertions to help document the tests
  * This helps pinpoint the failing assertion. Use this when there are a
    lot of assertions in your method or when it is not immediately clear
    from the `$expected`/`$actual` pair which assertion failed. Try
    failing a few assertions on purpose to get a feel for how to find
    them.
  * This field is a comment to your assertion. It should describe the
    expected behavior. Keep it short, it is not documentation for the
    code -- that belongs with the code.
  * If your class has a lot of methods, especially if they are similar
    consider prefixing the message. `func() accepts no arguments`
* For testing a range of options, use dataProviders to keep the test
  short.
* Include the $message parameter in your dataProvider array
* Use tearDown to cleanup your setUp
* Use @covers on methods that you cannot fully isolate or on
  constructors. Always set it on constructor tests as they may grow to
  include things that are verified in a separate test.

  `@covers EllisLab\ExpressionEngine\Something::__construct`

The Bad:

* Never assume that the test is wrong. A bug has probably been
  introduced.
* Never commit a new test that is broken unless it tests new code.
* Never commit a test that you did not see fail first. It may not be
  running.
* Avoid control structures (if/while/try/foreach).
  * Loops can be avoided by using @dataProvider
  * Try statements can be avoided using @expectedException
  * If statements can be avoided by creating multiple methods
* Avoid needless comments
    * They obscure the annotations, making the test harder to follow
    * They increase the perceived effort of adding a test, resulting in
      lower coverage
    * Consider putting the comment on the code you're testing instead.
      Do not duplicate code documentation in the test.
    * If your test needs more explanation than fits into the `$message`
      parameter, then you should reconsider the test case or the code it
      is testing.
* If you're stubbing a lot, take a step back and consider if you can
  decouple your class more cleanly.


Example of a class to test:

```php
class Math {

  function divide($a, $b)
  {
    if ($b == 0)
    {
      throw new InvalidArgumentException('Cannot divide by 0');
    }

    return $a / $b;
  }
}
```


Example of a good test:

```php
class MathTest {

  protected function setUp()
  {
    $this->math = new Math();
  }

  protected function tearDown()
  {
    $this->math = NULL;
  }

  public function validDivisons()
  {
    return array(
      array(6, 2, 3, 'divide() handles positive numbers'),
      array(-6, -2, 3, 'divide() handles negative numbers'),
      array(10, 2.5, 4, 'divide() handles floats'),
      array(INF, 2, INF, 'divide() is infinite with infinity in the dividend'),
      array(6, INF, 0, 'divide() is 0 with infinity in the divisor'),
      array(INF, INF, NAN, 'divide() is NotANumber with infinity in both arguments')
    )
  }

  /**
   * @dataProvider validDivisions
   */
  public function testDivide($a, $b, $result, $message)
  {
    $this->assertEquals($result, $this->math->divide($a, $b), $message);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testDivideThrowsExceptionForDivisonByZero()
  {
    $this->math->divide(10, 0);
  }
}
```
