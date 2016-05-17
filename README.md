# Awaitable

The purpose of this proposal is to provide a common interface for simple placeholder objects returned from async operations. This will allow libraries and components from different vendors to create coroutines regardless of used placeholder implementation. This proposal is not designed to replace promise implementations that may be chained. Instead, this interface may be extended by promise implementations.

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in this document are to be
interpreted as described in [RFC 2119][].

An `Awaitable` represents the eventual result of an asynchronous operation. Interaction with an `Awaitable` happens through its `when` method, which registers a callback to receive either an `Awaitable`'s eventual value or the reason why the `Awaitable` has failed. They're basically the same as a `Promise` in JavaScript's [Promises/A+ specification]([Promises/A+](https://promisesaplus.com/)), but the interaction with them is different as the following paragraphs show.

This specification defines the absolute minimums for interoperable coroutines, which can be implemented in PHP using generators. Further methods like a `watch` method for progress updates or `then` for chainability are out of scope of this specification. The name `Awaitable` works well, especially when PHP is extended to support `await`, and is also [already used in HHVM](https://docs.hhvm.com/hack/reference/interface/HH.Awaitable/).

`Awaitable` is the fundamental primitive in asynchronous programming. It should be as lightweight as possible, as any cost adds up significantly. The existing Promises/A+ specification conflicts with the goal of lightweight primitives. This specification uses `when`, so existing implementations can continue to provide `then` for chainability.

Coroutines should be the preferred way of writing asynchronous code, as it allows the use of `try` / `catch` and doesn't result in a so-called callback hell. Coroutines do not use the returned `Promise` of a `then` callback, but returning a `Promise` is required by Promises/A+. Thus there's a lot of useless object creations, which aren't cheap and add up.

This specification does not deal with how to create, succeed or fail `Awaitable`s, as only the consumption of `Awaitable`s is required to be interoperable.

## Terminology

1. _Awaitable_ is an object implementing `Interop\Async\Awaitable` and conforming to this specification.
1. _Value_ is any legal PHP value (including `null`), except an instance of `Interop\Async\Awaitable`.
1. _Error_ is any value that can be thrown using the `throw` statement.
1. _Reason_ is an error indicating why an `Awaitable` has failed.

## States

An `Awaitable` MUST be in one of three states: `pending`, `succeeded`, `failed`.

| A promise in … state | &nbsp; |
|----------------------|--------|
|`pending`  | <ul><li>MAY transition to either the `succeeded` or `failed` state.</li></ul>                                |
|`succeeded`| <ul><li>MUST NOT transition to any other state.</li><li>MUST have a value which MUST NOT change.*</li></ul>  |
|`failed`   | <ul><li>MUST NOT transition to any other state.</li><li>MUST have a reason which MUST NOT change.*</li></ul> |

* _Must not change_ refers to the _reference_ being immutable in case of an object, _not the object itself_ being immutable.

## Consumption

An `Awaitable` MUST implement `Interop\Async\Awaitable` and thus provide a `when` method to access its current or eventual value or reason.

```php
<?php

namespace Interop\Async;

/**
 * Representation of a the future value of an asynchronous operation.
 */
interface Awaitable
{
    /**
     * Registers a callback to be invoked when the awaitable is resolved.
     *
     * @param callable(\Throwable|\Exception|null $exception, mixed $result) $onResolved
     *
     * @return void
     */
    public function when(callable $onResolved);
}
```

The `when` method MUST accept at least one argument:

`$callback` – A callable conforming to the following signature:

```php
function($error, $value) { /* ... */ }
```

Any implementation MUST at least provide these two parameters. The implementation MAY extend the `Awaitable` interface with additional parameters passed to the callback. Further arguments to `when` MUST have default values, so `when` can always be called with only one argument. Callbacks MAY decide to consume only one or none of the provided parameters. `when` MUST NOT return a value.

> **NOTE:** The signature doesn't specify a type for `$error`. This is due to the new `Throwable` interface introduced in PHP 7. As this specification is PHP 5 compatible, we can use neither `Throwable` nor `Exception`.

All registered callbacks MUST be executed in the order they were registered. If one of the callbacks throws an `Exception` or `Throwable`, it MUST be rethrown in a callable passed to `Loop::defer` so `Loop::onError` can be properly invoked by the loop. `Loop` refers to the [global event loop accessor](https://github.com/async-interop/event-loop/blob/master/src/Loop.php). The `Awaitable` implementation MUST then continue to call the remaining callbacks with the original reason.

If an `Awaitable` is resolved with another `Awaitable`, the `Awaitable` MUST keep in pending state until the passed `Awaitable` is resolved. Thus, the value of an `Awaitable` can never be an `Awaitable`. An `Awaitable` MUST NOT be resolved with itself.

## Contributors

* [Aaron Piotrowski](https://github.com/trowski)
* [Andrew Carter](https://github.com/AndrewCarterUK)
* [Bob Weinand](https://github.com/bwoebi)
* [Cees-Jan Kiewiet](https://github.com/WyriHaximus)
* [Christopher Pitt](https://github.com/assertchris)
* [Daniel Lowrey](https://github.com/rdlowrey)
* [Niklas Keller](https://github.com/kelunik)
* [Stephen M. Coakley](https://github.com/coderstephen)

[RFC 2119]: http://tools.ietf.org/html/rfc2119
