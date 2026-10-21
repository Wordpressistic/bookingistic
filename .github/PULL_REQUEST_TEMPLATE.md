## Summary

<!-- What does this PR do, and why? Link the issue if there is one. -->

## Type of change

- [ ] Bug fix
- [ ] New feature
- [ ] Refactor (no behavior change)
- [ ] Documentation / readme
- [ ] Build / tooling

## Checklist

- [ ] `php tests/run-all.php` passes
- [ ] `git ls-files '*.php' | xargs -n1 php -l` passes
- [ ] Input sanitized, output escaped, every query through `$wpdb->prepare()`
- [ ] New strings use the `bookingistic` text domain and `languages/bookingistic.pot` is updated
- [ ] User-facing changes have a `readme.txt` changelog entry (mirrored in `CHANGELOG.md`)
- [ ] Tested on WordPress ___ / PHP ___

## License

By opening this pull request I confirm my contribution is licensed under GPL-2.0-or-later.
