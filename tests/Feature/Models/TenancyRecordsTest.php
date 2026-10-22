<?php

use App\Enums\PaymentProofStatus;
use App\Enums\TenancyDecisionType;
use App\Models\PaymentProof;
use App\Models\ReminderLog;
use App\Models\Tenancy;
use App\Models\TenancyDecision;
use Illuminate\Database\UniqueConstraintViolationException;

test('new payment proofs are pending by default', function () {
    $proof = Tenancy::factory()->create()->paymentProofs()->create([
        'file_path' => 'payment-proofs/contoh.jpg',
    ]);

    expect($proof->fresh()->status)->toBe(PaymentProofStatus::Pending);
});

test('payment proof file path is hidden when serialized', function () {
    $proof = PaymentProof::factory()->create();

    expect($proof->toArray())->not->toHaveKey('file_path');
});

test('deleting the uploader keeps the payment proof', function () {
    $proof = PaymentProof::factory()->create();

    $proof->uploader->delete();

    expect($proof->fresh()->uploaded_by)->toBeNull();
});

test('a tenancy with payment proofs cannot be deleted', function () {
    $proof = PaymentProof::factory()->create();

    expect(fn () => $proof->tenancy->delete())->toViolateForeignKey();
});

test('owner decisions record only when they were made', function () {
    $decision = Tenancy::factory()->create()->decisions()->create([
        'type' => TenancyDecisionType::Extend,
        'days' => 7,
    ]);

    expect($decision->fresh()->created_at)->not->toBeNull()
        ->and($decision->fresh()->type)->toBe(TenancyDecisionType::Extend);
});

test('a tenancy with owner decisions cannot be deleted', function () {
    $decision = TenancyDecision::factory()->create();

    expect(fn () => $decision->tenancy->delete())->toViolateForeignKey();
});

test('the same reminder cannot be logged twice', function () {
    $log = ReminderLog::factory()->create();

    ReminderLog::factory()->for($log->tenancy)->create([
        'due_date' => $log->due_date,
        'type' => $log->type,
    ]);
})->throws(UniqueConstraintViolationException::class);

test('deleting a tenancy deletes its reminder logs', function () {
    $tenancy = Tenancy::factory()->create();
    ReminderLog::factory()->for($tenancy)->create();

    $tenancy->delete();

    expect(ReminderLog::count())->toBe(0);
});
