<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Translations')
                    ->tabs([
                        Tabs\Tab::make('Indonesia')
                            ->schema([
                                TextInput::make('title.id')
                                    ->label('Judul')
                                    ->required(),
                                self::body('id', 'Isi'),
                            ]),
                        Tabs\Tab::make('English')
                            ->schema([
                                TextInput::make('title.en')
                                    ->label('Title')
                                    ->required(),
                                self::body('en', 'Body'),
                            ]),
                    ])
                    ->columnSpanFull(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                Toggle::make('is_published')
                    ->label('Terbitkan')
                    ->default(false),
            ]);
    }

    /**
     * Spec §9 Layer 2 wants a clean heading hierarchy so AI crawlers can lift
     * sections: H2/H3 only. H1 is reserved for the page title and H4+ fragments
     * the outline, so neither appears in the toolbar.
     *
     * Limitation: `toolbarButtons()` is the only heading-level lever Filament 5
     * exposes. The bundled TipTap `heading` extension still registers levels
     * 1–6, so markup pasted from elsewhere keeps its original level; the editor
     * simply offers no button to author one.
     */
    private static function body(string $locale, string $label): RichEditor
    {
        return RichEditor::make("body.{$locale}")
            ->label($label)
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'link'],
                ['h2', 'h3'],
                ['bulletList', 'orderedList', 'blockquote'],
                ['undo', 'redo'],
            ])
            ->floatingToolbars([
                'paragraph' => ['bold', 'italic', 'underline', 'link'],
                'heading' => ['h2', 'h3'],
            ])
            ->columnSpanFull();
    }
}
