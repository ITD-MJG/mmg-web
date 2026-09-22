<?php

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Filament\Widgets\NewInquiryCount;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

it('lists inquiries with status filter', function () {
    $new = Inquiry::factory()->create(['status' => InquiryStatus::New]);
    $read = Inquiry::factory()->create(['status' => InquiryStatus::Read]);

    Livewire::test(ListInquiries::class)
        ->assertCanSeeTableRecords([$new, $read])
        ->filterTable('status', InquiryStatus::Read)
        ->assertCanSeeTableRecords([$read])
        ->assertCanNotSeeTableRecords([$new]);
});

it('filters inquiries by the created-at date range', function () {
    $old = Inquiry::factory()->create(['created_at' => now()->subMonth()]);
    $recent = Inquiry::factory()->create(['created_at' => now()]);

    Livewire::test(ListInquiries::class)
        ->filterTable('created_at', [
            'from' => now()->subWeek()->toDateString(),
            'until' => now()->addDay()->toDateString(),
        ])
        ->assertCanSeeTableRecords([$recent])
        ->assertCanNotSeeTableRecords([$old]);
});

it('marks an inquiry as replied', function () {
    $inquiry = Inquiry::factory()->create(['status' => InquiryStatus::New]);

    Livewire::test(ListInquiries::class)
        ->callAction(TestAction::make('markReplied')->table($inquiry));

    expect($inquiry->fresh()->status)->toBe(InquiryStatus::Replied);
});

it('marks a new inquiry as read and hides the action once read', function () {
    $new = Inquiry::factory()->create(['status' => InquiryStatus::New]);

    Livewire::test(ListInquiries::class)
        ->assertActionVisible(TestAction::make('markRead')->table($new))
        ->callAction(TestAction::make('markRead')->table($new));

    expect($new->fresh()->status)->toBe(InquiryStatus::Read);

    // Idempotence: the action is gone once the inquiry is no longer new, so
    // it cannot be re-run against a record it does not apply to.
    $read = Inquiry::factory()->create(['status' => InquiryStatus::Read]);

    Livewire::test(ListInquiries::class)
        ->assertActionHidden(TestAction::make('markRead')->table($read));
});

it('hides the reply action once an inquiry has been replied to', function () {
    $replied = Inquiry::factory()->create(['status' => InquiryStatus::Replied]);

    Livewire::test(ListInquiries::class)
        ->assertActionHidden(TestAction::make('markReplied')->table($replied));
});

it('is a read-only resource with no create route', function () {
    expect(InquiryResource::canCreate())->toBeFalse();
    expect(array_keys(InquiryResource::getPages()))->not->toContain('create');

    $this->get('/admin/inquiries/create')->assertNotFound();
});

it('lets an admin reach the inquiry inbox', function () {
    Inquiry::factory()->create();

    $this->actingAs($this->admin)->get('/admin/inquiries')->assertOk();
});

it('blocks an editor from the inquiry inbox', function () {
    $inquiry = Inquiry::factory()->create();

    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor);

    // The gate is role-based, not route-based: an editor is denied at the
    // resource as well as at both URLs.
    expect(InquiryResource::canViewAny())->toBeFalse();

    $this->get('/admin/inquiries')->assertForbidden();
    $this->get("/admin/inquiries/{$inquiry->id}")->assertForbidden();
});

it('renders the notification for an inquiry with no product', function () {
    $inquiry = Inquiry::factory()->create(['product_id' => null]);

    $html = (new InquiryReceived($inquiry))->render();

    // Asserted against the escaped form, because the mail view renders through
    // Blade and escapes. Asserting the raw name passed only while the generated
    // name happened to contain no apostrophe: faker produces one in about 1.6%
    // of names, so this failed roughly once in sixty full-suite runs and never
    // in isolation. That made the suite intermittently red for a reason no
    // single-file run could reproduce.
    expect($html)->toContain(e($inquiry->name));
    expect($html)->toContain(e($inquiry->email));
});

it('counts new inquiries on the dashboard widget', function () {
    $stats = fn (): array => (function (): array {
        return $this->getStats();
    })->call(new NewInquiryCount);

    expect($stats()[0]->getValue())->toBe(0);

    Inquiry::factory()->count(2)->create(['status' => InquiryStatus::New]);
    Inquiry::factory()->create(['status' => InquiryStatus::Replied]);

    expect($stats()[0]->getValue())->toBe(2);
});

it('shows the inquiry widget on the dashboard for an admin', function () {
    // `canView()` is what the dashboard's widget filter consults, so the widget
    // being absent from an editor's page below is a real gate and not a
    // side-effect of the widget being lazy or failing to render.
    expect(NewInquiryCount::canView())->toBeTrue();

    $this->get('/admin')->assertSeeLivewire(NewInquiryCount::class);
});

it('hides the inquiry widget from an editor', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor);

    expect(NewInquiryCount::canView())->toBeFalse();

    $this->get('/admin')->assertDontSeeLivewire(NewInquiryCount::class);
});

it('renders the inbox and a record detail page', function () {
    $withProduct = Inquiry::factory()->create(['product_id' => Product::factory()]);
    Inquiry::factory()->create(['product_id' => null]);

    $this->get('/admin/inquiries')->assertOk()->assertSee($withProduct->name);
    $this->get("/admin/inquiries/{$withProduct->id}")->assertOk()->assertSee($withProduct->email);

    // The dashboard is what renders NewInquiryCount, so an error in the widget
    // fails here rather than only in production.
    $this->get('/admin')->assertOk();
});
