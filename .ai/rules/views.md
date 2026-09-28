---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## Guard sidebar nav links with Route::has
The sidebar builds its nav list and every nav `route()` link must first be guarded with `@if (Route::has($name))` (and `routeIs` for active state). Never render a nav route that is not registered, or the whole sidebar (and every authenticated page) 500s.
