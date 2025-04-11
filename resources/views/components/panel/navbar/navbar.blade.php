<x-panel.navbar.navbar-container>
    @php
     $menu = config('navbar');

    @endphp

    @foreach($menu as $grupo)
        <x-panel.navbar.group-item :titulo="$grupo['titulo']" :icono="$grupo['icono']">
            @foreach($grupo['items'] as $item)
                <x-panel.navbar.one-item 
                    :titulo="$item['titulo']"
                    :route="$item['route']"
                    :routes="$item['routes'] ?? []"
                    :permisos="$item['permisos'] ?? []"
                    :icono="$item['icono'] ?? null"
                    :children="$item['children'] ?? []"
                    :parametro="$item['parametro'] ?? null"
                />
            @endforeach
        </x-panel.navbar.group-item>
    @endforeach
</x-panel.navbar.navbar-container>
