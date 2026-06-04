<?php

use App\Scraping\Extractors\IcalExtractor;

it('parses VEVENTs from an iCal feed', function () {
    $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\n"
        ."BEGIN:VEVENT\r\nUID:1@x.de\r\nSUMMARY:Tantra Festival\r\nDTSTART:20260730T100000\r\nDTEND:20260802T170000\r\nURL:https://x.de/e/festival\r\nLOCATION:Hamburg\r\nEND:VEVENT\r\n"
        ."BEGIN:VEVENT\r\nUID:2@x.de\r\nSUMMARY:Kali Frauen\r\nDTSTART;VALUE=DATE:20260612\r\nURL:https://x.de/e/kali\r\nEND:VEVENT\r\n"
        .'END:VCALENDAR';

    $events = (new IcalExtractor)->extract($ics, 'https://x.de/cal.ics');

    expect($events)->toHaveCount(2)
        ->and($events[0]->title)->toBe('Tantra Festival')
        ->and($events[0]->startDate)->toBe('2026-07-30 10:00')
        ->and($events[0]->endDate)->toBe('2026-08-02 17:00')
        ->and($events[0]->sourceUrl)->toBe('https://x.de/e/festival')
        ->and($events[0]->city)->toBe('Hamburg')
        ->and($events[1]->startDate)->toBe('2026-06-12 00:00');
});

it('returns empty array for non-ical content', function () {
    expect((new IcalExtractor)->extract('<html>no calendar here</html>', 'https://x.de'))->toBe([]);
});
