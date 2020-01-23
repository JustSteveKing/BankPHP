# Bank PHP

!! WORK IN PROGRESS !!

Bank is a small and simple, but powerful, database toolkit.


## Selecting all records

```php
<?php

$bank = new Bank([
    // config here
]);

$bank->from('table-name')->select()->get();
```

## Limiting records

```php
<?php

$bank = new Bank([
    // config here
]);

$bank->from('table-name')->limit(12)->select()->get();
```

## Offsetting records

```php
<?php

$bank = new Bank([
    // config here
]);

$bank->from('table-name')->offset(2)->select()->get();
```

## Selecting specific fields as an Array

```php
<?php

$bank = new Bank([
    // config here
]);

$bank->from('table-name')->select(['id', 'name'])->get();
```

## Selecting specific fields as a string

```php
<?php

$bank = new Bank([
    // config here
]);

$bank->from('table-name')->select('id,name')->get();
```