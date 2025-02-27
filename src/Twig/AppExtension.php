<?php

namespace Kibatic\DatagridBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('inline_attr', $this->attributesToHtml(...), ['is_safe' => ['html']]),
            new TwigFilter('inline_if', $this->inlineIf(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('datagrid_reset_url', $this->resetUrl(...)),
        ];
    }

    public static function attributesToHtml(array $attributes): string
    {
        return array_reduce(
            array_keys($attributes),
            function (string $carry, string $key) use ($attributes) {
                $value = $attributes[$key];

                if (!\is_scalar($value) && null !== $value) {
                    throw new \LogicException(sprintf('A "%s" was passed, the value is not a scalar (it\'s a %s)', $key, $key, get_debug_type($value)));
                }

                if (null === $value) {
                    throw new \Exception('Passing "null" as an attribute value is forbidden');
                }

                return match ($value) {
                    true => "{$carry} {$key}",
                    false => $carry,
                    default => sprintf('%s %s="%s"', $carry, $key, $value),
                };
            },
            ''
        );
    }

    public function inlineIf(array $array, string $separator = ' '): string
    {
        $filtered = array_filter($array, fn ($value) => $value !== false);

        return implode($separator, array_keys($filtered));
    }

    public function resetUrl(FormView $form): string
    {
        if ($form->vars['method'] !== 'GET') {
            return '';
        }

        $request = $this->requestStack->getMainRequest();
        $queryAll = $request->query->all();
        unset($queryAll[$form->vars['name']]);

        return $this->router->generate(
            $request->attributes->get('_route'),
            array_merge($request->attributes->get('_route_params'), $queryAll)
        );
    }
}
