<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganisationRequest;
use App\Models\Organisation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganisationController extends Controller
{
    /**
     * Show the overview of all organisations.
     */
    public function index(): Response
    {
        return Inertia::render('organisations/Index', [
            'organisations' => Organisation::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating an organisation.
     */
    public function create(): Response
    {
        return Inertia::render('organisations/Create');
    }

    /**
     * Store a new organisation.
     */
    public function store(OrganisationRequest $request): RedirectResponse
    {
        Organisation::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organisatie aangemaakt.')]);

        return to_route('organisations.index');
    }

    /**
     * Show the form for editing an organisation.
     */
    public function edit(Organisation $organisation): Response
    {
        return Inertia::render('organisations/Edit', [
            'organisation' => $organisation,
        ]);
    }

    /**
     * Update an organisation.
     */
    public function update(OrganisationRequest $request, Organisation $organisation): RedirectResponse
    {
        $organisation->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organisatie bijgewerkt.')]);

        return to_route('organisations.index');
    }

    /**
     * Delete an organisation.
     */
    public function destroy(Organisation $organisation): RedirectResponse
    {
        $organisation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organisatie verwijderd.')]);

        return to_route('organisations.index');
    }
}
