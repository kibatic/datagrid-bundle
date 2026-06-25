# Thème « kibatic » — Datagrid Bundle

Thème par défaut neutre et soigné pour le Kibatic Datagrid Bundle. Conçu pour
être **embarqué dans le bundle** et **recoloré par projet** via un seul token.

## Activer le thème

Dans un GridBuilder :

```php
use Kibatic\DatagridBundle\Grid\Theme;

$grid->setTheme(Theme::KIBATIC);
```

Côté assets du projet, le bundle expose **un point d'entrée scopé** plus ses
**partials réutilisables** (avec le dossier `assets/styles` du bundle dans le
`load_path` du sass-bundle).

### `kibatic/datagrid` — intégration dans un projet existant (scopé)
```scss
@use 'kibatic/datagrid';
```
Tout est confiné sous `.kibatic-datagrid` (wrapper ajouté automatiquement
autour du rendu du datagrid). **Aucun effet de bord** sur l'UI existante : ni
sur les éléments de base, ni sur les classes Bootstrap hors datagrid, ni sur
les variables `--bs-*`. La couleur de marque est **adoptée du projet hôte**
(`--bs-primary`) si présente, sinon la marque kibatic par défaut.

### Mode greenfield (design system global) — assemblé côté projet
Le bundle ne fournit **pas** de point d'entrée global : appliquer le design au
niveau global (typo de base, remap Bootstrap, composants hors `.kibatic-datagrid`)
est une décision applicative. Un projet greenfield compose lui-même les partials
du bundle dans son `app.scss` :
```scss
@use 'kibatic/base/fonts';
@use 'kibatic/abstracts/tokens';
@use 'kibatic/base/typography';
@use 'kibatic/base/bootstrap';
@use 'kibatic/styles';

:root {
    @include tokens.tokens;
}

@include styles.components;
```
Voir `assets/styles/app.scss` du projet demo pour un exemple complet.

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

Tout le SCSS du thème est regroupé sous `assets/styles/kibatic/` (en miroir du
thème Twig `src/Resources/views/theme/kibatic/`), pour ne pas se mélanger aux
éventuels autres thèmes.

```
assets/styles/kibatic/
├── datagrid.scss         point d'entrée scopé (.kibatic-datagrid)
├── _styles.scss          agrégateur des composants (mixin `components`)
├── abstracts/
│   ├── _tokens.scss      design tokens (custom properties CSS)
│   └── _mixins.scss      mixins partagés (focus-ring, tabular-nums…)
├── base/
│   ├── _fonts.scss       @font-face Inter auto-hébergée
│   ├── _typography.scss  styles au niveau élément
│   ├── _bootstrap.scss   remap des variables Bootstrap (filet de sécurité)
│   └── _compat.scss      fallbacks cross-browser
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
