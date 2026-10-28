<?php

use App\Models\Property;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function propertyData(array $overrides = []): array
{
    return [
        'name' => 'Kost Contoh',
        'address' => 'Jl. Contoh No. 36, Kota Contoh',
        'description' => 'Kost dummy dekat kampus.',
        'rules' => 'Jam tenang pukul 22.00.',
        ...$overrides,
    ];
}

test('the owner creates the property profile when none exists yet', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->get(route('owner.property.edit'))
        ->assertInertia(fn (Assert $page) => $page->component('owner/property/edit')->where('property', null));

    $this->actingAs($owner)
        ->put(route('owner.property.update'), propertyData())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.property.edit'));

    $property = Property::sole();

    expect($property->owner_id)->toBe($owner->id)
        ->and($property->name)->toBe('Kost Contoh');
});

test('the owner updates the existing property profile', function () {
    $owner = User::factory()->owner()->create();
    Property::factory()->for($owner, 'owner')->create(['name' => 'Nama Lama']);

    $this->actingAs($owner)
        ->put(route('owner.property.update'), propertyData(['name' => 'Nama Baru']))
        ->assertSessionHasNoErrors();

    expect(Property::sole()->name)->toBe('Nama Baru');
});

test('all property fields are required', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->from(route('owner.property.edit'))
        ->put(route('owner.property.update'), [])
        ->assertSessionHasErrors(['name', 'address', 'description', 'rules']);

    expect(Property::count())->toBe(0);
});
