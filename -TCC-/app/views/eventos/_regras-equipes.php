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

function evento_vagas_por_time(string $esporte): ?int
{
    $regras = [
        'futebol' => 11,
        'futsal' => 5,
        'volei' => 6,
        'basquete' => 5,
        'handebol' => 7,
    ];

    return $regras[evento_normalizar_esporte($esporte)] ?? null;
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
