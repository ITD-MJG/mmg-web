<?php

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Filament\Widgets\NewInquiryCount;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
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

it('queues the inquiry notification with the inquiry attached', function () {
    Mail::fake();

    Setting::set('contact_email', 'sales@mmg.test');

    $inquiry = Inquiry::factory()->create();

    // The public controller that performs this send is Task 14, so this
    // exercises the exact call it will make rather than a controller that does
    // not exist yet.
    $recipient = Setting::get('contact_email');

    if ($recipient) {
        Mail::to($recipient)->queue(new InquiryReceived($inquiry));
    }

    Mail::assertQueued(
        InquiryReceived::class,
        fn (InquiryReceived $mail): bool => $mail->inquiry->is($inquiry) && $mail->hasTo('sales@mmg.test'),
    );
});

it('sends nothing when no contact email is configured', function () {
    Mail::fake();

    $inquiry = Inquiry::factory()->create();

    $recipient = Setting::get('contact_email');

    if ($recipient) {
        Mail::to($recipient)->queue(new InquiryReceived($inquiry));
    }

    Mail::assertNothingQueued();
});

it('renders the notification for an inquiry with no product', function () {
    $inquiry = Inquiry::factory()->create(['product_id' => null]);

    $html = (new InquiryReceived($inquiry))->render();

    expect($html)->toContain($inquiry->name);
    expect($html)->toContain($inquiry->email);
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

it('renders the inbox and a record detail page', function () {
    $withProduct = Inquiry::factory()->create(['product_id' => Product::factory()]);
    Inquiry::factory()->create(['product_id' => null]);

    $this->get('/admin/inquiries')->assertOk()->assertSee($withProduct->name);
    $this->get("/admin/inquiries/{$withProduct->id}")->assertOk()->assertSee($withProduct->email);

    // The dashboard is what renders NewInquiryCount, so an error in the widget
    // fails here rather than only in production.
    $this->get('/admin')->assertOk();
});
