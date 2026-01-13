<?php

namespace Database\Factories\Traits;

trait RemoveAccents
{
    /**
     * Remove accents from a string
     *
     * @param string $string
     * @return string
     */
    protected function removeAccents(string $string): string
    {
        $unwanted = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N'
        ];
        
        return strtr($string, $unwanted);
    }
}
