<?php
// separarconteudos.php

$options = getopt('', ['input:']);
if (!isset($options['input'])) {
    die("Uso: php separarconteudos.php --input nomedoarquivo.m3u\n");
}
$arquivo_m3u = $options['input'];

// Pastas de saída
$pastas = [
    'canais' => 'canais',
    'filmes' => 'filmes',
    'séries' => 'séries'
];

// Cria as pastas se não existirem
foreach ($pastas as $pasta) {
    if (!is_dir($pasta)) {
        mkdir($pasta, 0777, true);
    }
}

if (!file_exists($arquivo_m3u)) {
    die("Arquivo M3U não encontrado.\n");
}
$linhas = file($arquivo_m3u);

$conteudos = [
    'canais' => [],
    'filmes' => [],
    'séries' => []
];

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) return 'arquivo';
    return $text;
}

// Processa o arquivo M3U
for ($i = 0; $i < count($linhas); $i++) {
    $linha = trim($linhas[$i]);
    if (strpos($linha, '#EXTINF:') === 0) {
        // Extrai o group-title
        if (preg_match('/group-title="([^"]+)"/', $linha, $matches)) {
            $grupo = $matches[1];
            $partes = explode(' | ', $grupo);
            $categoria = strtolower($partes[0]);
            $subcategoria = isset($partes[1]) ? $partes[1] : 'geral';

            // Define a chave da categoria
            if ($categoria == 'canais') $cat_key = 'canais';
            elseif ($categoria == 'filmes') $cat_key = 'filmes';
            elseif ($categoria == 'series' || $categoria == 'séries') $cat_key = 'séries';
            else continue; // Ignora categorias não reconhecidas

            // Prepara o nome do arquivo
            $nome_arquivo = $cat_key . '-' . slugify($subcategoria) . '.m3u';

            // Adiciona o cabeçalho se for o primeiro item
            if (!isset($conteudos[$cat_key][$nome_arquivo])) {
                $conteudos[$cat_key][$nome_arquivo] = "#EXTM3U\n";
            }

            // Adiciona a linha #EXTINF e a próxima linha (URL)
            $conteudos[$cat_key][$nome_arquivo] .= $linha . "\n";
            if (isset($linhas[$i+1]) && trim($linhas[$i+1]) && strpos(trim($linhas[$i+1]), '#') !== 0) {
                $conteudos[$cat_key][$nome_arquivo] .= trim($linhas[$i+1]) . "\n";
            }
        }
    }
}

// Salva os arquivos separados
foreach ($conteudos as $cat_key => $arquivos) {
    foreach ($arquivos as $nome_arquivo => $conteudo) {
        $pasta = $pastas[$cat_key];
        file_put_contents($pasta . DIRECTORY_SEPARATOR . $nome_arquivo, $conteudo);
    }
}

echo "Separação concluída com sucesso!\n";
