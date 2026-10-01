@php
    // Read by the inline script below before the browser paints, so the first
    // frame is already the right theme and there is no flash.
    $themeFallback = $themePreference ?? 'system';
    $themeEndpoint = auth()->check() ? route('profile.theme') : null;
@endphp
{{--
    Applies the theme choice before the first paint.

    Order of authority: an explicit choice already saved in this browser wins,
    otherwise the value stored on the account is used. Guests only ever have the
    browser value, which is why the endpoint is null for them and the toggle
    stays in localStorage.
--}}
<script>
    (function () {
        var STORAGE_KEY = 'tsts.theme';
        var CHOICES = ['light', 'dark', 'system'];
        var media = window.matchMedia('(prefers-color-scheme: dark)');

        var preference = null;
        try {
            preference = window.localStorage.getItem(STORAGE_KEY);
        } catch (error) {
            preference = null;
        }

        if (CHOICES.indexOf(preference) === -1) {
            preference = @json($themeFallback);
        }

        try {
            window.localStorage.setItem(STORAGE_KEY, preference);
        } catch (error) {
            // Private browsing with storage disabled: the theme still applies for
            // this page, it just will not be remembered.
        }

        window.themePreference = preference;
        window.themeEndpoint = @json($themeEndpoint);

        var dark = preference === 'dark' || (preference === 'system' && media.matches);

        document.documentElement.classList.toggle('dark', dark);
        // Set inline as well, otherwise the canvas stays light until the
        // stylesheet that declares `color-scheme` arrives.
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    })();
</script>
