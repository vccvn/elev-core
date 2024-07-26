<?php

$text = 'a.b like';
preg_match_all('/[A-z0-9_]\s*(=|!=|<=|>=|<|>|<>|!|==|\slike|!like|notline)$/', $text, $m);
print_r($m);