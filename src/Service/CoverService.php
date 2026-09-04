<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CoverService
{
    private const MUSICBRAINZ_URL =
        'https://musicbrainz.org/ws/2/recording';

    private const COVER_ART_URL =
        'https://coverartarchive.org/release';

    private const USER_AGENT =
        'Stras4Water/1.0 (https://stras4water.org)';

    private string $projectDir;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        KernelInterface $kernel
    ) {
        $this->projectDir = $kernel->getProjectDir();
    }

    public function getCover(
        string $artist,
        string $title
    ): string {
        $artist = trim($artist);
        $title = trim($title);

        if ($artist === '' || $title === '') {
            return '/images/default-cover.png';
        }

        $cacheKey = $this->getCacheKey(
            $artist,
            $title
        );

        $cacheDirectory =
            $this->getCacheDirectory();

        $cacheFile =
            $cacheDirectory
            . '/'
            . $cacheKey
            . '.jpg';

        /*
         * Cache local
         */
        if (
            is_file($cacheFile)
            &&
            filesize($cacheFile) > 0
        ) {
            return '/upload/covers/' . $cacheKey . '.jpg';
        }

        try {

            /*
             * On essaie plusieurs versions du titre.
             */
            $titleVariants =
                $this->buildTitleVariants($title);

            foreach ($titleVariants as $titleVariant) {

                $releaseIds =
                    $this->findReleaseIds(
                        $artist,
                        $titleVariant
                    );

                if (empty($releaseIds)) {
                    continue;
                }

                /*
                 * On classe les releases.
                 *
                 * Pour commencer, on garde simplement
                 * l'ordre MusicBrainz mais on essaie
                 * toutes les releases.
                 */
                $releaseIds =
                    $this->sortReleaseIds(
                        $releaseIds
                    );

                foreach ($releaseIds as $releaseId) {

                    $imageUrl =
                        $this->findCoverUrl(
                            $releaseId
                        );

                    if ($imageUrl === null) {
                        continue;
                    }

                    $this->downloadCover(
                        $imageUrl,
                        $cacheFile
                    );

                    if (
                        is_file($cacheFile)
                        &&
                        filesize($cacheFile) > 0
                    ) {
                        return
                            '/upload/covers/'
                            . $cacheKey
                            . '.jpg';
                    }
                }
            }

        } catch (\Throwable) {
            /*
             * Une erreur externe ne doit jamais
             * empêcher l'écran Live.
             */
        }

        return '/img/default-cover.jpg';
    }


    /**
     * Génère plusieurs variantes du titre.
     */
    private function buildTitleVariants(
        string $title
    ): array {

        $variants = [];

        /*
         * Titre complet.
         */
        $variants[] = $title;

        /*
         * Supprime les parenthèses.
         *
         * Exemple :
         *
         * Vivir Mi Vida
         * (Official Remix Version Extended Dance Floor)
         *
         * devient :
         *
         * Vivir Mi Vida
         */
        $withoutParentheses =
            preg_replace(
                '/\s*\([^)]*\)/u',
                '',
                $title
            );

        if (
            is_string($withoutParentheses)
            &&
            trim($withoutParentheses) !== ''
        ) {
            $variants[] =
                trim($withoutParentheses);
        }

        /*
         * Supprime certaines indications DJ
         * placées après un tiret.
         */
        $withoutDjSuffix =
            preg_replace(
                '/\s*[-–—]\s*(extended|remix|edit|radio edit|version|mix).*$/iu',
                '',
                $title
            );

        if (
            is_string($withoutDjSuffix)
            &&
            trim($withoutDjSuffix) !== ''
        ) {
            $variants[] =
                trim($withoutDjSuffix);
        }

        /*
         * Suppression des doublons.
         */
        $result = [];

        foreach ($variants as $variant) {

            $variant = trim($variant);

            if ($variant === '') {
                continue;
            }

            $key =
                $this->normalize($variant);

            if (isset($result[$key])) {
                continue;
            }

            $result[$key] = $variant;
        }

        return array_values($result);
    }


    /**
     * Recherche les releases correspondant
     * à l'artiste et au titre.
     */
    private function findReleaseIds(
        string $artist,
        string $title
    ): array {

        try {

            $response =
                $this->httpClient->request(
                    'GET',
                    self::MUSICBRAINZ_URL,
                    [
                        'query' => [

                            'query' => sprintf(
                                'artist:"%s" AND recording:"%s"',
                                $artist,
                                $title
                            ),

                            'fmt' => 'json',

                            'limit' => 10,
                        ],

                        'headers' => [

                            'User-Agent' =>
                                self::USER_AGENT,

                            'Accept' =>
                                'application/json',
                        ],

                        'timeout' => 5,
                    ]
                );

            if (
                $response->getStatusCode()
                !==
                200
            ) {
                return [];
            }

            $data =
                $response->toArray(false);

            if (
                empty($data['recordings'])
            ) {
                return [];
            }

            $releaseIds = [];

            foreach (
                $data['recordings']
                as $recording
            ) {

                $recordingTitle =
                    $recording['title']
                    ?? '';

                /*
                 * Le titre doit correspondre.
                 */
                if (
                    $this->normalize(
                        $recordingTitle
                    )
                    !==
                    $this->normalize($title)
                ) {
                    continue;
                }

                /*
                 * Vérification artiste.
                 */
                $artistMatches = false;

                foreach (
                    $recording['artist-credit']
                    ?? []
                    as $artistCredit
                ) {

                    $name =
                        $artistCredit['name']
                        ??
                        $artistCredit['artist']['name']
                        ??
                        '';

                    if (
                        $this->normalize($name)
                        ===
                        $this->normalize($artist)
                    ) {
                        $artistMatches = true;
                        break;
                    }
                }

                if (!$artistMatches) {
                    continue;
                }

                foreach (
                    $recording['releases']
                    ?? []
                    as $release
                ) {

                    if (
                        !empty($release['id'])
                    ) {
                        $releaseIds[] =
                            $release['id'];
                    }
                }
            }

            return array_values(
                array_unique(
                    $releaseIds
                )
            );

        } catch (\Throwable) {

            return [];
        }
    }


    /**
     * Pour l'instant on garde l'ordre retourné
     * par MusicBrainz.
     *
     * Cette méthode est volontairement simple :
     * on pourra ensuite ajouter un vrai scoring
     * quand on aura identifié les releases problématiques.
     */
    private function sortReleaseIds(
        array $releaseIds
    ): array {

        return array_values(
            array_unique($releaseIds)
        );
    }


    /**
     * Cherche la meilleure image de la release.
     */
    private function findCoverUrl(
        string $releaseId
    ): ?string {

        try {

            $response =
                $this->httpClient->request(
                    'GET',
                    self::COVER_ART_URL
                    . '/'
                    . $releaseId,
                    [
                        'headers' => [

                            'User-Agent' =>
                                self::USER_AGENT,

                            'Accept' =>
                                'application/json',
                        ],

                        'timeout' => 5,
                    ]
                );

            if (
                $response->getStatusCode()
                !==
                200
            ) {
                return null;
            }

            $data =
                $response->toArray(false);

            if (
                empty($data['images'])
            ) {
                return null;
            }

            /*
             * Priorité à une vraie pochette avant.
             */
            foreach (
                $data['images']
                as $image
            ) {

                if (
                    ($image['front'] ?? false)
                    &&
                    !empty(
                        $image['thumbnails']['500']
                    )
                ) {
                    return
                        $image['thumbnails']['500'];
                }
            }

            /*
             * Sinon première image.
             */
            foreach (
                $data['images']
                as $image
            ) {

                if (
                    !empty(
                        $image['thumbnails']['500']
                    )
                ) {
                    return
                        $image['thumbnails']['500'];
                }

                if (
                    !empty($image['image'])
                ) {
                    return
                        $image['image'];
                }
            }

        } catch (\Throwable) {

            return null;
        }

        return null;
    }


    /**
     * Télécharge la pochette dans le cache local.
     */
    private function downloadCover(
        string $url,
        string $destination
    ): void {

        try {

            $response =
                $this->httpClient->request(
                    'GET',
                    $url,
                    [
                        'headers' => [

                            'User-Agent' =>
                                self::USER_AGENT,
                        ],

                        'timeout' => 10,
                    ]
                );

            if (
                $response->getStatusCode()
                !==
                200
            ) {
                return;
            }

            $content =
                $response->getContent();

            if ($content === '') {
                return;
            }

            file_put_contents(
                $destination,
                $content
            );

        } catch (\Throwable) {

            // Rien : la cover par défaut sera utilisée.
        }
    }


    /**
     * Répertoire du cache.
     */
    private function getCacheDirectory(): string
    {
        $directory =
            $this->projectDir
            . '/public/upload/covers';

        if (!is_dir($directory)) {

            $filesystem =
                new Filesystem();

            $filesystem->mkdir(
                $directory
            );
        }

        return $directory;
    }


    /**
     * Clé du cache.
     */
    private function getCacheKey(
        string $artist,
        string $title
    ): string {

        return md5(
            mb_strtolower(
                $artist
                . '|'
                . $title
            )
        );
    }


    /**
     * Normalisation des chaînes.
     */
    private function normalize(
        string $value
    ): string {

        $value =
            mb_strtolower(
                trim($value)
            );

        $converted =
            iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                $value
            );

        if (
            $converted !== false
        ) {
            $value = $converted;
        }

        $value =
            preg_replace(
                '/[^a-z0-9]+/',
                ' ',
                $value
            );

        return trim($value);
    }
}