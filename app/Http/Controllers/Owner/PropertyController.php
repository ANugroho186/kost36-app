<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\PropertyRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function edit(#[CurrentUser] User $user): Response
    {
        Gate::authorize('manage', Property::class);

        return Inertia::render('owner/property/edit', [
            'property' => $user->properties()->oldest('id')->first()
                ?->only(['name', 'address', 'description', 'rules']),
        ]);
    }

    /**
     * Menyimpan profil kost. Kalau belum ada, profil dibuat untuk pemilik yang sedang login.
     */
    public function update(PropertyRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $property = $user->properties()->oldest('id')->first() ?? $user->properties()->make();
        $property->fill($request->validated())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Property profile saved.')]);

        return to_route('owner.property.edit');
    }
}
