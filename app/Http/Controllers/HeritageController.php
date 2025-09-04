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
        return $this->browse($request, null);
    }

    public function browse($path = '')
    {
        $basePath = $this->baseDir . ($path ? '/' . $path : '');

        // Se não for um diretório, tenta encontrar um arquivo/post
        if (!is_dir($basePath)) {
            $post = $this->readPost($path);
            if ($post && !$post['draft']) {
                return view('heritages.show', ['post' => $post]);
            }
            abort(404);
        }

        $items = scandir($basePath);
        $folders = [];
        $pages = []; // CORREÇÃO: Inicializar o array $pages vazio aqui

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $fullPath = $basePath . '/' . $item;

            // Se for pasta
            if (is_dir($fullPath)) {
                $folders[] = $item;
            }

            // Se for arquivo .html ou .txt
            elseif (preg_match('/\.(html|txt)$/', $item)) {
                // Checar padrão de data AAAA-MM-DD
                if (!preg_match('/^(\d{4}-\d{2}-\d{2})-(.+)\.(html|txt)$/', $item, $matches)) {
                    continue; // sem data válida → ignora
                }

                $date = $matches[1];
                if (strtotime($date) > time()) {
                    continue; // futuro → ignora
                }

                $content = file_get_contents($fullPath);

                // Parser simples do front-matter
                $frontMatter = [];
                if (preg_match('/^---(.*?)---/s', $content, $fm)) {
                    $lines = preg_split('/\r\n|\r|\n/', trim($fm[1]));
                    foreach ($lines as $line) {
                        if (strpos($line, ':') !== false) {
                            [$key, $value] = array_map('trim', explode(':', $line, 2));
                            // Converte valores específicos
                            if ($value === 'true') $value = true;
                            elseif ($value === 'false') $value = false;
                            elseif (str_contains($value, ',')) {
                                $value = array_map('trim', explode(',', $value));
                            }
                            $frontMatter[$key] = $value;
                        }
                    }
                }

                // Se for rascunho → ignora
                if (($frontMatter['draft'] ?? false) === true) {
                    continue;
                }

                // Definir título
                $title = $frontMatter['title'] ?? $matches[2];
                $title = ucfirst(str_replace('-', ' ', $title));

                // Definir resumo
                $summary = $frontMatter['summary'] ?? '';

                // CORREÇÃO: Definir tags de forma segura
                $tags = [];
                if (isset($frontMatter['tags'])) {
                    if (is_array($frontMatter['tags'])) {
                        $tags = $frontMatter['tags'];
                    } elseif (is_string($frontMatter['tags'])) {
                        $tags = array_map('trim', explode(',', $frontMatter['tags']));
                    }
                }

                // CORREÇÃO: Garantir que todos os valores sejam strings
                $pages[] = [
                    'title' => is_array($title) ? implode(' ', $title) : (string) $title,
                    'date' => (string) $date,
                    'summary' => is_array($summary) ? implode(' ', $summary) : (string) $summary,
                    'tags' => $tags,
                    'path' => trim($path . '/' . pathinfo($item, PATHINFO_FILENAME), '/'),
                ];
            }
        }

        // Ordenar pastas A→Z
        sort($folders);

        // Ordenar páginas por data DESC
        usort($pages, fn($a, $b) => strcmp($b['date'], $a['date']));

        return view('heritages.index', compact('folders', 'pages', 'path'));
    }

    public function byTag(Request $request, string $tag)
    {
        $allPosts = $this->collectAllPosts();

        // CORREÇÃO: Filtrar posts que têm a tag e garantir que tags seja array
        $posts = array_filter($allPosts, function ($post) use ($tag) {
            return isset($post['tags']) && is_array($post['tags']) && in_array($tag, $post['tags']);
        });

        return view('heritages.tag', [
            'tag'   => $tag,
            'posts' => $posts,
        ]);
    }

    public function search(Request $request)
    {
        $query = (string) $request->query('q', '');
        $keywords = array_filter(preg_split('/\s*\/\s*|\s+/', $query));

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
            // Verificar se tags existe e é um array não vazio
            if (!empty($post['tags']) && is_array($post['tags'])) {
                $allTags = array_merge($allTags, $post['tags']);
            }
        }

        // Remover tags vazias e duplicadas, depois ordenar
        $allTags = array_filter($allTags, function ($tag) {
            return !empty(trim($tag));
        });

        $allTags = array_unique($allTags);
        sort($allTags);

        return view('heritages.tags', ['allTags' => $allTags]);
    }
    // -------------------------------
    // MÉTODOS AUXILIARES
    // -------------------------------

    private function scanFolder(string $relativePath = ''): array
    {
        $dir = $this->baseDir . ($relativePath ? DIRECTORY_SEPARATOR . $relativePath : '');

        if (!File::exists($dir)) abort(404, "Folder not found: $relativePath");

        $folders = [];
        $files   = [];

        // pastas
        foreach (File::directories($dir) as $subDir) {
            $folders[] = [
                'type' => 'folder',
                'name' => basename($subDir),
                'url'  => url("01_module_c/heritages/" . trim($relativePath . '/' . basename($subDir), '/')),
            ];
        }

        // arquivos
        foreach (File::files($dir) as $file) {
            $filename = $file->getFilename();
            if (!preg_match('/\.(html|txt)$/', $filename)) continue;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}-/', $filename)) continue;

            $dateStr = substr($filename, 0, 10);
            $date = Carbon::parse($dateStr);
            if ($date->isFuture()) continue;

            $content = File::get($file->getPathname());
            $meta = $this->parseFrontMatter($content);
            if (!empty($meta['draft']) && $meta['draft'] === true) continue;

            $slug = pathinfo($filename, PATHINFO_FILENAME);

            $files[] = [
                'type'    => 'file',
                'slug'    => $slug,
                'title'   => $meta['title'] ?? $slug,
                'summary' => $meta['summary'] ?? '',
                'date'    => $dateStr,
                'url'     => url("01_module_c/heritages/" . trim($relativePath . '/' . $slug, '/')),
            ];
        }

        usort($folders, fn($a, $b) => strcmp($a['name'], $b['name']));
        usort($files, fn($a, $b) => strcmp($b['slug'], $a['slug']));

        return array_merge($folders, $files);
    }

    private function isPostSlug(string $segment): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}-.+$/', $segment);
    }

    private function readPost(string $relativePathToFileNoExt): ?array
    {
        $fullPathNoExt = $this->baseDir . DIRECTORY_SEPARATOR . $relativePathToFileNoExt;

        $file = null;
        if (File::exists($fullPathNoExt . '.html')) $file = $fullPathNoExt . '.html';
        elseif (File::exists($fullPathNoExt . '.txt')) $file = $fullPathNoExt . '.txt';

        if (!$file) return null;

        $raw = File::get($file);
        $meta = $this->parseFrontMatter($raw);
        $content = preg_replace('/^---.*?---\s*/s', '', $raw);

        if (Str::endsWith($file, '.html')) {
            $content = $this->fixImagePaths($content);
        } else {
            $content = $this->renderTxtToHtml($content);
        }

        $title = $meta['title'] ?? null;
        if (!$title && preg_match('/<h1[^>]*>(.*?)<\/h1>/', $content, $m)) $title = strip_tags($m[1]);
        if (!$title) $title = Str::of(basename($file, '.' . pathinfo($file, PATHINFO_EXTENSION)))->after('2024-09-01-')->replace('-', ' ')->title();

        // CORREÇÃO: Definir tags de forma segura
        $tags = [];
        if (isset($meta['tags'])) {
            if (is_array($meta['tags'])) {
                $tags = $meta['tags'];
            } elseif (is_string($meta['tags'])) {
                $tags = array_map('trim', explode(',', $meta['tags']));
            }
        }

        return [
            'title'   => is_array($title) ? implode(' ', $title) : $title,
            'date'    => substr(basename($file), 0, 10),
            'tags'    => $tags,
            'draft'   => $meta['draft'] ?? false,
            'summary' => isset($meta['summary']) ? (is_array($meta['summary']) ? implode(' ', $meta['summary']) : $meta['summary']) : '',
            'cover'   => isset($meta['cover']) ? (is_array($meta['cover']) ? implode(' ', $meta['cover']) : $meta['cover']) : null,
            'content' => $content,
        ];
    }

    private function parseFrontMatter(string $raw): array
    {
        $meta = [];
        if (preg_match('/^---(.*?)---/s', $raw, $matches)) {
            $lines = preg_split("/\r?\n/", trim($matches[1]));
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (strpos($line, ':') !== false) {
                    [$key, $value] = array_map('trim', explode(':', $line, 2));
                    $key = strtolower($key);

                    // Pular linhas sem valor
                    if ($value === '') continue;

                    if ($key === 'tags') {
                        // Processar tags - remover espaços extras e filtrar vazias
                        $tags = array_map('trim', explode(',', $value));
                        $meta['tags'] = array_filter($tags, function ($tag) {
                            return !empty($tag);
                        });
                    } elseif ($key === 'draft') {
                        $meta['draft'] = strtolower($value) === 'true';
                    } else {
                        $meta[$key] = $value;
                    }
                }
            }
        }
        return $meta;
    }

    private function renderTxtToHtml(string $raw): string
    {
        $lines = preg_split("/\r?\n/", trim($raw));
        $html = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Garantir que a linha seja string
            $line = is_array($line) ? implode(' ', $line) : $line;

            if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $line)) {
                $html .= '<img src="/content-pages/images/' . $line . '" alt="" class="w-full cursor-pointer">';
            } else {
                $html .= '<p>' . e($line) . '</p>';
            }
        }
        return $html;
    }

    private function fixImagePaths(string $html): string
    {
        return preg_replace_callback(
            '/<img\s+[^>]*src=["\']([^"\']+)["\']/i',
            function ($matches) {
                $src = $matches[1];
                // Garantir que src seja string
                $src = is_array($src) ? implode(' ', $src) : $src;

                // Se já for um caminho absoluto ou URL, não modificar
                if (strpos($src, 'http') === 0 || strpos($src, '//') === 0) {
                    return $matches[0];
                }
                // Adicionar o caminho base para imagens e classe
                $replacement = str_replace('src="' . $src . '"', 'src="/content-pages/images/' . $src . '" class="w-full cursor-pointer"', $matches[0]);
                return $replacement;
            },
            $html
        );
    }

    private function collectAllPosts(): array
    {
        $dir = $this->baseDir;
        $posts = [];

        // Usar RecursiveDirectoryIterator para buscar arquivos em todas as subpastas
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/\.(html|txt)$/', $file->getFilename())) {
                $slug = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                if (!$this->isPostSlug($slug)) continue;

                // Obter o caminho relativo
                $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativePath = str_replace('.' . $file->getExtension(), '', $relativePath);

                $post = $this->readPost($relativePath);

                if ($post && !$post['draft']) {
                    // Garantir que tags seja sempre array e não esteja vazio
                    if (!isset($post['tags']) || !is_array($post['tags'])) {
                        $post['tags'] = [];
                    }

                    // Filtrar tags vazias
                    $post['tags'] = array_filter($post['tags'], function ($tag) {
                        return !empty(trim($tag));
                    });

                    $posts[] = $post;
                }
            }
        }

        usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));

        return $posts;
    }
}
