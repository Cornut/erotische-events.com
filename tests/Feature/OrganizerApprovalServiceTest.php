<?php

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use App\Services\OrganizerApprovalService;

beforeEach(fn () => $this->service = app(OrganizerApprovalService::class));

it('approves a pending organizer', function () {
    $organizer = Organizer::factory()->create(['verification_status' => OrganizerVerificationStatus::Pending]);
    $this->service->approve($organizer);
    expect($organizer->fresh()->verification_status)->toBe(OrganizerVerificationStatus::Approved);
});

it('rejects a pending organizer', function () {
    $organizer = Organizer::factory()->create(['verification_status' => OrganizerVerificationStatus::Pending]);
    $this->service->reject($organizer);
    expect($organizer->fresh()->verification_status)->toBe(OrganizerVerificationStatus::Rejected);
});

it('reports whether an organizer may publish', function () {
    $approved = Organizer::factory()->approved()->create();
    $pending = Organizer::factory()->create();
    expect($this->service->canPublish($approved))->toBeTrue()
        ->and($this->service->canPublish($pending))->toBeFalse();
});
