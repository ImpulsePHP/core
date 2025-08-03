ImpulsePHP Core
===============

This package provides the core building blocks of the ImpulsePHP framework. It
contains the component system, rendering engines and utility classes. The
framework entry point is defined by the `ImpulseKernelInterface` implemented by
the default `Kernel\Impulse` class.

### HTTP layer

A lightweight HTTP layer is available under `Impulse\Core\Http`. It exposes a
`Request` object able to parse GET/POST parameters from globals. The `PageRouter`
uses this layer to resolve pages from incoming requests.

### Router component

The `<router>` component generates standard `<a>` links using page names. It
handles AJAX navigation and updates the browser history seamlessly.

Components, pages and layouts can access the current HTTP request through the
`getRequest()` method inherited from `AbstractComponent`. At each navigation,
`HtmlResponse` stores the page render time and size in `$_SERVER['IMPULSE_PAGE_TIME']`
and `$_SERVER['IMPULSE_PAGE_WEIGHT']`.

Pages are identified via the `name` property of the `#[PageProperty]` attribute.

### Layout slots

Pages can send data to their layout using the `<slot-layout>` tag. Provide a
`name` attribute to target a named slot in the layout or omit it to use the
default slot:

```html
<slot-layout name="title">My title</slot-layout>
```

Any remaining markup in the page becomes the content of the layout's default
slot when no anonymous `<slot-layout>` tag is used.

### LocalStorage store access

`LocalStorageStoreInstance` lets PHP read and update values from the
browser's `localStorage` without server persistence. Use the provided
`assets/impulse/localStorageBridge.js` script on the client so that
`Request::createFromGlobals()` can populate the store data. When values are
modified server-side, the generated JavaScript is collected and automatically
sent back in AJAX or HTML responses so the browser keeps its `localStorage`
in sync.

### Kernel and service container

The `Impulse\Core\Bootstrap\Kernel` class exposes a minimal dependency
injection container through the `ImpulseKernelInterface`. Services can be
registered by `ServiceProvider` implementations like the provided
`CoreServiceProvider` which sets up the event dispatcher.

```php
use Impulse\Core\Bootstrap\{Kernel, CoreServiceProvider};

$kernel = new Kernel([
    new CoreServiceProvider(),
]);
$kernel->boot();
$events = $kernel->getContainer()->get(\Impulse\Core\Contracts\EventDispatcherInterface::class);
```

Service providers can optionally define a `boot()` method which will be
executed after all providers have registered their services. This allows
providers to hook into events or perform initialization once the container
is fully configured.

### Provider manager

The `Impulse` class exposes a simple provider manager. Register providers after
calling `Impulse::boot()` and then trigger their `boot()` methods:

```php
use Impulse\Core\Kernel\Impulse;

Impulse::boot();
Impulse::registerProvider(new MyCustomProvider());
Impulse::bootProviders();
```

### DevTools event collector

ImpulsePHP exposes a lightweight event collector that can broadcast framework
activity to an external DevTools interface when enabled. Set `"devtools" => true`
in `impulse.php` while in development to activate the socket emitter. All
events are formatted as JSON and include the originating file and line number.

```php
use Impulse\Core\Support\Logger;

Logger::info('Application started', ['route' => '/']);
```

Every log entry is also written to `var/impulse.log` using a readable format:

```
[2025-07-22 14:00:00] [INFO] /src/App.php:42 Application started
    context: {
        "route": "/"
    }
```

The logger accepts an optional context array to enrich each entry and requires
no configuration.

Profiler timers will also emit `profiler` events whenever `Profiler::stop()` is
called, capturing the duration and memory usage of each task.

Route resolutions and full HTTP requests are also tracked so a DevTools
interface can display navigation details in real time.
