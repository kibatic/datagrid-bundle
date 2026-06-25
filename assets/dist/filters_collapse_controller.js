import { Controller } from '@hotwired/stimulus'

/*
 * Replie/déplie la section des filtres avancés du datagrid.
 * Bascule la classe `is-expanded` sur l'élément racine ; le CSS du thème
 * gère la visibilité de la section avancée et l'état du bouton.
 */
export default class extends Controller {
    toggle() {
        this.element.classList.toggle('is-expanded')
    }
}
