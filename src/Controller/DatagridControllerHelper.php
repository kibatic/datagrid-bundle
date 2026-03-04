<?php

namespace Kibatic\DatagridBundle\Controller;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait DatagridControllerHelper
{
    public function createFilterFormBuilder(string $name = 'filters', string $method = 'GET', bool $csrfProtection = false, array $options = []): FormBuilderInterface
    {
        $defaultOptions = [
            'method' => $method,
        ];

        if ($this->container->get('form.factory')->create()->getConfig()->hasOption('csrf_protection')) {
            $defaultOptions['csrf_protection'] = $csrfProtection;
        }

        return $this->container->get('form.factory')->createNamedBuilder($name, options: array_merge($defaultOptions, $options));
    }

    protected function assertCsrfTokenValid(Request $request, $tokenId, string $tokenName = '_token'): void
    {
        $token = $request->getPayload()->get($tokenName);

        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            throw new AccessDeniedHttpException("Invalid CSRF token\nid : $tokenId\ntoken : $token");
        }
    }

    protected function assertBatchCsrfTokenValid(Request $request, string $datagridBuilderClass): void
    {
        $token = $request->getPayload()->get('_batch_csrf_token');

        if (!$this->isCsrfTokenValid($datagridBuilderClass, $token)) {
            throw new AccessDeniedHttpException("Invalid CSRF token\nid : $tokenId\ntoken : $token");
        }
    }

    /**
     * @return int[]
     */
    private function getBatchIds(Request $request): array
    {
        $ids = $request->request->all('ids');

        if (empty($ids)) {
            $ids = $request->query->all('ids');
        }

        return
            array_values(
                array_filter(
                    array_map('intval', $ids)
                )
            )
        ;
    }
}
