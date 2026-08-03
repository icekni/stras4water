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
    public function generate(array $lignes): string
    {
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'date',
            'type',
            'libelle',
            'tarif',
            'discipline',
            'moyen_paiement',
        ], ';');

        foreach ($lignes as $ligne) {

            fputcsv($handle, [
                $ligne->date->format('d/m/Y'),
                $ligne->type,
                $ligne->libelle,
                $ligne->typeTarif ?? '',
                $ligne->discipline ?? '',
                $ligne->moyenPaiement->name,
            ], ';');
        }

        rewind($handle);

        $csv = stream_get_contents($handle);

        fclose($handle);

        return $csv;
    }

    public function export(array $lignes): Response
    {
        $csv = $this->generate($lignes);

        return new Response(
            $csv,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="comptabilite_'.date('Y-m-d').'.csv"',
            ]
        );
    }

    public function exportAdherents(array $users): Response
    {
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'nom',
            'prenom',
            'email',
        ], ';');

        foreach ($users as $user) {

            fputcsv($handle, [
                $user->getNom(),
                $user->getPrenom(),
                $user->getEmail(),
            ], ';');
        }

        rewind($handle);

        $csv = stream_get_contents($handle);

        fclose($handle);

        return new Response(
            $csv,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="adherents_'.date('Y-m-d').'.csv"',
            ]
        );
    }
}