<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Domain;

/** Forma comparable de un texto de búsqueda: sin mayúsculas ni tildes, con ñ distinta de n. No altera lo almacenado. */
final class SearchText
{
    /** Pares letra a letra; PostgreSQL usa los mismos en translate() para que ambos adaptadores coincidan. */
    public const ACCENTED = 'áàäâãåéèëêíìïîóòöôõúùüû';
    public const PLAIN = 'aaaaaaeeeeiiiiooooouuuu';

    public static function fold(string $text): string
    {
        // Forma descompuesta: "n" + U+0303 es la ñ; las demás marcas combinantes son tildes y se retiran.
        $composed = str_replace("n\u{0303}", 'ñ', mb_strtolower($text));
        $unmarked = preg_replace('/\p{Mn}/u', '', $composed) ?? $composed;

        return strtr($unmarked, array_combine(mb_str_split(self::ACCENTED), mb_str_split(self::PLAIN)));
    }
}
