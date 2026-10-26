<?php

use App\Enums\RentalInquiryStatus;
use App\Models\Property;
use App\Models\RentalInquiry;

function validInquiry(array $overrides = []): array
{
    return [
        'name' => 'Calon Penyewa',
        'contact' => 'calon@example.com',
        'preferred_date' => now()->addDays(3)->toDateString(),
        'message' => 'Apakah kamar 4 masih tersedia?',
        ...$overrides,
    ];
}

test('visitors can request a viewing', function () {
    $property = Property::factory()->create();

    $this->from(route('home'))
        ->post(route('rental-inquiries.store'), validInquiry())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $inquiry = RentalInquiry::sole();

    expect($inquiry->property_id)->toBe($property->id)
        ->and($inquiry->name)->toBe('Calon Penyewa')
        ->and($inquiry->status)->toBe(RentalInquiryStatus::New);
});

test('name and contact are required', function () {
    Property::factory()->create();

    $this->from(route('home'))
        ->post(route('rental-inquiries.store'), validInquiry(['name' => '', 'contact' => '']))
        ->assertSessionHasErrors(['name', 'contact']);

    expect(RentalInquiry::count())->toBe(0);
});

test('the preferred date cannot be in the past', function () {
    Property::factory()->create();

    $this->from(route('home'))
        ->post(route('rental-inquiries.store'), validInquiry(['preferred_date' => now()->subDay()->toDateString()]))
        ->assertSessionHasErrors('preferred_date');
});

test('overly long messages are rejected', function () {
    Property::factory()->create();

    $this->from(route('home'))
        ->post(route('rental-inquiries.store'), validInquiry(['message' => str_repeat('a', 1001)]))
        ->assertSessionHasErrors('message');
});

test('visitors cannot set the status of their own inquiry', function () {
    Property::factory()->create();

    $this->from(route('home'))->post(route('rental-inquiries.store'), validInquiry([
        'status' => RentalInquiryStatus::Done->value,
        'done_at' => now()->toDateTimeString(),
    ]));

    $inquiry = RentalInquiry::sole();

    expect($inquiry->status)->toBe(RentalInquiryStatus::New)
        ->and($inquiry->done_at)->toBeNull();
});

test('requests are limited to five per hour per visitor', function () {
    Property::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->from(route('home'))
            ->post(route('rental-inquiries.store'), validInquiry())
            ->assertRedirect(route('home'));
    }

    $this->from(route('home'))
        ->post(route('rental-inquiries.store'), validInquiry())
        ->assertTooManyRequests();

    expect(RentalInquiry::count())->toBe(5);
});

test('requests are rejected before the property exists', function () {
    $this->post(route('rental-inquiries.store'), validInquiry())->assertNotFound();
});
