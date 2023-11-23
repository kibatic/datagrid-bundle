<?php

namespace Kibatic\DatagridBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HtmlExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('inline_attr', $this->attributesToHtml(...), ['is_safe' => ['html']]),
        ];
    }

    public static function attributesToHtml(array $attributes): string
    {
        return array_reduce(
            array_keys($attributes),
            function (string $carry, string $key) use ($attributes) {
                $value = $attributes[$key];

                if (!\is_scalar($value) && null !== $value) {
                    throw new \LogicException(sprintf('A "%s" prop was passed when creating the component. No matching "%s" property or mount() argument was found, so we attempted to use this as an HTML attribute. But, the value is not a scalar (it\'s a %s). Did you mean to pass this to your component or is there a typo on its name?', $key, $key, get_debug_type($value)));
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
}
