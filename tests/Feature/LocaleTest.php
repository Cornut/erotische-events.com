<?php

it('defaults to the German locale', function () {
    $this->get('/');

    expect(app()->getLocale())->toBe('de');
});

it('switches and persists the locale to English', function () {
    $this->get('/locale/en')->assertRedirect();
    expect(session('locale'))->toBe('en');

    $this->get('/');
    expect(app()->getLocale())->toBe('en');
});

it('ignores an unsupported locale', function () {
    $this->get('/locale/fr')->assertRedirect();

    expect(session('locale'))->toBeNull();
});
