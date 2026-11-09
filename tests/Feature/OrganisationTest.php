<?php

use App\Models\Organisation;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validOrganisationPayload(array $overrides = []): array
{
    return [
        'name' => 'Wilde Melk',
        'address' => 'Tienboerenweg 22',
        'postal_code' => '3641 RA',
        'city' => 'Mijdrecht',
        'email' => 'info@wildemelk.test',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function (string $method, string $routeName) {
    $organisation = Organisation::factory()->create();

    $response = $this->{$method}(route($routeName, $organisation));

    $response->assertRedirect(route('login'));
    expect(Organisation::query()->whereKey($organisation)->exists())->toBeTrue();
})->with([
    'overview' => ['get', 'organisations.index'],
    'create form' => ['get', 'organisations.create'],
    'store' => ['post', 'organisations.store'],
    'edit form' => ['get', 'organisations.edit'],
    'update' => ['put', 'organisations.update'],
    'destroy' => ['delete', 'organisations.destroy'],
]);

test('the overview lists all organisations sorted by name', function () {
    Organisation::factory()->create(['name' => 'Wilde Melk']);
    Organisation::factory()->create(['name' => 'Bakkerij Bol']);

    $response = $this->actingAs(User::factory()->create())->get(route('organisations.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('organisations/Index')
        ->has('organisations', 2)
        ->where('organisations.0.name', 'Bakkerij Bol')
        ->where('organisations.1.name', 'Wilde Melk'));
});

test('the create form can be displayed', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('organisations.create'));

    $response->assertInertia(fn (AssertableInertia $page) => $page->component('organisations/Create'));
});

test('the edit form receives the organisation', function () {
    $organisation = Organisation::factory()->create();

    $response = $this->actingAs(User::factory()->create())->get(route('organisations.edit', $organisation));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('organisations/Edit')
        ->where('organisation.id', $organisation->id));
});

test('an organisation can be created', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('organisations.store'), validOrganisationPayload());

    $response->assertSessionHasNoErrors()->assertRedirect(route('organisations.index'));

    $this->assertDatabaseHas('organisations', validOrganisationPayload());
});

test('required fields are validated', function () {
    $response = $this->actingAs(User::factory()->create())->post(route('organisations.store'), []);

    $response->assertSessionHasErrors([
        'name' => 'Vul een naam in.',
        'address' => 'Vul een adres in.',
        'postal_code' => 'Vul een postcode in.',
        'city' => 'Vul een plaats in.',
        'email' => 'Vul een e-mailadres in.',
    ]);
    expect(Organisation::query()->count())->toBe(0);
});

test('an invalid email address is rejected', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('organisations.store'), validOrganisationPayload(['email' => 'geen-email']));

    $response->assertSessionHasErrors(['email' => 'Vul een geldig e-mailadres in.']);
    expect(Organisation::query()->count())->toBe(0);
});

test('an organisation can be updated', function () {
    $organisation = Organisation::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->put(route('organisations.update', $organisation), validOrganisationPayload(['name' => 'Nieuwe Naam']));

    $response->assertSessionHasNoErrors()->assertRedirect(route('organisations.index'));

    expect($organisation->refresh()->name)->toBe('Nieuwe Naam')
        ->and($organisation->city)->toBe('Mijdrecht');
});

test('an organisation can be deleted', function () {
    $organisation = Organisation::factory()->create();

    $response = $this->actingAs(User::factory()->create())->delete(route('organisations.destroy', $organisation));

    $response->assertRedirect(route('organisations.index'));
    $this->assertModelMissing($organisation);
});
