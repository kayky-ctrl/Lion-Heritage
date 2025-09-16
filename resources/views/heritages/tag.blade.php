@extends('layouts.app')

@section('title', 'Tag: ' . $tag)

@section('content')
<div class="container mx-auto py-8">
    <h2 class="text-2xl font-bold mb-6">Posts com a tag: {{ $tag }}</h2>

    @if(!empty($posts) && count($posts) > 0)
        <div class="grid gap-4">
            @foreach($posts as $post)
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-xl font-semibold mb-2">
                        <a href="{{ url('01_module_c/heritages/' . $post['path']) }}"
                           class="text-blue-600 hover:underline">
                            {{ $post['title'] }}
                        </a>
                    </h3>
                    
                    @if (!empty($post['summary']))
                        <p class="text-gray-600 mb-2">{{ $post['summary'] }}</p>
                    @endif
                    
                    <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($post['date'])->format('d/m/Y') }}</p>
                    
                    @if (!empty($post['tags']) && is_array($post['tags']) && count($post['tags']) > 0)
                        <div class="mt-2">
                            @foreach ($post['tags'] as $postTag)
                                @if (!empty(trim($postTag)))
                                    <a href="{{ url('01_module_c/tags/' . urlencode($postTag)) }}" 
                                       class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2 mb-2">
                                        {{ $postTag }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <p class="text-gray-600 text-lg">Nenhum post encontrado com esta tag.</p>
            <a href="{{ url('01_module_c/tags') }}" class="text-blue-600 hover:underline mt-2 inline-block">
                Ver todas as tags
            </a>
        </div>
    @endif
</div>
@endsection