<?php

it('exposes scraping config defaults', function () {
    expect(config('scraping.candidate_paths'))->toContain('/termine', '/seminare')
        ->and(config('scraping.fx.CHF'))->toBeGreaterThan(0)
        ->and(config('scraping.timeout'))->toBeInt()
        ->and(config('services.anthropic.model'))->toBeString();
});
