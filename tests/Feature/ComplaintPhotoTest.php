<?php

use App\Models\Complaint;
use App\Models\ComplaintPhoto;
use App\Support\ImageUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

/**
 * Foto keluhan yang benar-benar tersimpan di disk privat, lewat jalur yang sama dengan unggahan asli.
 */
function storedComplaintPhoto(Complaint $complaint): ComplaintPhoto
{
    $path = app(ImageUpload::class)->store(UploadedFile::fake()->image('foto.jpg', 40, 30), 'complaints', 'local');

    return ComplaintPhoto::factory()->for($complaint)->create(['path' => $path]);
}

test('who may open a complaint photo', function (string $actor, int $status) {
    $scenario = accessScenario();
    $complaint = Complaint::factory()->for($scenario['tenancy']->room)->for($scenario['occupant'], 'reporter')->create();
    $photo = storedComplaintPhoto($complaint);

    $this->actingAs($scenario[$actor])
        ->get(route('complaints.photos.show', [$complaint, $photo]))
        ->assertStatus($status);
})->with([
    'pemilik kost' => ['owner', 200],
    'pelapor' => ['occupant', 200],
    'teman sekamar' => ['roommate', 200],
    'penghuni yang sudah keluar' => ['formerOccupant', 403],
    'tetangga kamar lain' => ['otherTenant', 403],
]);

test('photos are sent as private, uncached images', function () {
    $scenario = accessScenario();
    $complaint = Complaint::factory()->for($scenario['tenancy']->room)->create();
    $photo = storedComplaintPhoto($complaint);

    $response = $this->actingAs($scenario['roommate'])
        ->get(route('complaints.photos.show', [$complaint, $photo]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/webp')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))->toContain('private')->toContain('no-store')
        ->and($response->streamedContent())->toBe(Storage::disk('local')->get($photo->path));
});

test('photos from before the current tenancy stay hidden from its occupants', function () {
    $scenario = accessScenario();
    $oldComplaint = Complaint::factory()->for($scenario['tenancy']->room)->create([
        'created_at' => $scenario['tenancy']->start_date->subDay(),
    ]);
    $photo = storedComplaintPhoto($oldComplaint);

    $this->actingAs($scenario['occupant'])
        ->get(route('complaints.photos.show', [$oldComplaint, $photo]))
        ->assertForbidden();
});

test('a photo cannot be opened through another complaint', function () {
    $scenario = accessScenario();
    $ownComplaint = Complaint::factory()->for($scenario['tenancy']->room)->create();
    $neighbourPhoto = storedComplaintPhoto(
        Complaint::factory()->for($scenario['otherTenant']->currentTenancy()->room)->create(),
    );

    // Penghuni boleh melihat keluhannya sendiri, tapi foto di URL milik keluhan tetangga.
    // scopeBindings() menolak pasangan itu sebelum Policy dicek.
    $this->actingAs($scenario['occupant'])
        ->get(route('complaints.photos.show', [$ownComplaint, $neighbourPhoto]))
        ->assertNotFound();
});

test('a photo whose file is gone returns not found', function () {
    $scenario = accessScenario();
    $complaint = Complaint::factory()->for($scenario['tenancy']->room)->create();
    $photo = ComplaintPhoto::factory()->for($complaint)->create();

    $this->actingAs($scenario['owner'])
        ->get(route('complaints.photos.show', [$complaint, $photo]))
        ->assertNotFound();
});

test('guests cannot open complaint photos', function () {
    $complaint = Complaint::factory()->create();
    $photo = storedComplaintPhoto($complaint);

    $this->get(route('complaints.photos.show', [$complaint, $photo]))->assertRedirect(route('login'));
});

test('laravel does not serve the private disk by itself', function () {
    // Kalau 'serve' => true di config/filesystems.php, file privat bisa dibuka lewat signed URL
    // /storage/{path} tanpa Policy.
    expect(Route::has('storage.local'))->toBeFalse();
});
