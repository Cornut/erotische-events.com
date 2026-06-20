<?php

use App\Scraping\LinkExtractor;

it('returns absolute same-domain content links and skips assets/endpoints', function () {
    $html = <<<'HTML'
        <a href="/events/tantra-festival.html">Festival</a>
        <a href="https://x.de/events/paar-seminar/">Seminar</a>
        <a href="https://other.de/events/foreign">Other domain</a>
        <a href="/wp-content/uploads/2021/icon.svg">logo</a>
        <a href="https://x.de/assets/app.js?ver=3">script</a>
        <a href="/feed/">feed</a>
        <a href="#anchor">anchor</a>
        <a href="mailto:hi@x.de">mail</a>
    HTML;

    $links = LinkExtractor::sameDomainLinks($html, 'https://x.de/termine');

    expect($links)->toBe([
        'https://x.de/events/tantra-festival.html',
        'https://x.de/events/paar-seminar/',
    ]);
});

it('flags static assets and machine endpoints', function () {
    expect(LinkExtractor::isAsset('https://x.de/a/logo.svg'))->toBeTrue()
        ->and(LinkExtractor::isAsset('https://x.de/wp-json/oembed/1.0/embed'))->toBeTrue()
        ->and(LinkExtractor::isAsset('https://x.de/font.woff2?ver=1'))->toBeTrue()
        ->and(LinkExtractor::isAsset('https://x.de/events/seminar'))->toBeFalse();
});

it('resolves ical and webcal links to absolute https urls', function () {
    $html = '<a href="webcal://x.de/cal.ics">cal</a><a href="/ics/seminar.php?id=3">add</a>';

    expect(LinkExtractor::icalLinks($html, 'https://x.de/detail'))->toBe([
        'https://x.de/cal.ics',
        'https://x.de/ics/seminar.php?id=3',
    ]);
});
