<?php

function evento_normalizar_esporte(string $nome): string
{
    $nome = mb_strtolower(trim($nome), 'UTF-8');

    return strtr($nome, [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
        'é' => 'e', 'ê' => 'e',
        'í' => 'i',
        'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u',
        'ç' => 'c',
    ]);
}

function evento_vagas_por_time(string $esporte, ?PDO $conn = null): ?int
{
    if ($conn !== null) {
        $stmt = $conn->prepare('SELECT jogadores_por_time FROM Esporte WHERE LOWER(nome_esporte)=LOWER(?) LIMIT 1');
        $stmt->execute([$esporte]);
        $jogadores = $stmt->fetchColumn();
        if ($jogadores !== false && $jogadores !== null && (int)$jogadores > 0) {
            return (int)$jogadores;
        }
    }

    $regras = [
        'futebol' => 11,
        'futebol society' => 7,
        'futsal' => 5,
        'volei' => 6,
        'basquete' => 5,
        'handebol' => 7,
    ];

    return $regras[evento_normalizar_esporte($esporte)] ?? null;
}

function evento_quantidade_times(string $esporte, PDO $conn): int
{
    $stmt=$conn->prepare('SELECT quantidade_times FROM Esporte WHERE LOWER(nome_esporte)=LOWER(?) LIMIT 1');
    $stmt->execute([$esporte]);
    $n=(int)$stmt->fetchColumn();
    return in_array($n, [1,2], true) ? $n : 2;
}

function evento_iniciais(string $nome): string
{
    $partes = preg_split('/\s+/u', trim($nome)) ?: [];
    $iniciais = '';

    foreach (array_slice($partes, 0, 2) as $parte) {
        if ($parte !== '') {
            $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1, 'UTF-8'), 'UTF-8');
        }
    }

    return $iniciais !== '' ? $iniciais : '?';
}