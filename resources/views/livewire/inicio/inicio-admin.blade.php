<div>
    @can('inicio-admin-inicio')
        @livewire(\App\Livewire\Inicio\Cards\Cards::class)
    @endcan

    @can('inicio-docente-inicio')
        <h2>Estás registrado como un docente</h2>

        @php
            // Obtener el usuario autenticado
            $usuario = Auth::user();
        @endphp

        @if($usuario)
            <p>Nombre: {{ $usuario->name }}</p>
            <p>Correo: {{ $usuario->email }}</p>
            <p>ID: {{ $usuario->id }}</p>
            <!-- Agrega más campos según lo necesites -->
        @else
            <p>No se encontró información del usuario autenticado.</p>
        @endif
    @endcan
</div>

