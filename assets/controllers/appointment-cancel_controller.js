import {
    Controller
} from '@hotwired/stimulus'
import {
    Modal
} from 'bootstrap'

export default class extends Controller {
    static targets = ['modal', 'title', 'body', 'confirmBtn', 'spinner', 'successModal', 'successBody']
    static values = {
        appointmentId: Number,
        startAtUtcIso: String,
        amountCents: Number,
        csrf: String,
        cancelUrl: String,
        timezone: {
            type: String,
            default: 'Europe/Paris'
        }
    }

    // Ouvre la modale de confirmation
    open(event) {
        event.preventDefault()
        const policy = this._computePolicy()
        const startLocal = this._formatInTZ(this.startAtUtcIsoValue, this.timezoneValue)

        const amountCents = this.amountCentsValue ?? 0
        const refundCents = Math.round(amountCents * policy.percent)

        this.titleTarget.textContent = 'Confirmer l\'annulation du rendez-vous'
        this.bodyTarget.innerHTML = `
      <p>Vous êtes sur le point d'annuler votre rendez-vous prévu le <strong>${startLocal}</strong>.</p>
      <hr>
      <p>${policy.message}</p>
      ${
        amountCents > 0
          ? `<p>Montant payé : <strong>${this._fmtEuros(amountCents)}</strong><br>
               Remboursement estimé : <strong>${this._fmtEuros(refundCents)}</strong></p>`
          : ''
      }
    `
        this.confirmBtnTarget.disabled = false
        this._showModal()
    }

    // Envoi de la requête d'annulation
    async confirm(event) {
        event.preventDefault()
        this.confirmBtnTarget.disabled = true
        this.spinnerTarget.classList.remove('d-none')

        try {
            const res = await fetch(this.cancelUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfValue,
                },
                body: JSON.stringify({
                    id: this.appointmentIdValue
                }),
            })

            const data = await res.json()
            if (!res.ok || !data.ok) throw new Error(data.error || 'Erreur serveur')

            this._hideModal()

            // Affiche la modale de succès
            const startLocal = this._formatInTZ(this.startAtUtcIsoValue, this.timezoneValue)
            const refundTxt =
                data.refund && data.refund.percent > 0 ?
                `Montant remboursé : <strong>${this._fmtEuros(data.refund.amount)}</strong> (${data.refund.percent}%)` :
                'Aucun remboursement n\'est dû selon les CGV.'

            this.successBodyTarget.innerHTML = `
        <p>Votre rendez-vous du <strong>${startLocal}</strong> a bien été annulé.</p>
        <p>${refundTxt}</p>
      `
            this._showSuccessModal()

            // Recharge la page après quelques secondes
            setTimeout(() => window.location.reload(), 4000)
        } catch (e) {
            console.error(e)
            this.bodyTarget.insertAdjacentHTML(
                'beforeend',
                `<p class="text-danger mt-2">Une erreur est survenue. Merci de réessayer.</p>`
            )
        } finally {
            this.spinnerTarget.classList.add('d-none')
            this.confirmBtnTarget.disabled = false
        }
    }

    // ==================== Helpers ====================

    // Politique en JOURS OUVRÉS (lun→ven) : on exclut samedi/dimanche
    _computePolicy() {
        const now = new Date() // now local, comparaison en ms OK
        const start = new Date(this.startAtUtcIsoValue) // ISO "Z" → UTC, Date gère en ms absolus

        const workingHours = this._workingHoursExclWeekends(now, start)

        if (workingHours > 48)
            return {
                percent: 1,
                message: 'Plus de 48h ouvrées avant le RDV : remboursement intégral (100%).'
            }
        if (workingHours > 24)
            return {
                percent: 0.5,
                message: 'Entre 48h et 24h ouvrées avant le RDV : remboursement à 50%.'
            }
        return {
            percent: 0,
            message: 'Moins de 24h ouvrées avant le RDV : aucun remboursement.'
        }
    }

    /**
     * Calcule les heures entre `from` et `to` en excluant samedis/dimanches.
     * Compte toutes les heures des jours lun→ven (24h par jour ouvré).
     * Itération par jour (performant).
     */
    _workingHoursExclWeekends(from, to) {
        if (to <= from) return 0

        // On travaille en ms
        const H = 3600000
        const D = 24 * H

        // Curseurs par jour
        let cursor = new Date(from.getTime())
        let totalMs = 0

        while (cursor < to) {
            const dayStart = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate(), 0, 0, 0, 0)
            const dayEnd = new Date(dayStart.getTime() + D)

            const isWeekend = dayStart.getDay() === 0 || dayStart.getDay() === 6 // 0=dim,6=sam

            const segStart = cursor > dayStart ? cursor : dayStart
            const segEnd = to < dayEnd ? to : dayEnd

            if (!isWeekend && segEnd > segStart) {
                totalMs += (segEnd.getTime() - segStart.getTime())
            }

            // Passe au jour suivant
            cursor = dayEnd
        }

        return totalMs / H
    }

    _formatInTZ(isoUtc, tz) {
        const d = new Date(isoUtc)
        const fDate = new Intl.DateTimeFormat('fr-FR', {
            dateStyle: 'full',
            timeZone: tz
        }).format(d)
        const fTime = new Intl.DateTimeFormat('fr-FR', {
            timeStyle: 'short',
            hour12: false,
            timeZone: tz,
        }).format(d)
        return `${fDate} à ${fTime}`
    }

    _fmtEuros(cents) {
        return (cents / 100).toLocaleString('fr-FR', {
            style: 'currency',
            currency: 'EUR',
        })
    }

    // Gestion modales Bootstrap
    _showModal() {
        this._bsModal = this._bsModal ?? new Modal(this.modalTarget)
        this._bsModal.show()
    }

    _hideModal() {
        if (this._bsModal) this._bsModal.hide()
    }

    _showSuccessModal() {
        this._successModal = this._successModal ?? new Modal(this.successModalTarget)
        this._successModal.show()
    }
}
