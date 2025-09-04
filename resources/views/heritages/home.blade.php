@extends('layouts.app')

@section('title', 'Lyon Heritage Sites')

@section('content')
<div class="container mx-auto py-8 text-center">
    <h1 class="text-4xl font-bold mb-8">Bem-vindo a Lyon Heritage Sites</h1>
    
    <form action="{{ url('01_module_c/search') }}" method="GET" class="max-w-md mx-auto mb-8">
        <div class="flex">
            <input type="text" name="q" placeholder="Digite palavras-chave..." 
                   class="flex-1 border border-gray-300 rounded-l px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                   value="{{ request('q') }}">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-r hover:bg-blue-700 transition-colors">
                Pesquisar
            </button>
        </div>
        <p class="text-sm text-gray-600 mt-2">Use / para separar múltiplas palavras-chave</p>
    </form>
    
    <div class="space-y-4">
        <p class="text-lg">
            <a href="{{ url('01_module_c/tags') }}" class="text-blue-600 hover:underline">
                Ver todas as tags
            </a>
        </p>
        
        <p class="text-lg">
            <a href="{{ url('01_module_c/heritages') }}" class="text-blue-600 hover:underline">
                Explorar todos os patrimônios
            </a>
        </p>
    </div>
</div>
@endsection