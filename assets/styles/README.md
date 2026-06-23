# Thème « kibatic » — Datagrid Bundle

Thème par défaut neutre et soigné pour le Kibatic Datagrid Bundle. Conçu pour
être **embarqué dans le bundle** et **recoloré par projet** via un seul token.

## Activer le thème

Dans un GridBuilder :

```php
use Kibatic\DatagridBundle\Grid\Theme;

$grid->setTheme(Theme::KIBATIC);
```

Côté assets du projet, importer le point d'entrée SCSS :

```scss
@use '@kibatic/datagrid-bundle/styles/theme';
```

## Recolorer pour un client

Surcharger les tokens de marque dans une feuille de style **projet** (jamais ici) :

```css
:root {
    --brand-500: #c026d3; /* couleur principale du client */
    --brand-600: #a21caf; /* hover */
    --brand-700: #86198f; /* pressed */
}
```

Le token `--brand-500` cascade sur les boutons, liens, focus rings, lignes
actives et la pagination. C'est le seul override requis pour un reskin de base.

## Organisation

```
assets/styles/
├── theme.scss            point d'entrée (@use des fondations + composants)
├── abstracts/
│   ├── _tokens.scss      design tokens (custom properties CSS)
│   └── _mixins.scss      mixins partagés (focus-ring, tabular-nums…)
├── base/
│   └── _typography.scss  styles au niveau élément
└── components/           un partial = un composant (datagrid, badges, …)
```

**Règle clé** : les valeurs thémables restent des **custom properties CSS**
(`--brand-500`), pas des variables Sass figées — sinon on perd l'override
runtime par projet. Le SCSS sert à organiser et factoriser (mixins), pas à
geler les couleurs.

## Templates Twig

Le rendu Twig du thème vit dans
`src/Resources/views/theme/kibatic/`, en parallèle de `theme/bootstrap5/`. Le
SCSS de ce dossier style le markup que ces templates émettent.
