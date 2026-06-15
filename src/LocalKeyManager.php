<?php

namespace Rackbeat\TolgeeSync;

class LocalKeyManager {

    public function storeKey($locale, $file, $key, $value)
    {
        $path = base_path("resources/lang/{$locale}/" . $file . '.php');

        if (file_exists($path)) {
            $translations = require $path;

            $nestedKeys = explode('.', $key);
            $lastKey = array_pop($nestedKeys);

            $nestedArray = &$translations;
            foreach ($nestedKeys as $nestedKey) {
                if (!isset($nestedArray[$nestedKey]) || !is_array($nestedArray[$nestedKey])) {
                    $nestedArray[$nestedKey] = [];
                }
                $nestedArray = &$nestedArray[$nestedKey];
            }

            $nestedArray[$lastKey] = $value;
            $output = "<?php\n\nreturn [\n" . $this->formatArray($translations, 1) . "];\n";
            file_put_contents($path, $output);
        } else {
            echo "File: {$file} does not exist. Creating..." . PHP_EOL;
            file_put_contents($path, "<?php\n\nreturn [\n];\n");
            $this->storeKey($locale, $file, $key, $value);
        }
    }

    public function formatArray($array, $level = 0)
    {
        $output = '';

        foreach ($array as $key => $value) {
            $output .= str_repeat("\t", $level) . "'$key' => ";

            if (is_array($value)) {
                $output .= "[\n" . $this->formatArray($value, $level + 1) . str_repeat("\t", $level) . "],\n";
            } else {
                // Check if translation contains '\n' or '\t' and decide whether to use double or single quotes
                if (strpos($value, "\n") !== false || strpos($value, "\t") !== false) {
                    $output .= '"' . str_replace("\"", '\\"', str_replace("\t", '\\t', str_replace("\n", '\\n', $value))) . "\",\n";
                } else {
                    $value = str_replace("'", "\\'", $value);
                    $output .= "'" . $value . "',\n";
                }
            }
        }

        return $output;
    }
}

