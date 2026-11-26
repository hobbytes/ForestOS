<?php
/* Forest OS — Settings localization helper.
 *
 * Every Settings page keeps its strings in lang/<page>.lang as
 *   ru_<key> = ...
 *   en_<key> = ...
 * Use it like this:
 *   list($t, $lc) = fo_settings_lang('security');
 *   echo $t('save_button');
 *
 * $t() resolves "<locale>_<key>". When the key is missing in the active
 * locale it returns $default (or the key itself), never a Russian string,
 * so a missing translation is always visible during testing.
 */
if (!function_exists('fo_settings_lang')) {
    function fo_settings_lang(string $page): array
    {
        $locale = 'ru';
        if (isset($_SESSION['locale']) && $_SESSION['locale'] === 'en') {
            $locale = 'en';
        }

        $dict = @parse_ini_file(__DIR__ . '/lang/' . $page . '.lang');
        if (!is_array($dict)) {
            $dict = array();
        }

        $t = function (string $key, string $default = '') use ($dict, $locale): string {
            $full = $locale . '_' . $key;
            if (isset($dict[$full]) && trim((string)$dict[$full]) !== '') {
                return (string)$dict[$full];
            }
            if ($default !== '') {
                return $default;
            }
            return $key;
        };

        return array($t, $locale);
    }
}

if (!function_exists('fo_settings_js')) {
    /* Build the {} object handed to the page JavaScript. */
    function fo_settings_js($t, array $keys): string
    {
        $out = array();
        foreach ($keys as $key) {
            $out[$key] = $t($key);
        }
        return json_encode($out, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
