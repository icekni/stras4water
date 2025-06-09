<?php

namespace App\Service;

use Symfony\Component\Intl\Countries;

class CountryCodeService {
    function getIsoCodes(string $countryName): ?string
    {
        $locales = ['fr', 'en', 'es', 'de', 'it', 'pt']; 
        foreach ($locales as $locale) {
            $names = Countries::getNames($locale);
            $iso2 = array_search(ucwords(strtolower($countryName)), $names);

            if ($iso2 !== false) {
                if (method_exists(Countries::class, 'getAlpha3Code')) {
                    $iso3 = Countries::getAlpha3Code($iso2);
                } else {
                    $iso3 = null;
                }

                return $iso3;
            }
        }

        return null; 
    }
}