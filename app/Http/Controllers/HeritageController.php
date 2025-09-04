<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class HeritageController extends Controller
{
    private string $baseDir;

    public function __construct()
    {
        $this->baseDir = base_path('content-pages');
    }

    // -------------------------------
    // ROTAS PRINCIPAIS
    // -------------------------------

    public function index(Request $request)
    {
        return $this->browse('');
    }

    public function browse($path = '')
    {
        $basePath = $this->baseDir . ($path ? '/' . $path : '');

        if (!is_dir($basePath)) {
            $post = $this->readPost($path);
            if ($post && !$post['draft']) {
                return view('heritages.show', ['post' => $post]);
            }
            abort(404);
        }

        $items = scandir($basePath);
        $folders = [];
        $pages = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $fullPath = $basePath . '/' . $item;

            if (is_dir($fullPath)) {
                $folders[] = $item;
            } elseif (preg_match('/\.(html|txt)$/', $item)) {
                if (!preg_match('/^(\d{4}-\d{2}-\d{2})-(.+)\.(html|txt)$/', $item, $matches)) {
                    continue;
                }

                $date = $matches[1];
                if (strtotime($date) > time()) continue;

                $content = file_get_contents($fullPath);

                $frontMatter = $this->parseFrontMatter($content);

                if (($frontMatter['draft'] ?? false) === true) continue;

                $title = $frontMatter['title'] ?? ucfirst(str_replace('-', ' ', $matches[2]));
                $summary = $frontMatter['summary'] ?? '';
                $tags = $this->normalizeTags($frontMatter['tags'] ?? []);

                $pages[] = [
                    'title' => (string) $title,
                    'date' => (string) $date,
                    'summary' => (string) $summary,
                    'tags' => $tags,
                    'path' => trim($path . '/' . pathinfo($item, PATHINFO_FILENAME), '/'),
                ];
            }
        }

        sort($folders);
        usort($pages, fn($a, $b) => strcmp($b['date'], $a['date']));

        return view('heritages.index', compact('folders', 'pages', 'path'));
    }

    public function byTag(Request $request, string $tag)
    {
        $allPosts = $this->collectAllPosts();

        $posts = array_filter($allPosts, fn($post) =>
            isset($post['tags']) && is_array($post['tags']) && in_array($tag, $post['tags'])
        );

        return view('heritages.tag', [
            'tag'   => $tag,
            'posts' => $posts,
        ]);
    }

    public function search(Request $request)
    {
        $query = (string) $request->query('q', '');
        $keywords = array_filter(preg_split('/\s+/', $query));

        $allPosts = $this->collectAllPosts();
        $results = [];

        foreach ($allPosts as $post) {
            if (empty($keywords)) {
                $results[] = $post;
                continue;
            }

            foreach ($keywords as $kw) {
                $kw = strtolower(trim($kw));
                $title = strtolower($post['title']);
                $content = strtolower(strip_tags($post['content']));

                if (strpos($title, $kw) !== false || strpos($content, $kw) !== false) {
                    $results[] = $post;
                    break;
                }
            }
        }

        return view('heritages.search', [
            'results' => $results,
            'query'   => $query,
        ]);
    }

    public function tags()
    {
        $allPosts = $this->collectAllPosts();
        $allTags = [];

        foreach ($allPosts as $post) {
            if (!empty($post['tags']) && is_array($post['tags'])) {
                $allTags = array_merge($allTags, $post['tags']);
            }
        }

        $allTags = array_unique(array_filter($allTags, fn($t) => trim($t) !== ''));
        sort($allTags);

        return view('heritages.tags', ['allTags' => $allTags]);
    }

    // -------------------------------
    // MÉTODOS AUXILIARES
    // -------------------------------

    private function parseFrontMatter(string $raw): array
    {
        $meta = [];
        if (preg_match('/^---(.*?)---/s', $raw, $m)) {
            foreach (preg_split("/\r?\n/", trim($m[1])) as $line) {
                $line = trim($line);
                if ($line === '' || !str_contains($line, ':')) continue;

                [$k, $v] = array_map('trim', explode(':', $line, 2));
                $k = strtolower($k);

                if ($k === 'tags') {
                    $tags = array_map('trim', explode(',', $v));
                    $meta['tags'] = array_values(array_filter($tags, fn($t) => $t !== ''));
                } elseif ($k === 'draft') {
                    $meta['draft'] = strtolower($v) === 'true';
                } else {
                    $meta[$k] = $v;
                }
            }
        }
        return $meta;
    }

    private function readPost(string $relativePathToFileNoExt): ?array
    {
        $fullNoExt = $this->baseDir . DIRECTORY_SEPARATOR . $relativePathToFileNoExt;

        $file = null;
        if (File::exists($fullNoExt . '.html')) $file = $fullNoExt . '.html';
        elseif (File::exists($fullNoExt . '.txt')) $file = $fullNoExt . '.txt';
        if (!$file) return null;

        $raw   = File::get($file);
        $meta  = $this->parseFrontMatter($raw);
        $body  = preg_replace('/^---.*?---\s*/s', '', $raw);
        $ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $slug  = pathinfo($file, PATHINFO_FILENAME);

        $title = $meta['title']
            ?? (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $body, $m) ? strip_tags($m[1]) : Str::of(preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $slug))->replace('-', ' ')->title());

        $content = ($ext === 'txt') ? $this->renderTxtToHtml($body) : $this->fixImagePaths($body);

        $tags = $this->normalizeTags($meta['tags'] ?? []);

        $coverImage = isset($meta['cover'])
            ? $this->normalizeCoverPath($meta['cover'])
            : $this->resolveCover($slug);

        return [
            'title'   => (string) $title,
            'date'    => substr(basename($file), 0, 10),
            'tags'    => $tags,
            'draft'   => (bool) ($meta['draft'] ?? false),
            'summary' => isset($meta['summary']) ? (string) $meta['summary'] : '',
            'cover'   => $coverImage,
            'content' => $content,
        ];
    }

    private function resolveCover(string $slug): ?string
    {
        $publicDir = public_path('images');
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
            $filename = $slug . '.' . $ext;
            if (File::exists($publicDir . '/' . $filename)) {
                return '/images/' . $filename;
            }
        }
        return '/images/placeholder.jpg';
    }

    private function normalizeCoverPath(string $cover): string
    {
        $cover = ltrim($cover, '/');
        if (File::exists(public_path('images/' . $cover))) {
            return '/images/' . $cover;
        }
        return '/images/placeholder.jpg';
    }

    private function fixImagePaths(string $html): string
    {
        return preg_replace_callback(
            '/<img\s+[^>]*src=["\']([^"\']+)["\']/i',
            function ($m) {
                $src = $m[1];
                if (preg_match('#^(https?:)?//#', $src)) return $m[0];
                return str_replace($src, '/images/' . ltrim($src, '/'), $m[0]);
            },
            $html
        );
    }

    private function renderTxtToHtml(string $raw): string
    {
        $html = '';
        foreach (preg_split("/\r?\n/", trim($raw)) as $line) {
            $line = trim($line);
            if ($line === '') continue;

            if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $line)) {
                $html .= '<img src="/images/' . ltrim($line, '/') . '" alt="" class="w-full cursor-pointer">';
            } else {
                $html .= '<p>' . e($line) . '</p>';
            }
        }
        return $html;
    }

    private function collectAllPosts(): array
    {
        $posts = [];
        $dir   = $this->baseDir;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($it as $fileInfo) {
            if (!$fileInfo->isFile()) continue;

            $ext = strtolower($fileInfo->getExtension());
            if (!in_array($ext, ['html', 'txt'])) continue;

            $filename = $fileInfo->getFilename();
            if (!preg_match('/^\d{4}-\d{2}-\d{2}-/', $filename)) continue;

            $dateStr = substr($filename, 0, 10);
            if (Carbon::parse($dateStr)->isFuture()) continue;

            $slugNoExt = pathinfo($filename, PATHINFO_FILENAME);
            $relative  = trim(str_replace($dir, '', $fileInfo->getPath()), DIRECTORY_SEPARATOR);
            $fullSlug  = ltrim(($relative ? $relative . '/' : '') . $slugNoExt, '/');

            $post = $this->readPost($fullSlug);
            if ($post && !$post['draft']) {
                $posts[] = $post;
            }
        }

        usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
        return $posts;
    }

    private function normalizeTags($tags): array
    {
        if (is_array($tags)) return $tags;
        if (is_string($tags)) return array_map('trim', explode(',', $tags));
        return [];
    }
}
