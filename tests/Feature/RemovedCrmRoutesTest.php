<?php

test('crm and portal routes are gone', function (string $url) {
    $this->get($url)->assertNotFound();
})->with([
    '/crm',
    '/crm/clients',
    '/crm/campaigns',
    '/crm/deals',
    '/crm/pipelines',
    '/portal',
]);

test('staff still cannot open removed crm paths', function (string $url) {
    $this->actingAs(staffWith())->get($url)->assertNotFound();
})->with([
    '/crm',
    '/crm/clients',
    '/portal',
]);
