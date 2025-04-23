<?php

namespace App\Livewire\Personal\Contacto;

use App\Models\Personal\Contacto;
use App\Services\Correos\EmailBuilder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ViewAction;
use Filament\Notifications\Notification;

use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Mail;


class ListContactos extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Contacto::query())
            ->columns([
                Tables\Columns\TextColumn::make('nombres')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellidos')
                    ->searchable(),
                Tables\Columns\TextColumn::make('institucion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([

                ViewAction::make()
                    ->form([
                        TextInput::make('nombres')
                            ->label('Nombres')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('apellidos')
                            ->label('Apellidos')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('institucion')
                            ->label('Institucion')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->maxLength(255),
                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->required()
                            ->tel()
                            ->maxLength(15),

                        Textarea::make('mensaje')
                            ->columnSpanFull()

                            ->rows(5)
                            ->required()
                            ->cols(15)
                        // ...
                    ]),
                Action::make('responder')
                    ->label('Responder')
                    ->form([
                        Textarea::make('mensaje')
                            ->columnSpanFull()

                            ->rows(5)
                            ->required()
                            ->cols(15)
                    ])
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->modalHeading(
                        function (Contacto $contacto) {
                            return 'Responder a ' . $contacto->nombres . ' ' . $contacto->apellidos;
                        }
                    )
                    ->modalSubheading(
                        function (Contacto $contacto) {
                            return 'Le enviarás un correo electrónico a ' . $contacto->email . ' en nombre de ' . config('app.name');
                        }

                    )
                    ->action(function (Contacto $contacto, array $data) {
                        $correo = (new EmailBuilder())
                            ->setEstadoNombre("")
                            ->setEmpleadoNombre($contacto->nombres . ' ' . $contacto->apellidos)
                            ->setNombreProyecto("")
                            ->setActionUrl(route('home'))
                            ->setLogoUrl(asset('images/logo_nuevo.png'))
                            ->setAppName('NEXO-UNAH')
                            ->setMensaje($data['mensaje'])
                            ->setSubject('Respuesta a su consulta')
                            ->build();

                        Mail::to($contacto->email)->queue($correo);
                        Notification::make()
                            ->title('¡Éxito!')
                            ->body('Su mensaje ha sido enviado correctamente.')
                            ->success()
                            ->send();
                    })


            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.personal.contacto.list-contactos');
    }
}
