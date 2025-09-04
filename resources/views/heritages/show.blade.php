@extends('layouts.app')

@section('title', $post['title'] ?? 'Heritage')

@section('meta')
    <meta property="og:title" content="{{ $post['title'] }}">
    <meta property="og:description" content="{{ $post['summary'] ?? substr(strip_tags($post['content']), 0, 160) }}">
    @if (!empty($post['cover']))
        <meta property="og:image" content="{{ $post['cover'] }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
@endsection

@section('styles')
    <style>
        .cover-image {
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem;
        }

        .cover-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .cover-spotlight {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle 300px at var(--x, 50%) var(--y, 50%),
                    rgba(255, 255, 255, 0) 0%,
                    rgba(0, 0, 0, 0.8) 100%);
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .prose p:first-of-type::first-letter {
            font-size: 4em;
            float: left;
            line-height: 1;
            margin-right: 0.1em;
            font-weight: bold;
            font-family: serif;
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
    </style>
@endsection

@section('content')
    <article class="bg-white rounded-lg shadow p-6">
        @if (!empty($post['cover']))
            <div class="cover-image mb-6" id="coverImage">
                <img src="{{ $post['cover'] }}" alt="{{ $post['title'] }}">
                <div class="cover-spotlight" id="coverSpotlight"></div>
            </div>
        @endif

        <h2 class="text-3xl font-bold mb-2">{{ $post['title'] }}</h2>

        <div class="flex justify-between text-sm text-gray-600 mb-4">
            <span>{{ \Carbon\Carbon::parse($post['date'])->format('d/m/Y') }}</span>

            @if (!empty($post['tags']) && is_array($post['tags']) && count($post['tags']) > 0)
                <span>
                    Tags:
                    @foreach ($post['tags'] as $tag)
                        @if (!empty(trim($tag)))
                            <a href="{{ url('01_module_c/tags/' . urlencode($tag)) }}"
                               class="text-blue-600 hover:underline">{{ $tag }}</a>
                            @if (!$loop->last),@endif
                        @endif
                    @endforeach
                </span>
            @endif
        </div>

        <div class="prose max-w-none">
            {!! $post['content'] !!}
        </div>
    </article>

    <div class="image-modal" id="imageModal">
        <button class="image-modal-close" onclick="closeModal()">&times;</button>
        <img src="" alt="" id="modalImage">
    </div>
@endsection

@section('scripts')
    <script>
        const coverImage = document.getElementById('coverImage');
        if (coverImage) {
            coverImage.addEventListener('mousemove', function(e) {
                const spotlight = document.getElementById('coverSpotlight');
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                spotlight.style.setProperty('--x', x + 'px');
                spotlight.style.setProperty('--y', y + 'px');
            });

            coverImage.addEventListener('mouseleave', function() {
                document.getElementById('coverSpotlight').style.opacity = '0';
            });

            coverImage.addEventListener('mouseenter', function() {
                document.getElementById('coverSpotlight').style.opacity = '1';
            });
        }

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
        });

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        window.addEventListener('scroll', closeModal);
    </script>
@endsection
