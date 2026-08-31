# Fonctionnement du bundle datagrid

Philosophie : **moins de magie, plus de flexibilité**. Pas de génération automatique
de colonnes depuis les métadonnées Doctrine — chaque colonne, filtre et action est
déclaré explicitement en PHP, et le rendu est entièrement surchargeable côté Twig.

> Cette doc décrit l'**utilisation** du bundle. Pour intervenir dans le bundle
> lui-même (composants internes, pipeline de rendu, invariants), voir
> [internals.md](internals.md).

---

## Lecture rapide

### Le principe en une phrase

On donne un `QueryBuilder` Doctrine au `GridBuilder`, on déclare des colonnes et
des filtres en chaînant des appels, on récupère un objet `Grid` (paginé, trié,
filtré), et on le rend avec `<twig:datagrid :grid="grid" />`.

### Le flux

```
QueryBuilder + FormInterface (filtres)
        │
        ▼
GridBuilder ── addColumn() / addFilter() / addBatchAction() / setTheme()
        │
        ▼  getGrid()  →  applique tri + filtres, pagine (KnpPaginator)
        │
        ▼
Grid (objet immuable pour la vue)
        │
        ▼
<twig:datagrid :grid="grid" :form="form" />
```

### Exemple minimal

```php
// Contrôleur
$grid = $gridBuilder
    ->initialize($repository->createQueryBuilder('p'))
    ->addColumn('Nom', 'name', sortable: 'p.name')
    ->addColumn('Créé le', 'createdAt', Template::DATETIME)
    ->getGrid();

return $this->render('product/index.html.twig', ['grid' => $grid]);
```

```twig
<twig:datagrid :grid="grid" />
```

### Pattern recommandé : un GridBuilder spécialisé par entité

Plutôt que de tout déclarer dans le contrôleur, on étend `GridBuilder` dans une
classe dédiée (`App\Datagrid\ProductGridBuilder`) qui surcharge `initialize()`.
Le contrôleur devient trivial :

```php
$form = $this->createForm(ProductFiltersType::class);
$grid = $productGridBuilder->initialize(filtersForm: $form)->getGrid();
```

Squelette générable avec `bin/console make:datagrid`.

### Les 6 concepts à connaître

| Concept | Rôle |
|---|---|
| `GridBuilder` | Construit la grille : colonnes, filtres, tri, actions, thème |
| `Grid` | Résultat figé, consommé par les templates |
| `Column` | Une colonne : valeur (accessor ou closure), template de rendu, tri |
| `Template::*` | Templates de valeur fournis : TEXT, DATETIME, BOOLEAN, ENTITY, ENUM_BADGE, ACTIONS… |
| `Theme::*` | Chaîne de thèmes de rendu : `KIBATIC`, `BOOTSTRAP5` (fallback possible) |
| `Filter` | Champ du form de filtres + callback qui modifie le QueryBuilder |

---

## Lecture détaillée

### 1. Cycle de vie d'une grille

1. **`initialize(QueryBuilder, ?FormInterface $filtersForm, ?Request)`** — enregistre
   le QueryBuilder, soumet le form de filtres à la requête courante
   (`handleRequest`) s'il ne l'est pas déjà, et remet le builder à zéro (`reset()`).
   La `Request` par défaut est la main request du `RequestStack`.
2. **Déclarations** — `addColumn()`, `addFilter()`, `addBatchAction()`,
   `setTheme()`, `setItemsPerPage()`, `setPaginationKey()`,
   `setRowAttributesCallback()`…
3. **`getGrid()`** — au premier appel (mémoïsé, `forceRecreate` pour forcer) :
   - `applySort()` : lit `?sort_by=` / `?sort_order=` et applique un `orderBy`
     sur la colonne correspondante ;
   - `applyFilters()` : pour chaque filtre dont le champ de form a une valeur
     non vide, invoque son callback sur le QueryBuilder ;
   - pagine via KnpPaginator (`?page=`, ou la clé définie par
     `setPaginationKey()` — utile pour deux grilles sur la même page) ;
   - construit le `Grid`.

Le `Grid` expose aux templates : `columns`, `pagination`, `request`, `themes`,
`batchActions`, `filterLayout`, `rowAttributes(item)`.

### 2. Les colonnes

```php
addColumn(
    string|TranslatableMessage $name,   // libellé d'en-tête (traduit par le template)
    string|\Closure|null $value,        // résolution de la valeur (voir ci-dessous)
    ?string $template,                  // template de rendu de la valeur (Template::*)
    array|\Closure $templateParameters, // paramètres passés au template
    ?string $sortable,                  // valeur du ?sort_by= qui active le tri
    \Closure|string|null $sortableQuery,// expression ou callback de tri custom
    bool $enabled,                      // false = colonne ignorée
    array $headerAttr,                  // attributs HTML statiques du <th>
)
```

#### Résolution de la valeur (`Column::getValue`)

Trois formes, dans cet ordre de priorité :

- **Closure** : `fn(Product $p) => $p->getPrice()`. Si la ligne provient d'un
  `SELECT` multiple (entité + colonnes extra), la closure reçoit
  `fn($entity, array $extra)` — `$extra` contient les selects additionnels.
  Les autres formes de callable (`[$objet, 'methode']`, `'nom_de_fonction'`) ne
  sont pas acceptées : enveloppez-les avec `\Closure::fromCallable()` ou `...`.
- **string** : accessor PropertyAccess (`'name'`, `'owner.email'`). Si la
  propriété n'existe pas sur l'entité, tentative dans les données extra.
- **null** : la valeur est l'entité elle-même.

#### Template de rendu de la valeur

Sans template explicite : `Template::TEXT` (ou `Template::ARRAY` si la valeur
est un tableau). Templates fournis (`Template::*`) : `TEXT`, `DATETIME`,
`BOOLEAN`, `ARRAY`, `ENTITY` (lien vers une route), `ENUM_BADGE`,
`LABELED_ENUM`, `ROLES`, `ACTIONS` (boutons), `DUMP` (debug).

La résolution du fichier Twig se fait dans `_column_value.html.twig` par ordre
de priorité :

1. `@KibaticUX/datagrid/column_type/<template>` (surcharges du projet via ux-bundle)
2. `<thème>/column_type/<template>` pour chaque thème de la chaîne `setTheme()`
3. `@KibaticDatagrid/column_type/<template>` (fallback de base)
4. le nom brut (chemin de template custom du projet)

On peut donc passer son propre chemin de template à `addColumn`.

#### `templateParameters` : statique ou par ligne

- **Tableau statique** : `['format' => 'd/m/Y']`, `['col_class' => 'num']`.
- **Closure globale** : `fn($entity, array $extra) => [...]` — résolue **par
  ligne** avec l'entité (même signature que la closure de valeur). Exemple :

  ```php
  templateParameters: fn(Product $p) => [
      'variant' => match ($p->getCategory()) { ... },
  ],
  ```

Les templates la reçoivent résolue via `column.templateParameters(item)`.

#### `col_class` vs `headerAttr` : cellule vs en-tête

- `templateParameters['col_class']` → classe CSS posée sur chaque **`<td>`**
  (résolue par ligne, closure possible).
- `headerAttr` → attributs HTML **statiques** du **`<th>`**
  (ex. `['class' => 'num']`, extensible : `data-*`…). L'en-tête est rendu avant
  la boucle sur les lignes : il n'a pas d'entité, d'où un bag séparé et statique.

Pour une colonne numérique alignée à droite des deux côtés :

```php
templateParameters: ['col_class' => 'num'],
headerAttr: ['class' => 'num'],
```

#### Tri

- `sortable: 'p.price'` → `?sort_by=p.price&sort_order=ASC|DESC`, appliqué en
  `orderBy` tel quel.
- `sortableQuery: 'b.editor.name'` → découple le nom public (`sortable`) de
  l'expression DQL réellement utilisée.
- `sortableQuery: fn(QueryBuilder $qb, string $direction) => ...` → tri
  entièrement custom (ex. `NULLS LAST` émulé).

### 3. Les filtres

Deux moitiés :

1. **Un FormType classique** (méthode GET, sans CSRF), créé par le contrôleur et
   passé à `initialize(filtersForm: $form)`. Types utilitaires fournis :
   `BooleanChoiceType` (oui/non/indifférent), `DateRangeType` (+ DTO `DateRange`).
2. **Des callbacks** enregistrés par `addFilter(champ, callback)` :

   ```php
   ->addFilter('category', fn(QueryBuilder $qb, ProductCategory $c) =>
       $qb->andWhere('p.category = :c')->setParameter('c', $c))
   ```

   Le callback n'est invoqué que si le champ a une valeur non vide (les booléens
   `false` passent ; les Collections vides non). Signature complète :
   `fn(QueryBuilder $qb, $formValue, FormInterface $form)`.

Options d'`addFilter` :
- `group: 'price'` — les champs d'un même groupe partagent un slot dans la barre
  de filtres (ex. min/max côte à côte). Le layout résultant est exposé par
  `Grid::getFilterLayout()`.
- `hidden: true` — le filtre est replié dans la zone « plus de filtres »
  (contrôleur Stimulus `filters_collapse`).
- `enabled: false` — ignoré (pratique pour désactiver conditionnellement).

Un champ déclaré dans `addFilter` mais absent du form lève une exception explicite.

### 4. Actions unitaires et par lot

- **Actions par ligne** : colonne `Template::ACTIONS` dont la valeur est un
  tableau de boutons `['name' => ..., 'url' => ..., 'btn_type' => ...,
  'icon' => ..., 'visible' => bool]`.
- **Actions par lot** : `addBatchAction(label, url, confirm, variant, icon)`.
  Le thème affiche alors une colonne de cases à cocher (contrôleur Stimulus
  `batch`) et une barre d'actions. Les ids cochés sont postés en `ids[]`
  (méthode configurable par `setBatchMethod()`) avec un jeton CSRF
  `_batch_csrf_token` dont l'id est la classe du GridBuilder.

Côté contrôleur destinataire, le trait `DatagridControllerHelper` fournit
`assertBatchCsrfTokenValid($request, ProductGridBuilder::class)` et
`getBatchIds($request)`, plus `createFilterFormBuilder()` pour construire un
form de filtres GET sans CSRF à la volée.

### 5. Thèmes et rendu

`setTheme(...themes)` accepte une **chaîne de thèmes**, du plus prioritaire au
moins prioritaire :

```php
->setTheme(Theme::KIBATIC, Theme::BOOTSTRAP5)
```

- Le **premier** est le thème *structurel* : ses gabarits `datagrid.html.twig`,
  `datagrid-table.html.twig`, `datagrid-filters.html.twig` rendent la grille.
- Les **suivants** servent de fallback pour la résolution des `column_type`
  (un type absent du thème kibatic est cherché dans bootstrap5, puis dans la
  base du bundle).

Thèmes fournis : `Theme::KIBATIC` (design system Kibatic, CSS scopé sous
`.kibatic-datagrid`, SCSS dans `assets/styles/kibatic/`) et
`Theme::BOOTSTRAP5` (défaut).

Points d'extension côté rendu :
- blocks Twig des gabarits (`grid_table_class`, `grid_header_th_class`,
  `grid_header_tr_class`) ;
- `setRowAttributesCallback(fn($item) => ['class' => ..., 'data-x' => ...])` —
  attributs du `<tr>` par ligne, rendus par `Grid::getRowAttributes()` ;
- filtres Twig utilitaires (`AppExtension`) : `inline_attr` (tableau →
  attributs HTML) et `inline_if`.

### 6. Composants Twig

- `<twig:datagrid :grid="grid" :form="form" />` — rend la grille complète
  (filtres + table) ; sans `form`, rend la table seule. Ajoute automatiquement
  la classe `kibatic-datagrid` quand le thème structurel est kibatic.
- `<twig:datagrid-filters :form="form" :grid="grid" />` — barre de filtres
  seule (avec `relatedTurboFrames` pour recharger des frames Turbo à la
  soumission).

### 7. Pagination

Déléguée à KnpPaginator : `setItemsPerPage()` (défaut : `knp_paginator.page_limit`),
`setPaginationKey('productPage')` pour éviter les collisions entre plusieurs
grilles, `setExplicitRoute()` quand la route de pagination ne peut pas être
déduite de la requête courante (ex. grille rendue dans un fragment ou un live
component).
