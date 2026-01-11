<?php

namespace App\Controller\Settings;

use App\Ai\AiProvider;
use App\Repository\SettingRepository;
use App\Settings\SettingKeys;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultProviderUpdateController extends AbstractController
{
    #[Route('/settings/default-provider', name: 'app_settings_default_provider', methods: ['POST'])]
    public function __invoke(Request $request, SettingRepository $settings) : Response
    {
        if (!$this->isCsrfTokenValid('default_provider', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $raw = (string) $request->request->get('provider', '');
        $provider = AiProvider::tryFrom($raw);

        if (!$provider) {
            $this->addFlash('danger', 'Invalid provider.');

            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
        }

        $settings->set(SettingKeys::DEFAULT_PROVIDER, $provider->value);

        $this->addFlash('success', sprintf('Default provider set to %s.', $provider->label()));

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
    }
}
