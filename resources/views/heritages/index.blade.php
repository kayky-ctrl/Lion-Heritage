@extends('layouts.app')

@section('title', 'Heritages')

@section('content')
<div class="container py-4">

    <h1 class="mb-4">Heritage Sites</h1>

    {{-- Subpastas --}}
    @if (!empty($folders))
        <h2 class="h5 mt-3">Subfolders</h2>
        <ul class="list-group mb-4">
            @foreach ($folders as $folder)
                <li class="list-group-item">
                    <a href="{{ url('01_module_c/heritages/' . trim($path . '/' . $folder, '/')) }}">
                        📁 {{ ucfirst($folder) }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Páginas --}}
    @if (!empty($pages))
        <h2 class="h5 mt-3">Pages</h2>
        <div class="list-group">
            @foreach ($pages as $page)
                <a href="{{ url('01_module_c/heritages/' . $page['path']) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">{{ is_array($page['title'] ?? '') ? implode(' ', $page['title']) : ($page['title'] ?? 'No Title') }}</h5>
                        <small>{{ \Carbon\Carbon::parse($page['date'])->format('d/m/Y') }}</small>
                    </div>
                    
                    @if (!empty($page['summary']))
                        <p class="mb-1">{{ is_array($page['summary'] ?? '') ? implode(' ', $page['summary']) : ($page['summary'] ?? '') }}</p>
                    @endif

                    {{-- Exibir tags se existirem --}}
                    @if (!empty($page['tags']) && is_array($page['tags']) && count($page['tags']) > 0)
                        <small>
                            @foreach ($page['tags'] as $tag)
                                @if (!empty(trim($tag)))
                                    <a href="{{ url('01_module_c/tags/' . urlencode($tag)) }}" class="badge bg-secondary text-decoration-none">
                                        {{ $tag }}
                                    </a>
                                @endif
                            @endforeach
                        </small>
                    @endif
                </a>
            @endforeach
        </div>
    @else
        <p class="text-muted">No pages found in this folder.</p>
    @endif

</div>
@endsection