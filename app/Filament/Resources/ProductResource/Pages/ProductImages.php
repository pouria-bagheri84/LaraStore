<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ProductImages extends EditRecord
{
    protected static ?string $model = Product::class;

    protected static ?string $title = 'Images';

    protected static string $resource = ProductResource::class;

    protected static ?string $navigationIcon = 'heroicon-c-photo';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                SpatieMediaLibraryFileUpload::make('images')
                    ->label(false)
                    ->image()
                    ->multiple()
                    ->openable()
                    ->panelLayout('grid')
                    ->reorderable()
                    ->collection('images')
                    ->appendFiles()
                    ->preserveFilenames()
                    ->columnSpan(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

}