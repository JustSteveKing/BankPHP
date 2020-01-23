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

## Query Statistics

```php
$bank = new Bank([
    // config here
]);
$bank->statsEnabled = true;
$bank->from('table-name')->select()->get();

$bank->getStats();

/**
 * OUTPUT:
 *  [
 *  "queries" => [
 *      0 => [
 *          "query" => "SELECT * FROM table-name",
 *          "time" => 0.031558036804199,
 *          "rows" => 73,
 *          "changes" => 73
 *      ]
 *  ],
 *  "total_time" => 0.031558036804199,
 *  "num_queries" => 1,
 *  "num_rows" => 73,
 *  "num_changes" => 73,
 *  "avg_query_time" => 0.031558036804199
 *  ]
 * /
```