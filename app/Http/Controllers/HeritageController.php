<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File; // Para interagir com o sistema de arquivos.
use Illuminate\Support\Str; // Para operações de string, como manipulação de slug.
use Carbon\Carbon; // Para manipulação de datas e horários.

/**
 * Controller responsável por gerenciar o conteúdo "Heritage", que parece ser um sistema de blog ou CMS simples baseado em arquivos.
 */
class HeritageController extends Controller
{
    // Propriedade privada para armazenar o diretório base dos arquivos de conteúdo.
    private string $baseDir;

    /**
     * Construtor da classe.
     * Define o diretório base para os arquivos de conteúdo como 'content-pages' dentro do caminho base da aplicação.
     */
    public function __construct()
    {
        $this->baseDir = base_path('content-pages');
    }

    // -------------------------------
    // ROTAS PRINCIPAIS
    // -------------------------------

    /**
     * Método para a página inicial (index).
     * Simplesmente chama o método 'browse' sem um caminho específico.
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return $this->browse('');
    }

    /**
     * Navega pelos diretórios e arquivos de conteúdo.
     * Se o caminho for um diretório, lista as pastas e páginas.
     * Se o caminho for um arquivo, tenta exibir o post.
     * @param string $path O caminho relativo dentro do diretório de conteúdo.
     * @return \Illuminate\View\View
     */
    public function browse($path = '')
    {
        $basePath = $this->baseDir . ($path ? '/' . $path : '');

        // Verifica se o caminho é um diretório.
        if (!is_dir($basePath)) {
            // Se não for, tenta ler como um post individual.
            $post = $this->readPost($path);
            // Exibe o post se ele existir e não for um rascunho.
            if ($post && !$post['draft']) {
                return view('heritages.show', ['post' => $post]);
            }
            // Se não encontrar ou for rascunho, retorna um erro 404.
            abort(404);
        }

        // Se for um diretório, lê os itens.
        $items = scandir($basePath);
        $folders = [];
        $pages = [];

        // Itera sobre cada item no diretório.
        foreach ($items as $item) {
            // Ignora os diretórios de navegação padrão.
            if ($item === '.' || $item === '..') continue;

            $fullPath = $basePath . '/' . $item;

            // Se o item for um diretório, adiciona à lista de pastas.
            if (is_dir($fullPath)) {
                $folders[] = $item;
            } elseif (preg_match('/\.(html|txt)$/', $item)) {
                // Se for um arquivo HTML ou TXT, verifica o formato do nome do arquivo.
                if (!preg_match('/^(\d{4}-\d{2}-\d{2})-(.+)\.(html|txt)$/', $item, $matches)) {
                    continue; // Ignora arquivos com nome fora do padrão.
                }

                $date = $matches[1];
                // Ignora arquivos com data futura.
                if (strtotime($date) > time()) continue;

                $content = file_get_contents($fullPath);
                $frontMatter = $this->parseFrontMatter($content);

                // Ignora posts marcados como rascunho.
                if (($frontMatter['draft'] ?? false) === true) continue;

                $title = $frontMatter['title'] ?? ucfirst(str_replace('-', ' ', $matches[2]));
                $summary = $frontMatter['summary'] ?? '';
                $tags = $this->normalizeTags($frontMatter['tags'] ?? []);

                // Adiciona a página processada à lista.
                $pages[] = [
                    'title' => (string) $title,
                    'date' => (string) $date,
                    'summary' => (string) $summary,
                    'tags' => $tags,
                    'path' => trim($path . '/' . pathinfo($item, PATHINFO_FILENAME), '/'),
                ];
            }
        }

        // Ordena as pastas em ordem alfabética e as páginas por data.
        sort($folders);
        usort($pages, fn($a, $b) => strcmp($b['date'], $a['date']));

        // Retorna a view com os dados para exibição.
        return view('heritages.index', compact('folders', 'pages', 'path'));
    }

    /**
     * Exibe todos os posts que possuem uma tag específica.
     * @param Request $request
     * @param string $tag A tag a ser filtrada.
     * @return \Illuminate\View\View
     */
    public function byTag(Request $request, string $tag)
    {
        // Coleta todos os posts disponíveis.
        $allPosts = $this->collectAllPosts();

        // Filtra os posts para encontrar aqueles que contêm a tag.
        $posts = array_filter(
            $allPosts,
            fn($post) =>
            isset($post['tags']) && is_array($post['tags']) && in_array($tag, $post['tags'])
        );

        // Retorna a view com a tag e os posts filtrados.
        return view('heritages.tag', [
            'tag'   => $tag,
            'posts' => $posts,
        ]);
    }

    /**
     * Realiza uma pesquisa por posts com base em uma consulta de string.
     * A pesquisa é feita no título e no conteúdo dos posts.
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
        $query = (string) $request->query('q', '');
        $keywords = array_filter(preg_split('/\s+/', $query)); // Divide a consulta em palavras-chave.

        $allPosts = $this->collectAllPosts();
        $results = [];

        // Itera sobre todos os posts para encontrar correspondências.
        foreach ($allPosts as $post) {
            // Se não houver palavras-chave, todos os posts são incluídos.
            if (empty($keywords)) {
                $results[] = $post;
                continue;
            }

            // Verifica se alguma palavra-chave está no título ou conteúdo do post.
            foreach ($keywords as $kw) {
                $kw = strtolower(trim($kw));
                $title = strtolower($post['title']);
                $content = strtolower(strip_tags($post['content']));

                if (strpos($title, $kw) !== false || strpos($content, $kw) !== false) {
                    $results[] = $post;
                    break; // Adiciona o post aos resultados e passa para o próximo post.
                }
            }
        }

        // Retorna a view com os resultados da pesquisa.
        return view('heritages.search', [
            'results' => $results,
            'query'   => $query,
        ]);
    }

    /**
     * Exibe uma lista de todas as tags usadas nos posts.
     * @return \Illuminate\View\View
     */
    public function tags()
    {
        $allPosts = $this->collectAllPosts();
        $allTags = [];

        // Coleta todas as tags de todos os posts.
        foreach ($allPosts as $post) {
            if (!empty($post['tags']) && is_array($post['tags'])) {
                $allTags = array_merge($allTags, $post['tags']);
            }
        }

        // Remove tags duplicadas e vazias e ordena em ordem alfabética.
        $allTags = array_unique(array_filter($allTags, fn($t) => trim($t) !== ''));
        sort($allTags);

        // Retorna a view com a lista de tags.
        return view('heritages.tags', ['allTags' => $allTags]);
    }

    // -------------------------------
    // MÉTODOS AUXILIARES
    // -------------------------------

    /**
     * Analisa o 'front matter' (metadados no início do arquivo) de um arquivo de conteúdo.
     * @param string $raw O conteúdo bruto do arquivo.
     * @return array Um array com os metadados encontrados.
     */
    private function parseFrontMatter(string $raw): array
    {
        $meta = [];
        // Usa uma expressão regular para encontrar o bloco de metadados entre '---'.
        if (preg_match('/^---(.*?)---/s', $raw, $m)) {
            // Divide o bloco em linhas e processa cada uma.
            foreach (preg_split("/\r?\n/", trim($m[1])) as $line) {
                $line = trim($line);
                if ($line === '' || !str_contains($line, ':')) continue;

                // Divide a linha em chave e valor.
                [$k, $v] = array_map('trim', explode(':', $line, 2));
                $k = strtolower($k);

                // Trata casos especiais como 'tags' e 'draft'.
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

    /**
     * Lê e processa um único post de conteúdo a partir de um caminho relativo.
     * @param string $relativePathToFileNoExt O caminho do arquivo sem extensão.
     * @return array|null Um array contendo os dados do post ou null se não for encontrado.
     */
    private function readPost(string $relativePathToFileNoExt): ?array
    {
        $fullNoExt = $this->baseDir . DIRECTORY_SEPARATOR . $relativePathToFileNoExt;

        // Verifica a existência do arquivo com extensão .html ou .txt.
        $file = null;
        if (File::exists($fullNoExt . '.html')) $file = $fullNoExt . '.html';
        elseif (File::exists($fullNoExt . '.txt')) $file = $fullNoExt . '.txt';
        if (!$file) return null;

        $raw   = File::get($file);
        $meta  = $this->parseFrontMatter($raw); // Extrai os metadados.
        $body  = preg_replace('/^---.*?---\s*/s', '', $raw); // Remove o front matter para obter o conteúdo.
        $ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $slug  = pathinfo($file, PATHINFO_FILENAME);

        // Define o título do post, priorizando o metadado, depois a tag H1, e por último o nome do arquivo.
        $title = $meta['title']
            ?? (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $body, $m) ? strip_tags($m[1]) : Str::of(preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $slug))->replace('-', ' ')->title());

        // Renderiza o conteúdo de acordo com a extensão do arquivo.
        $content = ($ext === 'txt') ? $this->renderTxtToHtml($body) : $this->fixImagePaths($body);

        $tags = $this->normalizeTags($meta['tags'] ?? []);

        // Resolve o caminho da imagem de capa.
        $coverImage = isset($meta['cover'])
            ? $this->normalizeCoverPath($meta['cover'])
            : $this->resolveCover($slug);

        // Retorna um array com todos os dados do post.
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

    /**
     * Tenta encontrar uma imagem de capa para um post com base no seu slug.
     * @param string $slug O slug do post.
     * @return string O caminho da imagem de capa ou um placeholder.
     */
    private function resolveCover(string $slug): ?string
    {
        $publicDir = public_path('images');
        // Verifica se existe uma imagem com o nome do slug e uma das extensões listadas.
        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
            $filename = $slug . '.' . $ext;
            if (File::exists($publicDir . '/' . $filename)) {
                return '/images/' . $filename;
            }
        }
        return '/images/placeholder.jpg'; // Retorna uma imagem padrão se nenhuma for encontrada.
    }

    /**
     * Normaliza o caminho de uma imagem de capa especificada no front matter.
     * @param string $cover O caminho da imagem especificado.
     * @return string O caminho normalizado ou um placeholder.
     */
    private function normalizeCoverPath(string $cover): string
    {
        $cover = ltrim($cover, '/'); // Remove a barra inicial, se houver.
        if (File::exists(public_path('images/' . $cover))) {
            return '/images/' . $cover;
        }
        return '/images/placeholder.jpg';
    }

    /**
     * Corrige os caminhos das imagens em um conteúdo HTML para que sejam absolutos.
     * @param string $html O conteúdo HTML.
     * @return string O HTML com os caminhos das imagens corrigidos.
     */
    private function fixImagePaths(string $html): string
    {
        return preg_replace_callback(
            '/<img\s+[^>]*src=["\']([^"\']+)["\']/i', // Encontra todas as tags <img> com src.
            function ($m) {
                $src = $m[1];
                // Ignora caminhos que já são URLs completas.
                if (preg_match('#^(https?:)?//#', $src)) return $m[0];
                // Adiciona o caminho base de imagens se o caminho for relativo.
                return str_replace($src, '/images/' . ltrim($src, '/'), $m[0]);
            },
            $html
        );
    }

    /**
     * Converte o conteúdo de um arquivo de texto (.txt) para HTML.
     * Linhas que terminam com extensões de imagem são convertidas em tags <img>.
     * As outras linhas são envolvidas em tags <p>.
     * @param string $raw O conteúdo de texto bruto.
     * @return string O conteúdo HTML gerado.
     */
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

    /**
     * Coleta todos os posts de forma recursiva a partir do diretório base.
     * @return array Um array de posts, ordenado por data.
     */
    private function collectAllPosts(): array
    {
        $posts = [];
        $dir   = $this->baseDir;

        // Cria um iterador recursivo para percorrer todos os arquivos nos subdiretórios.
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($it as $fileInfo) {
            if (!$fileInfo->isFile()) continue;

            $ext = strtolower($fileInfo->getExtension());
            // Processa apenas arquivos com extensão .html ou .txt.
            if (!in_array($ext, ['html', 'txt'])) continue;

            $filename = $fileInfo->getFilename();
            // Verifica se o nome do arquivo segue o padrão de data.
            if (!preg_match('/^\d{4}-\d{2}-\d{2}-/', $filename)) continue;

            $dateStr = substr($filename, 0, 10);
            // Ignora posts com datas futuras.
            if (Carbon::parse($dateStr)->isFuture()) continue;

            // Constrói o slug relativo para ler o post.
            $slugNoExt = pathinfo($filename, PATHINFO_FILENAME);
            $relative  = trim(str_replace($dir, '', $fileInfo->getPath()), DIRECTORY_SEPARATOR);
            $fullSlug  = ltrim(($relative ? $relative . '/' : '') . $slugNoExt, '/');

            // Lê o post e o adiciona à lista se não for um rascunho.
            $post = $this->readPost($fullSlug);
            if ($post && !$post['draft']) {
                $post['path'] = $fullSlug;
                $posts[] = $post;
            }
        }

        // Ordena os posts pela data.
        usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
        return $posts;
    }

    /**
     * Normaliza a entrada de tags, convertendo strings em arrays.
     * @param mixed $tags A entrada de tags, que pode ser uma string ou um array.
     * @return array Um array limpo de tags.
     */
    private function normalizeTags($tags): array
    {
        if (is_array($tags)) return $tags;
        if (is_string($tags)) return array_map('trim', explode(',', $tags));
        return [];
    }
}