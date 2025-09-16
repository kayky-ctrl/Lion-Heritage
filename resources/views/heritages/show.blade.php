@extends('layouts.app')

@section('title', $post['title'] ?? 'Heritage')

@section('meta')
    <meta property="og:title" content="{{ $post['title'] }}">
    <meta property="og:description" content="{{ $post['summary'] ?? substr(strip_tags($post['content']), 0, 160) }}">
    @if (!empty($post['cover']))
        <meta property="og:image" content="{{ url($post['cover']) }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
@endsection

@section('styles')
    <style>
        body {
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        .cover-container {
            width: 100%;
            height: 500px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            overflow: hidden;
            --spot-size: 300px;
            --overlay-color: rgba(0, 0, 0, 0.555);
            --edge-opacity: 0.9; /* Adicionada para controlar a opacidade das bordas */
        }

        .cover-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--overlay-color);
            transition: background-color 0.2s ease-in;
            mask-image: none;
        }
        .cover-container:not(:hover)::before {
            background-color: var(--overlay-color);
        }

        .content-container {
            margin-top: -5rem;
            position: relative;
            z-index: 10;
        }

        .title-overlay {
            width: 80%;
            height: auto;
            background-color: rgb(0, 0, 0);
            color: white;
            padding: 2rem;
            box-sizing: border-box;
            text-align: center;
            margin: 0 auto;
        }

        .title-overlay h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }

        .content-layout {
            display: flex;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            align-items: flex-start;
        }

        .main-content {
            flex: 1;
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .aside-info {
            flex-grow: 0;
            flex-shrink: 0;
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .aside-info p,
        .aside-info ul {
            margin-bottom: 0.5rem;
        }

        .aside-info p strong {
            color: #333;
        }

        .aside-info ul {
            list-style: none;
            padding-left: 0;
        }

        .aside-info ul li {
            margin-bottom: 0.25rem;
        }

        .aside-info ul li a {
            color: #2a69b9;
            text-decoration: none;
        }

        .aside-info ul li a:hover {
            text-decoration: underline;
        }

        .prose p:first-of-type::first-letter {
            font-size: 4em;
            float: left;
            line-height: 1;
            margin-right: 0.1em;
            font-weight: bold;
            font-family: serif;
        }

        .prose img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .image-modal.active {
            display: flex;
        }

        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .image-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 2rem;
            background: none;
            border: none;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .content-layout {
                flex-direction: column;
            }

            .aside-info {
                flex-basis: auto;
                width: 100%;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid p-0">
        @if (!empty($post['cover']))
            <div class="cover-container" style="background-image: url('{{ url($post['cover']) }}');">
            </div>
        @endif

        <div class="content-container">
            <div class="title-overlay">
                <h1>{{ $post['title'] }}</h1>
            </div>
            <div class="content-layout">
                <div class="main-content">
                    <div class="prose max-w-none">
                        {!! $post['content'] !!}
                    </div>
                </div>
                <div class="aside-info">
                    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($post['date'])->format('Y-m-d') }}</p>
                    @if (!empty($post['tags']) && is_array($post['tags']) && count($post['tags']) > 0)
                        <p><strong>tags:</strong></p>
                        <ul>
                            @foreach ($post['tags'] as $tag)
                                @if (!empty(trim($tag)))
                                    <li>
                                        <a href="{{ url('01_module_c/tags/' . urlencode($tag)) }}"
                                            class="text-blue-600 hover:underline">{{ $tag }}</a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                    @if ($post['draft'])
                        <p class="text-red-500 font-semibold mt-4">Draft: true</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="image-modal" id="imageModal">
        <button class="image-modal-close" onclick="closeModal()">&times;</button>
        <img src="" alt="" id="modalImage">
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.prose img');
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');

            images.forEach(img => {
                img.style.cursor = 'pointer';
                img.addEventListener('click', function() {
                    modalImage.src = this.src;
                    modalImage.alt = this.alt;
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            });

            const closeModalBtn = document.querySelector('.image-modal-close');
            closeModalBtn.addEventListener('click', closeModal);

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            const coverContainer = document.querySelector('.cover-container');

            if (coverContainer) {
                // Escuta o movimento do mouse para atualizar o holofote
                coverContainer.addEventListener('mousemove', function(e) {
                    const rect = coverContainer.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    // Aplica a máscara para "abrir um buraco" no overlay
                    this.style.setProperty('mask-image',
                        `radial-gradient(circle var(--spot-size) at ${x}px ${y}px, transparent 0%, rgba(0, 0, 0, var(--edge-opacity)) 100%)`
                    );
                });

                // Quando o mouse sair da imagem, remove a máscara
                coverContainer.addEventListener('mouseleave', function() {
                    this.style.setProperty('mask-image', 'none');
                });
            }
        });

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        window.addEventListener('scroll', closeModal);
    </script>
@endsection