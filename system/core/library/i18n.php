<?php
/**
 * Forest OS — локализация ядра.
 *
 * Строки системы живут в system/core/os.lang в виде пар <locale>_<key>.
 * fo_t('key', 'запасной текст') возвращает строку текущей локали; если ключа
 * нет — запасной текст, чтобы интерфейс никогда не показывал «сырой» ключ.
 */
function fo_locale(): string
{
    $locale = (string)($_SESSION['locale'] ?? 'ru');
    return in_array($locale, array('ru', 'en'), true) ? $locale : 'ru';
}

function fo_lang(): array
{
    static $cache = array();
    $locale = fo_locale();
    if (isset($cache[$locale])) {
        return $cache[$locale];
    }
    $doc = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') : dirname(__DIR__, 3);
    $ini = @parse_ini_file($doc . '/system/core/os.lang', true);
    $dict = array();
    if (is_array($ini)) {
        foreach ($ini as $section) {
            if (!is_array($section)) {
                continue;
            }
            foreach ($section as $k => $v) {
                if (strpos($k, $locale . '_') === 0) {
                    $dict[substr($k, strlen($locale) + 1)] = (string)$v;
                }
            }
        }
    }
    $cache[$locale] = $dict;
    return $dict;
}

function fo_t(string $key, string $default = ''): string
{
    $dict = fo_lang();
    if (isset($dict[$key]) && $dict[$key] !== '') {
        return $dict[$key];
    }
    return $default !== '' ? $default : $key;
}
