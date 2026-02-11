# Potential Core Bugs

No findings recorded yet.

## 2026-02-11 10:55 EST - legacy/Fluid_field_parser::pre_process
- file: system/ee/legacy/libraries/Fluid_field_parser.php:101
- reason: Guard appears ineffective because the regex at `pre_process()` line 64 already constrains matches to configured fluid field names, making the `isset($fluid_field_fields[$field_name])` false-branch effectively unreachable.
- symptom: Attempting to drive invalid resolved names still proceeds through normal flow and does not trigger the intended early `false`.
- expected: Either the guard should be reachable for malformed input, or dead validation code should be removed/rewired so behavior is explicit.
- repro: `FluidFieldParserTest::testPreProcessSkipsVariableTagsForNonReservedModifiers` demonstrated that parser-controlled resolved names do not affect the guard key and invalid resolution cannot trip this branch.
- failing_versions: 7.4, 8.0, 8.1, 8.2, 8.3, 8.4
