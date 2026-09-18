<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static ?string $slug = 'settings';

    protected static ?string $title = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected string $view = 'filament.pages.manage-settings';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    /**
     * Security: the `editor` role is content-only (spec §7), so settings are
     * admin-only. `canAccess()` is what Filament calls from
     * `mountCanAuthorizeAccess()`/`hydrateCanAuthorizeAccess()`, so it guards
     * the route and every Livewire request — not just the navigation item.
     */
    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->hasRole('admin') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'company_name' => Setting::get('company_name'),
            'contact_email' => Setting::get('contact_email'),
            'contact_phone' => Setting::get('contact_phone'),
            'whatsapp' => Setting::get('whatsapp'),
            'address' => Setting::get('address'),
            'default_meta_title' => Setting::get('default_meta_title'),
            'default_meta_description' => Setting::get('default_meta_description'),
            'socials' => Setting::get('socials', []),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Perusahaan')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Nama perusahaan')
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3),
                    ]),

                Section::make('Kontak')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Email kontak')
                            ->helperText('Penerima notifikasi permintaan penawaran. Periksa ejaannya — email yang salah membuat permintaan pelanggan tidak terkirim.')
                            ->email()
                            ->maxLength(255),
                        // Free text on purpose: Indonesian numbers arrive as
                        // `+62…`, `08…`, with spaces, dashes, and parentheses.
                        // The value is stored exactly as typed and never
                        // normalised, so no format rule is imposed here.
                        TextInput::make('contact_phone')
                            ->label('Telepon')
                            ->type('tel')
                            ->maxLength(255),
                        TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->type('tel')
                            ->maxLength(255),
                        // KeyValue, not a Repeater: `Setting` stores the value as
                        // JSON and `Setting::get()` must return an array. KeyValue
                        // reads and writes an associative map natively, so the
                        // `['linkedin' => '…']` shape survives untouched. A
                        // Repeater dehydrates to a list of rows and would need the
                        // same `mutateDehydratedStateUsing()` override that
                        // `ProductForm` had to add for `specs`.
                        KeyValue::make('socials')
                            ->label('Media sosial')
                            ->keyLabel('Platform')
                            ->valueLabel('Tautan')
                            ->addActionLabel('Tambah media sosial'),
                    ]),

                Section::make('Meta bawaan')
                    ->description('Dipakai halaman yang belum punya meta sendiri.')
                    ->schema([
                        TextInput::make('default_meta_title')
                            ->label('Judul meta')
                            ->maxLength(255),
                        Textarea::make('default_meta_description')
                            ->label('Deskripsi meta')
                            ->rows(3)
                            ->maxLength(255),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Simpan')
                                ->submit('save'),
                        ]),
                    ]),
            ]);
    }

    /**
     * Upserts only the keys this form owns. A blanket replace would delete
     * settings written elsewhere (now or by a later task) that the page has no
     * field for.
     */
    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->success()
            ->title('Pengaturan disimpan')
            ->send();
    }
}
