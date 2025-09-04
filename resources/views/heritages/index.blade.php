@extends('layouts.app')

@section('title', 'Heritages')

@section('content')
<div class="container py-4">

    {{-- Título principal --}}
    <div class="d-flex justify-content-center mb-4">
        <div class="bg-dark text-white rounded-3 px-4 py-2 fw-bold fs-4 text-center shadow-sm">
            Listing Page Layout
        </div>
    </div>

    {{-- Layout principal: Lista + Sidebar --}}
    <div style="display: flex; flex-direction: row; gap: 2.5rem; align-items: flex-start;">

        {{-- Listagem de artigos --}}
        <div style="flex: 1 1 auto; min-width: 0;">
            <div class="card shadow-sm border-0">
                <div class="card-body">

                    {{-- Subpastas --}}
                    @if (!empty($folders))
                        <h5 class="fw-bold mb-3">Subfolders</h5>
                        <ul class="mb-4 ps-3 list-unstyled">
                            @foreach ($folders as $folder)
                                <li class="mb-2">
                                    <a class="link-primary text-decoration-none fw-semibold"
                                       href="{{ url('01_module_c/heritages/' . trim($path . '/' . $folder, '/')) }}">
                                        📁 {{ ucfirst($folder) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Páginas --}}
                    @if (!empty($pages))
                        <h5 class="fw-bold mb-3">Pages</h5>
                        <ul class="list-unstyled">
                            @foreach ($pages as $page)
                                <li class="mb-4 pb-3 border-bottom">
                                    <a class="d-block fw-semibold fs-5 text-dark text-decoration-none"
                                       href="{{ url('01_module_c/heritages/' . $page['path']) }}">
                                        {{ is_array($page['title'] ?? '') ? implode(' ', $page['title']) : ($page['title'] ?? 'No Title') }}
                                    </a>
                                    @if (!empty($page['summary']))
                                        <p class="text-muted small mb-2">
                                            {{ is_array($page['summary'] ?? '') ? implode(' ', $page['summary']) : ($page['summary'] ?? '') }}
                                        </p>
                                    @endif
                                    <small class="text-secondary">
                                        {{ \Carbon\Carbon::parse($page['date'])->format('d/m/Y') }}
                                    </small>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted fst-italic">No pages found in this folder.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Barra lateral de pesquisa --}}
        <aside style="flex: 0 0 280px;">
            <div class="card shadow-lg border-0" style="background: #f8f9fa00;">
                <div class="card-body">
                    <h2 class="h6 text-uppercase fw-bold text-secondary mb-3">Search</h2>
                    <form action="{{ url('01_module_c/search') }}" method="GET" class="d-flex flex-column gap-2">
                        <input type="text" name="q" class="form-control" placeholder="Type keyword..." value="{{ request('q') }}">
                        <button type="submit" class="btn btn-dark w-100">Search</button>
                    </form>
                </div>
            </div>
        </aside>

    </div>

</div>
@endsection
