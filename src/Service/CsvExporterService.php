<?php

namespace App\Service;

use App\Dto\ComptabiliteLigne;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporterService
{
    /**
     * @param ComptabiliteLigne[] $lignes
     */
    public function export(array $lignes): Response
    {
        $response = new StreamedResponse(function () use ($lignes) {

            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'date',
                'nom',
                'type',
                'libelle',
                'discipline',
                'moyen_paiement',
            ], ';');

            foreach ($lignes as $ligne) {

                fputcsv($handle, [
                    $ligne->date->format('d/m/Y'),
                    $ligne->type,
                    $ligne->libelle,
                    $ligne->discipline ?? '',
                    $ligne->moyenPaiement->name,
                ], ';');

            }

            fclose($handle);

        });

        $filename = 'comptabilite_' . date('Y-m-d') . '.csv';

        $response->headers->set(
            'Content-Type',
            'text/csv; charset=UTF-8'
        );

        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="' . $filename . '"'
        );

        return $response;
    }
}