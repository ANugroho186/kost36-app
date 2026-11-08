<?php

use App\Enums\RentalInquiryStatus;
use App\Models\RentalInquiry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the owner sees new requests first, then contacted, then done', function () {
    $done = RentalInquiry::factory()->done()->create(['created_at' => now()->subHour()]);
    $olderNew = RentalInquiry::factory()->create(['created_at' => now()->subDays(2)]);
    $contacted = RentalInquiry::factory()->create(['status' => RentalInquiryStatus::Contacted, 'created_at' => now()->subMinutes(5)]);
    $newerNew = RentalInquiry::factory()->create(['created_at' => now()->subDay()]);

    $response = $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.rental-inquiries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('owner/rental-inquiries/index'));

    expect(array_column($response->inertiaProps('inquiries'), 'id'))
        ->toBe([$newerNew->id, $olderNew->id, $contacted->id, $done->id]);
});

test('each request carries only what the page needs, with its deletion date once done', function () {
    $this->freezeSecond();
    $inquiry = RentalInquiry::factory()->done()->create([
        'name' => 'Rina',
        'contact' => '081234567890',
        'preferred_date' => '2026-11-12',
        'message' => 'Boleh survei sore?',
    ]);

    // Tanpa ->etc(): kunci tambahan membuat test gagal.
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.rental-inquiries.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.0', fn (Assert $item) => $item
                ->where('id', $inquiry->id)
                ->where('name', 'Rina')
                ->where('contact', '081234567890')
                ->where('preferredDate', '2026-11-12')
                ->where('message', 'Boleh survei sore?')
                ->where('status', 'done')
                ->where('receivedAt', now()->format('Y-m-d\TH:i'))
                ->where('deletesOn', now()->addDays(RentalInquiry::RETENTION_DAYS)->toDateString()),
            ),
        );
});

test('the owner moves a request along and done fills the deletion clock', function () {
    $this->freezeSecond();
    $inquiry = RentalInquiry::factory()->create();
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->put(route('owner.rental-inquiries.update', $inquiry), ['status' => 'contacted'])
        ->assertRedirect(route('owner.rental-inquiries.index'));

    expect($inquiry->fresh())
        ->status->toBe(RentalInquiryStatus::Contacted)
        ->done_at->toBeNull();

    $this->actingAs($owner)->put(route('owner.rental-inquiries.update', $inquiry), ['status' => 'done']);

    expect($inquiry->fresh())
        ->status->toBe(RentalInquiryStatus::Done)
        ->and($inquiry->fresh()->done_at?->equalTo(now()))->toBeTrue();
});

test('marking a done request as done again keeps the first done date', function () {
    $inquiry = RentalInquiry::factory()->done()->create(['done_at' => now()->subDays(10)]);

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.rental-inquiries.update', $inquiry), ['status' => 'done']);

    expect($inquiry->fresh()->done_at->toDateString())->toBe(now()->subDays(10)->toDateString());
});

test('reopening a done request stops the deletion clock', function () {
    $inquiry = RentalInquiry::factory()->done()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.rental-inquiries.update', $inquiry), ['status' => 'contacted']);

    expect($inquiry->fresh())
        ->status->toBe(RentalInquiryStatus::Contacted)
        ->done_at->toBeNull();
});

test('an unknown status is rejected', function () {
    $inquiry = RentalInquiry::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.rental-inquiries.update', $inquiry), ['status' => 'archived'])
        ->assertInvalid('status');

    expect($inquiry->fresh()->status)->toBe(RentalInquiryStatus::New);
});

test('the owner deletes a request', function () {
    $inquiry = RentalInquiry::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->delete(route('owner.rental-inquiries.destroy', $inquiry))
        ->assertRedirect(route('owner.rental-inquiries.index'));

    expect(RentalInquiry::find($inquiry->id))->toBeNull();
});
