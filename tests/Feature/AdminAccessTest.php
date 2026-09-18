<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Database\Seeders\FirstAdminSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('creates the admin and editor roles', function () {
    $this->seed(RoleSeeder::class);

    expect(Role::pluck('name')->all())->toContain('admin', 'editor');
});

it('lets an admin reach the panel', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('lets an editor reach the panel', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('blocks a user with no role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('lets an admin reach the user resource', function () {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin/users')->assertOk();
});

it('blocks an editor from the user resource', function () {
    $this->seed(RoleSeeder::class);

    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)->get('/admin/users')->assertForbidden();
});

it('blocks an editor from the user resource sub-routes', function () {
    $this->seed(RoleSeeder::class);

    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $target = User::factory()->create();

    $this->actingAs($editor)->get('/admin/users/create')->assertForbidden();
    $this->actingAs($editor)->get("/admin/users/{$target->id}/edit")->assertForbidden();
});

it('stores a hashed password when creating a user through the resource', function () {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New Editor',
            'email' => 'editor@example.com',
            'password' => 'rahasia-sekali',
            'roles' => [Role::findByName('editor')->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::where('email', 'editor@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->password)->not->toBe('rahasia-sekali')
        ->and(Hash::check('rahasia-sekali', $created->password))->toBeTrue()
        ->and($created->hasRole('editor'))->toBeTrue();
});

it('leaves the password unchanged when editing a user with a blank password', function () {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    $target = User::factory()->create(['password' => 'original-password']);
    $originalHash = $target->fresh()->password;

    Livewire::test(EditUser::class, ['record' => $target->getKey()])
        ->fillForm(['password' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->password)->toBe($originalHash)
        ->and(Hash::check('original-password', $target->fresh()->password))->toBeTrue();
});

it('creates the first admin from env credentials', function () {
    config(['app.admin_email' => 'boss@example.com', 'app.admin_password' => 's3cret-pass']);

    $this->seed(RoleSeeder::class);
    $this->seed(FirstAdminSeeder::class);

    $admin = User::where('email', 'boss@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and(Hash::check('s3cret-pass', $admin->password))->toBeTrue();
});

it('does not touch an existing user when seeding the first admin again', function () {
    config(['app.admin_email' => 'boss@example.com', 'app.admin_password' => 's3cret-pass']);

    $this->seed(RoleSeeder::class);
    $this->seed(FirstAdminSeeder::class);

    $admin = User::where('email', 'boss@example.com')->first();
    $admin->update(['password' => 'changed-by-hand']);
    $changedHash = $admin->fresh()->password;

    $this->seed(FirstAdminSeeder::class);

    expect(User::count())->toBe(1)
        ->and(User::where('email', 'boss@example.com')->first()->password)->toBe($changedHash);
});

it('skips the first admin when the credentials are unset', function () {
    config(['app.admin_email' => null, 'app.admin_password' => null]);

    $this->seed(RoleSeeder::class);
    $this->seed(FirstAdminSeeder::class);

    expect(User::count())->toBe(0);
});
