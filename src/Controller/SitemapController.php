<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function index(UrlGeneratorInterface $urlGenerator): Response
    {
        $routes = [
            ['name' => 'home', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['name' => 'bachata', 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['name' => 'salsa', 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['name' => 'kizomba', 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['name' => 'anglais', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['name' => 'espagnol', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['name' => 'rando', 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['name' => 'events', 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['name' => 'adhesion', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['name' => 'about', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['name' => 'contact', 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['name' => 'donation', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ];

        $urls = [];

        foreach ($routes as $route) {
            $urls[] = [
                'loc' => $urlGenerator->generate(
                    $route['name'],
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'changefreq' => $route['changefreq'],
                'priority' => $route['priority'],
            ];
        }

        return new Response(
            $this->renderXml($urls),
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );
    }

    private function renderXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($url['loc'], ENT_XML1) . '</loc>';
            $xml .= '<changefreq>' . $url['changefreq'] . '</changefreq>';
            $xml .= '<priority>' . $url['priority'] . '</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return $xml;
    }
}