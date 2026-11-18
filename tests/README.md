# Tests

`composer test` runs the PHPUnit suite. There is no WordPress to load: the
core functions the library touches are stubbed in `bootstrap.php` -- hooks
that really run, and the register and enqueue calls recorded so a test can
read back what WordPress was asked to do.

- **Engine** -- the comparator, regular expressions, and how a group of rules
  decides when some of them cannot be judged.
- **Admin** -- what a saved rule keeps, and the save itself: the nonce, the
  capability, and values arriving in the database as they were typed.
  `CoreTest` covers the jobs handed to core: the trash notice
  (`wp_admin_notice()`, copied verbatim into `bootstrap.php`), the REST
  check and reading a list.
- **Helpers** -- IP patterns (`Helpers\IP`, through the comparator and
  the sanitiser), email patterns, the request (and what it refuses to
  believe), the velocity counts, a user's capabilities as `user_can()` answers
  them, and a post's age in the site's timezone.
- **ConditionsTest** -- registering a set, checking it, the prefix, the
  search endpoints' permission and the editor's data.
- **BuiltinTest** -- every built-in condition is well formed and judged as
  its description says.
- **MovingTest** -- no global helpers, only the dependencies meant, and every
  old built-in id still there.
