<?php

function domena() {
    $domena = preg_replace('/[^a-zA-Z0-9\.]/', '', $_SERVER['HTTP_HOST']);
    return $domena;
}