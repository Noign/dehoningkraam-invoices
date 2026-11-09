<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validProductPayload(array $overrides = []): array
{
    return [
        'name' => 'Bloemenhoning',
        'description' => 'Honing van de lokale bijen.',
        'unit' => 'st',
        'price' => '19.99',
        'vat_percentage' => '9',
        ...$overrides,
    ];
}

test('guests are redirected to the login page', function (string $method, string $routeName) {
    $product = Product::factory()->create();

    $response = $this->{$method}(route($routeName, $product));

    $response->assertRedirect(route('login'));
    expect(Product::query()->whereKey($product)->exists())->toBeTrue();
})->with([
    'overview' => ['get', 'products.index'],
    'create form' => ['get', 'products.create'],
    'store' => ['post', 'products.store'],
    'edit form' => ['get', 'products.edit'],
    'update' => ['put', 'products.update'],
    'destroy' => ['delete', 'products.destroy'],
]);

test('the overview lists all products sorted by name', function () {
    Product::factory()->create(['name' => 'Propolis']);
    Product::factory()->create(['name' => 'Bloemenhoning']);

    $response = $this->actingAs(User::factory()->create())->get(route('products.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('products/Index')
        ->has('products', 2)
        ->where('products.0.name', 'Bloemenhoning')
        ->where('products.1.name', 'Propolis'));
});

test('the create form offers the allowed vat percentages', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('products.create'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('products/Create')
        ->where('vatPercentages', Product::VAT_PERCENTAGES));
});

test('the edit form receives the product', function () {
    $product = Product::factory()->create();

    $response = $this->actingAs(User::factory()->create())->get(route('products.edit', $product));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('products/Edit')
        ->where('product.id', $product->id)
        ->where('vatPercentages', Product::VAT_PERCENTAGES));
});

test('a product can be created with its price stored in cents', function (string $price, int $expectedCents) {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('products.store'), validProductPayload(['price' => $price]));

    $response->assertSessionHasNoErrors()->assertRedirect(route('products.index'));

    $product = Product::query()->sole();

    expect($product->name)->toBe('Bloemenhoning')
        ->and($product->description)->toBe('Honing van de lokale bijen.')
        ->and($product->unit)->toBe('st')
        ->and($product->price_including_vat_in_cents)->toBe($expectedCents)
        ->and($product->vat_percentage)->toBe(9)
        ->and($product->image_path)->toBeNull();
})->with([
    'two decimals' => ['19.99', 1999],
    'floating point edge' => ['1.15', 115],
    'whole euros' => ['5', 500],
    'free' => ['0', 0],
]);

test('a product can be created with an image', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->create())->post(route('products.store'), validProductPayload([
        'image' => UploadedFile::fake()->image('honing.jpg'),
    ]))->assertSessionHasNoErrors();

    $product = Product::query()->sole();

    Storage::disk('public')->assertExists($product->image_path);
    expect($product->image_url)->toBe(Storage::disk('public')->url($product->image_path));
});

test('required fields are validated', function () {
    $response = $this->actingAs(User::factory()->create())->post(route('products.store'), []);

    $response->assertSessionHasErrors([
        'name' => 'Vul een naam in.',
        'unit' => 'Vul een eenheid in.',
        'price' => 'Vul een prijs in.',
        'vat_percentage' => 'Kies een btw-percentage.',
    ]);
    expect(Product::query()->count())->toBe(0);
});

test('invalid values are rejected', function (array $overrides, string $field, string $message) {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('products.store'), validProductPayload($overrides));

    $response->assertSessionHasErrors([$field => $message]);
    expect(Product::query()->count())->toBe(0);
})->with([
    'non-numeric price' => [['price' => 'gratis'], 'price', 'Vul een geldige prijs in.'],
    'too many decimals' => [['price' => '1.999'], 'price', 'De prijs mag maximaal twee decimalen hebben.'],
    'negative price' => [['price' => '-1'], 'price', 'De prijs mag niet negatief zijn.'],
    'price too high' => [['price' => '10000000'], 'price', 'De prijs is te hoog.'],
    'unknown vat percentage' => [['vat_percentage' => '6'], 'vat_percentage', 'Kies een geldig btw-percentage.'],
]);

test('uploads that are not allowed images are rejected', function (Closure $makeFile, string $message) {
    Storage::fake('public');

    $response = $this->actingAs(User::factory()->create())
        ->post(route('products.store'), validProductPayload(['image' => $makeFile()]));

    $response->assertSessionHasErrors(['image' => $message]);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
})->with([
    'not an image' => [fn () => UploadedFile::fake()->create('factuur.pdf', 100, 'application/pdf'), 'Het bestand moet een afbeelding zijn.'],
    'svg' => [fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'), 'Het bestand moet een afbeelding zijn.'],
    'too large' => [fn () => UploadedFile::fake()->image('honing.jpg')->size(5121), 'De afbeelding mag maximaal 5 MB groot zijn.'],
]);

test('a product is not created when its image cannot be stored', function () {
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('putFileAs')->andReturnFalse();
    Storage::shouldReceive('disk')->with('public')->andReturn($disk);

    $response = $this->actingAs(User::factory()->create())->post(route('products.store'), validProductPayload([
        'image' => UploadedFile::fake()->image('honing.jpg'),
    ]));

    $response->assertSessionHasErrors(['image' => 'De afbeelding kon niet worden opgeslagen. Probeer het opnieuw.']);
    expect(Product::query()->count())->toBe(0);
});

test('a product can be updated while keeping its image', function () {
    Storage::fake('public');
    $imagePath = UploadedFile::fake()->image('oud.jpg')->store('products', 'public');
    $product = Product::factory()->create(['image_path' => $imagePath]);

    $response = $this->actingAs(User::factory()->create())
        ->put(route('products.update', $product), validProductPayload(['name' => 'Heidehoning']));

    $response->assertSessionHasNoErrors()->assertRedirect(route('products.index'));

    expect($product->refresh()->name)->toBe('Heidehoning')
        ->and($product->price_including_vat_in_cents)->toBe(1999)
        ->and($product->image_path)->toBe($imagePath);
    Storage::disk('public')->assertExists($imagePath);
});

test('uploading a new image replaces the old file', function () {
    Storage::fake('public');
    $oldImagePath = UploadedFile::fake()->image('oud.jpg')->store('products', 'public');
    $product = Product::factory()->create(['image_path' => $oldImagePath]);

    $this->actingAs(User::factory()->create())->put(route('products.update', $product), validProductPayload([
        'image' => UploadedFile::fake()->image('nieuw.jpg'),
    ]))->assertSessionHasNoErrors();

    expect($product->refresh()->image_path)->not->toBe($oldImagePath);
    Storage::disk('public')->assertMissing($oldImagePath);
    Storage::disk('public')->assertExists($product->image_path);
});

test('a product can be deleted together with its image', function () {
    Storage::fake('public');
    $imagePath = UploadedFile::fake()->image('honing.jpg')->store('products', 'public');
    $product = Product::factory()->create(['image_path' => $imagePath]);

    $response = $this->actingAs(User::factory()->create())->delete(route('products.destroy', $product));

    $response->assertRedirect(route('products.index'));
    $this->assertModelMissing($product);
    Storage::disk('public')->assertMissing($imagePath);
});

test('updating or deleting an unknown product returns not found', function (string $method) {
    $response = $this->actingAs(User::factory()->create())
        ->{$method}(route('products.update', 999), validProductPayload());

    $response->assertNotFound();
})->with(['put', 'delete']);
