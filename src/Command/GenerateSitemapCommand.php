<?php

namespace App\Command;

use App\Entity\AppointmentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

use function Safe\parse_url;

#[AsCommand(
    name: 'app:generate-sitemap',
    description: 'Génère public/sitemap.xml à partir des URLs publiques de l\'application.',
)]
final class GenerateSitemapCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly KernelInterface $kernel,
        private readonly RouterInterface $router,
        private readonly string $hostname,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Sécuriser un hostname propre
        $hostname = rtrim($this->hostname, '/');

        // (Optionnel mais utile en CLI) : met à jour le contexte du router
        // pour éviter les mauvaises surprises si quelqu'un génère des URLs absolues
        $context = $this->router->getContext();
        if ($hostname !== '') {
            $parts = parse_url($hostname);
            if (is_array($parts)) {
                if (!empty($parts['host'])) {
                    $context->setHost($parts['host']);
                }
                if (!empty($parts['scheme'])) {
                    $context->setScheme($parts['scheme']);
                }
                if (!empty($parts['path'])) {
                    $context->setBaseUrl(rtrim($parts['path'], '/'));
                }
            }
        }

        $urls = [];

        // 1) URLs statiques (publiques)
        // Option : est-ce qu'on veut indexer /rendez-vous/list ?
        $includeAppointmentList = false;

        $staticRoutes = [
            ['route' => 'app_home', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['route' => 'app_about', 'changefreq' => 'yearly', 'priority' => '0.7'],
            ['route' => 'app_contact', 'changefreq' => 'yearly', 'priority' => '0.6'],

            // Légal
            ['route' => 'app_legal', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['route' => 'app_privacy', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['route' => 'app_terms', 'changefreq' => 'yearly', 'priority' => '0.3'],

            // Pages "métier" publiques
            ['route' => 'app_appointment_types', 'changefreq' => 'weekly', 'priority' => '0.8'],

            // (à toi de voir) : inscription / login -> souvent inutile en SEO
            // ['route' => 'app_register', 'changefreq' => 'yearly', 'priority' => '0.2'],
            // ['route' => 'app_login', 'changefreq' => 'yearly', 'priority' => '0.2'],

            ['route' => 'app_numerologie', 'changefreq' => 'monthly', 'priority' => '0.8'],
        ];

        if ($includeAppointmentList) {
            $staticRoutes[] = ['route' => 'app_appointment_list', 'changefreq' => 'daily', 'priority' => '0.6'];
        }

        foreach ($staticRoutes as $item) {
            $path = $this->urlGenerator->generate($item['route']);

            $urls[] = [
                'loc' => $hostname . $path,
                'lastmod' => (new \DateTimeImmutable())->format('c'), // ✅ ISO 8601
                'changefreq' => $item['changefreq'],
                'priority' => $item['priority'],
            ];
        }

        // 2) Pages SEO locales (route unique seo_local_page + slugs whitelistés)
        $localSeoSlugs = [
            'numerologie-la-plaine-sur-mer',
            'numerologue-pornic',
            'numerologue-saint-brevin',
            'numerologue-saint-nazaire',
            'numerologue-nantes',
        ];

        foreach ($localSeoSlugs as $slug) {
            $path = $this->urlGenerator->generate('seo_local_page', ['slug' => $slug]);

            $urls[] = [
                'loc' => $hostname . $path,
                'lastmod' => (new \DateTimeImmutable())->format('c'),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
        }

        // 3) Urls dynamiques "SEO OK" : page détail d'un type de rendez-vous
        // Ici : la page détail d'un type de rendez-vous : /rendez-vous/type/{id}
        $types = $this->em->getRepository(AppointmentType::class)->findAll();

        foreach ($types as $type) {
            $lastmod = $type->getUpdatedAt() ?? new \DateTimeImmutable();

            $urls[] = [
                'loc' => $hostname . $this->urlGenerator->generate('app_appointment_type_detail', ['id' => $type->getId()]),
                'lastmod' => $lastmod->format('c'), // ✅ ISO 8601
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        // 4) Génération XML
        $xml = $this->generateXml($urls);

        // 5) Ecriture dans public/sitemap.xml
        $target = $this->kernel->getProjectDir() . '/public/sitemap.xml';
        file_put_contents($target, $xml);

        $output->writeln(sprintf('<info>Sitemap généré : %s (%d URL)</info>', $target, count($urls)));

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array{loc:string,lastmod:string,changefreq:string,priority:string}> $urls
     */
    private function generateXml(array $urls): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

        foreach ($urls as $url) {
            $urlElement = $xml->addChild('url');
            $urlElement->addChild('loc', htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8'));
            $urlElement->addChild('lastmod', $url['lastmod']);
            $urlElement->addChild('changefreq', $url['changefreq']);
            $urlElement->addChild('priority', $url['priority']);
        }

        return $xml->asXML() ?: '';
    }
}
