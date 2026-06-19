<?php

namespace Rackbeat\TolgeeSync;

class LocalKeyManager {

    public function storeKey($locale, $file, $key, $value)
    {
        if (empty($value)) {
            return;
        }

        // Sanitize inputs to prevent path traversal
        $locale = basename(str_replace(['..', '/'], '', $locale));
        $file = str_replace(['..', '/', '\\'], '', $file);

        if (empty($locale) || empty($file)) {
            return;
        }

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
            $directory = dirname($path);

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
                echo "Directory: resources/lang/{$locale}/ does not exist. Creating..." . PHP_EOL;
            }

            echo "File: {$file} does not exist. Creating..." . PHP_EOL;
            $written = file_put_contents($path, "<?php\n\nreturn [\n];\n");
            if ($written === false) {
                echo "Error: Failed to create file {$path}" . PHP_EOL;
                return;
           }
           $this->storeKey($locale, $file, $key, $value);
        }
    }

    public function formatArray($array, $level = 0)
    {
        $output = '';

        foreach ($array as $key => $value) {
            $output .= str_repeat("\t", $level) . var_export((string) $key, true) . " => ";

            if (is_array($value)) {
                $output .= "[\n" . $this->formatArray($value, $level + 1) . str_repeat("\t", $level) . "],\n";
            } else {
                // Check if translation contains '\n' or '\t' and decide whether to use double or single quotes
                if (strpos($value, "\n") !== false || strpos($value, "\t") !== false) {
                    $escaped = str_replace("\\", "\\\\", $value);
                    $escaped = str_replace("\n", "\\n", $escaped);
                    $escaped = str_replace("\t", "\\t", $escaped);
                    $escaped = str_replace("\"", "\\\"", $escaped);
                    $output .= '"' . $escaped . "\",\n";
                } else {
                    $escaped = str_replace("\\", "\\\\", $value);
                    $escaped = str_replace("'", "\\'", $escaped);
                    $output .= "'" . $escaped . "',\n";
                }
            }
        }

        return $output;
    }
}

