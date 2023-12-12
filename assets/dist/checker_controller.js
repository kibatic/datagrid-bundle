import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ["master", "checkbox"]

    masterTargetConnected(element) {
        element.addEventListener("click", this.toggleAll.bind(this))
    }

    toggleAll() {
        console.log(this.checkboxTargets)

        for (let checkbox of this.checkboxTargets) {
            checkbox.checked = this.masterTarget.checked
        }
    }
}
