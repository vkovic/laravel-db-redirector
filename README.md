# Laravel DB redirector

[![Build](https://travis-ci.org/vkovic/laravel-db-redirector.svg?branch=master)](https://travis-ci.org/vkovic/laravel-db-redirector)
[![Downloads](https://poser.pugx.org/vkovic/laravel-db-redirector/downloads)](https://packagist.org/packages/vkovic/laravel-db-redirector)
[![Stable](https://poser.pugx.org/vkovic/laravel-db-redirector/v/stable)](https://packagist.org/packages/vkovic/laravel-db-redirector)
[![License](https://poser.pugx.org/vkovic/laravel-db-redirector/license)](https://packagist.org/packages/vkovic/laravel-db-redirector)

### Manage HTTP redirections in Laravel using database

Manage redirects using database rules. Rules are intended to be very  similar to Laravel default routes, so syntax is pretty easy
to comprehend.

---

## Compatibility

The package is compatible with Laravel versions `>= 5.1`

## Installation

Install the package via composer:

```bash
composer require vkovic/laravel-db-redirector
```

The package needs to be registered in service providers:

```php
// File: config/app.php

// ...

/*
 * Package Service Providers...
 */

// ...

Vkovic\LaravelDbRedirector\Providers\DbRedirectorServiceProvider::class,
```

Database redirector middleware needs to be added to middleware array:

```php
// File: app/Http/Kernel.php

// ...

protected $middleware = [
    // ...
    \Vkovic\LaravelDbRedirector\Http\Middleware\DbRedirectorMiddleware::class
];
```

Run migrations to create table which will store redirect rules:

```bash
php artisan migrate
```

## Usage: Simple Examples

Creating a redirect is easy. You just have to add db record via provided RedirectRule model.
Default status code for redirections will be 301 (Moved Permanently).

```php
use Vkovic\LaravelDbRedirector\Models\RedirectRule;

// ...

RedirectRule::create([
    'origin' => '/one/two',
    'destination' => '/three'
]);
```

You can also specify another redirection status code:

```php
RedirectRule::create([
    'origin' => '/one/two',
    'destination' => '/three',
    'status_code' => 307 // Temporary Redirect
]);
```

You may use route parameters just like in native Laravel routes,
they'll be passed down the road - from origin to destination:

```php
RedirectRule::create([
    'origin' => '/one/{param}',
    'destination' => '/two/{param}'
]);

// If we visit: "/one/foo" we will end up at "two/foo"
```

Optional parameters can also be used:

```php
RedirectRule::create([
    'origin' => '/one/{param1?}/{param2?}',
    'destination' => '/four/{param1}/{param2}'
]);

// If we visit: "/one" we'll end up at "/four
// If we visit: "/one/two" we'll end up at "/four/two"
// If we visit: "/one/two/three" we'll end up at "/four/two/three"
```

Chained redirects will also work:

```php
RedirectRule::create([
    'origin' => '/one',
    'destination' => '/two'
]);

RedirectRule::create([
    'origin' => '/two',
    'destination' => '/three'
]);

RedirectRule::create([
    'origin' => '/three',
    'destination' => '/four'
]);

// If we visit: "/one" we'll end up at "/four"
```

We also can delete the whole chain at once
(3 previous redirect records in this example):

```php
RedirectRule::deleteChainedRecursively('/four');
```

Sometimes it's possible that you'll have more than one redirection with
the same destination. So it's smart to surround code with try catch block, because exception
will be raised in this case:

```php
RedirectRule::create(['origin' => '/one/two', 'destination' => '/three/four']);
RedirectRule::create(['origin' => '/three/four', 'destination' => '/five/six']);

// One more with same destination ("/five/six") as the previous one.
RedirectRule::create(['origin' => '/ten/eleven', 'destination' => '/five/six']);

try {
    RedirectRule::deleteChainedRecursively('five/six');
} catch (\Exception $e) {
    // ... handle exception
}
```

## Usage: Advanced

What about order of rules execution when given url corresponds to multiple rules.
Let's find out in this simple example:

```php
RedirectRule::create(['origin' => '/one/{param}/three', 'destination' => '/four']);
RedirectRule::create(['origin' => '/{param}/two/three', 'destination' => '/five']);

// If we visit: "/one/two/three" it corresponds to both of rules above,
// so, where should we end up: "/four" or "/five" ?
// ...
// It does not have anything to do with rule order in our rules table!
```

To solve this problem, we need to agree on simple (and logical) rule prioritizing:

**Priority 1:**
Rules without named parameters have top priority:

**Priority 2:**
If rule origin have named parameters, those with less named parameters will have higher priority

**Priority 3:**
If rule origin have same number of named parameters, those where named parameters are nearer the
end of the rule string will have priority

So lets examine our previous case, we have:
- "/one/{param}/three" => "/four"
- "/{param}/two/three" => "/five"

In this case both rules have the same number of named params, but in the first rule "{param}" is
nearer the end of the rule, so it will have priority and we'll end up at "/four".
