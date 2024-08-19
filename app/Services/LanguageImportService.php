<?php
namespace App\Services;

use Illuminate\Filesystem\Filesystem;

class LanguageImportService {

    public function addLanguageEntry($filePath, $keyPath, $value, $prefix = 'installer::app.')
    {
        $filesystem = new Filesystem();
        $langArray = $filesystem->exists($filePath) ? include($filePath) : [];

        $keys = explode('.', $keyPath);

        $exists = $this->keyExists($langArray, $keys);

        if ($exists) {
            return ['code' => $prefix . $keyPath, 'value' => $langArray[$keys[0]]];
        }
        $temp = &$langArray;
        foreach ($keys as $key) {
            if (!isset($temp[$key])) {
                $temp[$key] = [];
            }
            $temp = &$temp[$key];
        }
        $temp = $value;

        $exportedArray = var_export($langArray, true);
        
        $content = "<?php\n\nreturn " . $this->arrayToShortSyntax($langArray) . ";\n";

        $filesystem->put($filePath, $content);

        return ['code' => 'installer::app.' . $keyPath, 'value' => $value];
    }

    protected function keyExists(array $array, array $keys): bool
    {
        $temp = $array;
        foreach ($keys as $key) {
            if (!isset($temp[$key])) {
                return false;
            }
            $temp = $temp[$key];
        }
        return true;
    }

    public function arrayToShortSyntax(array $array, int $indentLevel = 1): string
    {
        $indent = str_repeat('    ', $indentLevel);  // Four spaces per indent level
        $arrayString = "[\n";
        
        foreach ($array as $key => $value) {
            $arrayString .= $indent . "'" . addslashes($key) . "' => ";
            
            if (is_array($value)) {
                $arrayString .= $this->arrayToShortSyntax($value, $indentLevel + 1);
            } else {
                $arrayString .= "'" . addslashes($value) . "'";
            }
            
            $arrayString .= ",\n";
        }
        
        $arrayString .= str_repeat('    ', $indentLevel - 1) . ']';
        
        return $arrayString;
    }
    
    public function searchAndAdd($keyPath, $value) {
    $filePath = 'packages' 
        . DIRECTORY_SEPARATOR . 'Webkul'
        . DIRECTORY_SEPARATOR . 'Installer'
        . DIRECTORY_SEPARATOR . 'src'
        . DIRECTORY_SEPARATOR . 'Resources'
        . DIRECTORY_SEPARATOR . 'lang'
        . DIRECTORY_SEPARATOR . 'en'
        . DIRECTORY_SEPARATOR . 'app.php';

        return $this->addLanguageEntry($filePath, $keyPath, $value);
    }

    public function createCategoryTranslationKey($categoryName): string {
        $categoryName = strtolower($categoryName);
        $categoryName = preg_replace('/[^a-z0-9]+/', '-', $categoryName);
        $categoryName = trim($categoryName, '-');

        return 'seeders.categories.' . $categoryName;
    }

    public function createAttributeTranslationKey($attributeName): string {
        $attributeName = strtolower($attributeName);
        $attributeName = preg_replace('/[^a-z0-9]+/', '-', $attributeName);
        $attributeName = trim($attributeName, '-');

        return 'seeders.attribute.attribute-options.materials.' . $attributeName;
    }
}
