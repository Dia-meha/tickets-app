<?php
declare(strict_types=1);

/** Longueur en caractères (UTF-8), retours à la ligne normalisés comme côté navigateur. */
function text_length(string $s): int
{
    return mb_strlen(str_replace("\r\n", "\n", $s), 'UTF-8');
}

/** @return array{0: array, 1: array} [valeurs nettoyées, erreurs par champ] */
function validate_ticket(string $titre, string $description, string $categorie): array
{
    $titre = trim($titre);
    $description = trim(str_replace("\r\n", "\n", $description));
    $errors = [];

    $len = text_length($titre);
    if ($titre === '') {
        $errors['titre'] = 'Le titre est obligatoire.';
    } elseif ($len < 5 || $len > 100) {
        $errors['titre'] = 'Le titre doit contenir entre 5 et 100 caractères.';
    }

    $len = text_length($description);
    if ($description === '') {
        $errors['description'] = 'La description est obligatoire.';
    } elseif ($len < 20 || $len > 2000) {
        $errors['description'] = 'La description doit contenir entre 20 et 2 000 caractères.';
    }

    if (!array_key_exists($categorie, CATEGORIES)) {
        $errors['categorie'] = 'Choisissez une catégorie valide.';
    }

    return [['titre' => $titre, 'description' => $description, 'categorie' => $categorie], $errors];
}

/** @return array{0: string, 1: ?string} [contenu nettoyé, message d'erreur] */
function validate_reply(string $contenu): array
{
    $contenu = trim(str_replace("\r\n", "\n", $contenu));
    $len = text_length($contenu);
    if ($contenu === '') {
        return [$contenu, 'La réponse est obligatoire.'];
    }
    if ($len < 2 || $len > 2000) {
        return [$contenu, 'La réponse doit contenir entre 2 et 2 000 caractères.'];
    }
    return [$contenu, null];
}
