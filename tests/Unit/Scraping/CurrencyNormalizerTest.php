<?php

use App\Scraping\CurrencyNormalizer;

beforeEach(fn () => $this->n = new CurrencyNormalizer(['EUR' => 1.0, 'CHF' => 1.05, 'USD' => 0.92]));

it('keeps EUR amounts', function () {
    expect($this->n->toEur(100.0, 'EUR'))->toBe(100.0);
});

it('treats unknown/missing currency as EUR', function () {
    expect($this->n->toEur(50.0, null))->toBe(50.0)
        ->and($this->n->toEur(50.0, 'XYZ'))->toBe(50.0);
});

it('converts CHF and USD to EUR', function () {
    expect($this->n->toEur(100.0, 'CHF'))->toBe(105.0)
        ->and($this->n->toEur(100.0, 'USD'))->toBe(92.0);
});
