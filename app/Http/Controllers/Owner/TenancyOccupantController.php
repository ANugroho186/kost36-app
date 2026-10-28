<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Tenancies\AddOccupant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AddOccupantRequest;
use App\Models\Tenancy;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TenancyOccupantController extends Controller
{
    public function store(AddOccupantRequest $request, Tenancy $tenancy, AddOccupant $addOccupant): RedirectResponse
    {
        $addOccupant->handle(
            $tenancy,
            $request->string('nickname')->toString(),
            CarbonImmutable::parse($request->string('moved_in_at')->toString()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Occupant added.')]);

        return to_route('owner.tenancies.show', $tenancy);
    }
}
