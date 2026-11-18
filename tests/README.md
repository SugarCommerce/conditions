# Tests

`composer test` runs the PHPUnit suite. There is no WordPress to load: the
core functions the library touches are stubbed in `bootstrap.php` -- hooks
that really run, and the register and enqueue calls recorded so a test can
read back what WordPress was asked to do.

- **Engine** -- the comparator, regular expressions, and how a group of rules
  decides when some of them cannot be judged.
- **Admin** -- what a saved rule keeps, and the save itself: the nonce, the
  capability, and values arriving in the database as they were typed.
- **Helpers** -- IP and email patterns, the request (and what it refuses to
  believe), and the velocity counts.
- **ConditionsTest** -- registering a set, checking it, the prefix, the
  search endpoints' permission and the editor's data.
- **BuiltinTest** -- every built-in condition is well formed and judged as
  its description says.
- **MovingTest** -- no global helpers, and every old built-in id still there.
