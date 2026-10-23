import { Controller } from '@hotwired/stimulus'
import Swal from 'sweetalert2'

/*
 * Sélection par lot du datagrid.
 * - bascule la barre d'actions (affichée dès qu'une ligne est cochée) ;
 * - met à jour le compteur, l'état de la case « tout sélectionner » et le
 *   surlignage des lignes ;
 * - soumet le formulaire vers l'URL de l'action choisie (avec confirmation
 *   optionnelle).
 */
export default class extends Controller {
    static targets = ['bar', 'toolbar', 'count', 'master', 'checkbox', 'form']

    // Libellés de la confirmation, communs à la grille et fournis traduits par le projet.
    static values = {
        confirmTitle: String,
        confirmYes: String,
        confirmNo: String,
    }

    connect() {
        this.refresh()
    }

    toggleAll() {
        for (const checkbox of this.checkboxTargets) {
            checkbox.checked = this.masterTarget.checked
        }
        this.refresh()
    }

    refresh() {
        const total = this.checkboxTargets.length
        const selected = this.checkboxTargets.filter((c) => c.checked).length

        if (this.hasCountTarget) {
            this.countTarget.textContent = selected
        }

        if (this.hasBarTarget) {
            this.barTarget.hidden = selected === 0
            // Expose la hauteur de la barre pour décaler le sticky de l'en-tête
            // (évite tout chevauchement). 0 quand la barre est masquée.
            const barHeight = selected === 0 ? 0 : this.barTarget.offsetHeight
            this.element.style.setProperty('--datagrid-batchbar-h', `${barHeight}px`)
        }

        if (this.hasToolbarTarget) {
            this.toolbarTarget.hidden = selected > 0
        }

        if (this.hasMasterTarget) {
            this.masterTarget.checked = selected > 0 && selected === total
            this.masterTarget.indeterminate = selected > 0 && selected < total
        }

        for (const checkbox of this.checkboxTargets) {
            const row = checkbox.closest('tr')
            if (row) {
                row.classList.toggle('selected', checkbox.checked)
            }
        }
    }

    clear() {
        for (const checkbox of this.checkboxTargets) {
            checkbox.checked = false
        }

        if (this.hasMasterTarget) {
            this.masterTarget.checked = false
            this.masterTarget.indeterminate = false
        }

        this.refresh()
    }

    submit({ params }) {
        const run = () => {
            this.formTarget.action = params.url
            this.formTarget.submit()
        }

        if (params.confirm) {
            // Le message peut contenir du HTML simple (ex. <br />), comme le contrôleur confirm de Kibatic UX.
            Swal.fire({
                title: params.confirmTitle || this.confirmTitleValue,
                html: params.confirmText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: this.confirmYesValue,
                cancelButtonText: this.confirmNoValue,
            }).then((result) => {
                if (result.isConfirmed) {
                    run()
                }
            })

            return
        }

        run()
    }
}
