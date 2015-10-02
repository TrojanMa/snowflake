<?php

require 'vendor/autoload.php';

// Test how fast the IDs can be generated.

$idW = new LucasVscn\Snowflake\IdWorker(31, 31);
$t1  = $idW->getTimestamp();
$max = 1000000;

for ($i = 0; $i < $max; $i++) {
    $idW->nextId();
}

$t2 = $idW->getTimestamp();

$t = $t2 - $t1;
printf('generated %d ids in %d ms, or %.0f ids/sec' . PHP_EOL, $max, $t, ($max*100)/$t);
