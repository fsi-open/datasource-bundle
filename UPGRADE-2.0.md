# This is an upgrade guide from versions 1.x to 2.0

## Use doctrine-orm driver instead of doctrine

The `doctrine` driver has been renamed to `doctrine-orm`, so you will need to use
that instead.

## Autoconfiguration of DataGrid extensions 

Any custom datasource extensions are no longer needed to be tagged manually
with `'datasource.*'` tags. They should implement appropriate interfaces instead. 
