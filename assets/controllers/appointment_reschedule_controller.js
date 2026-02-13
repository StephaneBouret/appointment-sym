import { Controller } from "@hotwired/stimulus";
import * as bootstrap from "bootstrap";

function fmtDateFr(d, timeZone = "Europe/Paris") {
  return new Intl.DateTimeFormat("fr-FR", {
    timeZone, weekday: "long", day: "2-digit", month: "long", year: "numeric",
  }).format(d);
}
function fmtTimeFr(d, timeZone = "Europe/Paris") {
  return new Intl.DateTimeFormat("fr-FR", {
    timeZone, hour: "2-digit", minute: "2-digit", hour12: false,
  }).format(d);
}
function ymd(d) { return d.toISOString().slice(0, 10); }

export default class extends Controller {
  static targets = ["modal", "list", "spinner", "hint", "confirmBtn", "emptyNotice"];
  static values = {
    appointmentId: Number,
    typeId: Number,
    endpoint: String,         // /api/fixed-slots-range
    rescheduleUrl: String,    // route POST
    csrf: String,
    openDelayHours: { type: Number, default: 48 },
    openDays: { type: String, default: "1,2,3,4,5" }, // 1=lun..7=dim
    timeZone: { type: String, default: "Europe/Paris" },
  };

  connect() {
    this.selectedStartIsoTz = null;
    this.ensureModal();          // garantit modalTarget
    this.ensureEmptyNotice();    // garantit emptyNoticeTarget
    this.ensureList();           // garantit listTarget
    // Bootstrap modal
    const Modal = bootstrap?.Modal || window.bootstrap?.Modal;
    if (Modal && this.modalTarget) {
        const prev = Modal.getInstance(this.modalTarget);
        prev?.dispose();
        this.bs = new Modal(this.modalTarget, { backdrop: "static" });
    } else {
        console.warn("[reschedule] Bootstrap Modal introuvable");
    }
  }

  // Ouverture de la modale + chargement des créneaux
  open() {
    this.ensureModal();
    this.ensureEmptyNotice();
    this.ensureList();

    this.selectedStartIsoTz = null;
    if (this.hasConfirmBtnTarget) this.confirmBtnTarget.disabled = true;
    if (this.hasListTarget) this.listTarget.innerHTML = "";
    this.toggleSpinner(true);
    this.showEmpty(false);

    // Fenêtre de recherche: barrière -> +28 jours
    const barrier = this.computeBusinessBarrier();
    const start = barrier;
    const end = new Date(barrier.getTime() + 28 * 24 * 60 * 60 * 1000);

    const url = new URL(this.endpointValue, window.location.origin);
    url.searchParams.set("type", String(this.typeIdValue));
    url.searchParams.set("start", ymd(start));
    url.searchParams.set("end", ymd(end));

    fetch(url.toString(), { headers: { "X-Requested-With": "XMLHttpRequest" }})
      .then(r => r.ok ? r.json() : [])
      .then(events => this.renderList(events || [], barrier))
      .catch(() => this.renderList([], barrier))
      .finally(() => this.toggleSpinner(false));

    this.bs?.show();
  }

  renderList(events, barrier) {
    if (!this.hasListTarget) return;

    const valid = (events || []).filter(e => new Date(e.start) >= barrier);

    if (valid.length === 0) {
      const label = barrier.toLocaleDateString("fr-FR");
      if (this.hasEmptyNoticeTarget) {
        this.emptyNoticeTarget.innerHTML = `<strong>Aucun créneau avant ${label}.</strong> Essayez une autre semaine.`;
      }
      this.showEmpty(true);
      this.listTarget.innerHTML = "";
      return;
    }

    const byDay = new Map();
    for (const e of valid) {
      const key = String(e.start).slice(0, 10);
      (byDay.get(key) || byDay.set(key, []).get(key)).push(e);
    }

    const chunks = [];
    for (const [day, evts] of byDay.entries()) {
      const d = new Date(evts[0].start);
      const dayLabel = fmtDateFr(d, this.timeZoneValue);
      const items = evts.map(e => {
        const s = new Date(e.start), en = new Date(e.end);
        const label = `${fmtTimeFr(s, this.timeZoneValue)} – ${fmtTimeFr(en, this.timeZoneValue)}`;
        return `<button type="button" class="btn btn-outline-success btn-sm me-2 mb-2"
                  data-start="${e.start}" data-end="${e.end}">
                  ${label}
                </button>`;
      }).join("");

      chunks.push(`
        <div class="card mb-2">
          <div class="card-body py-2">
            <div class="fw-semibold mb-2">${dayLabel}</div>
            <div class="d-flex flex-wrap">${items}</div>
          </div>
        </div>
      `);
    }

    this.listTarget.innerHTML = chunks.join("");
    this.listTarget.removeEventListener("click", this.onClickSlot);
    this.listTarget.addEventListener("click", this.onClickSlot);
  }

  onClickSlot = (e) => {
    const btn = e.target.closest("button[data-start]");
    if (!btn) return;

    if (this.hasListTarget) {
      this.listTarget.querySelectorAll("button[data-start]").forEach(b => {
        b.classList.remove("btn-primary");
        b.classList.add("btn-outline-success");
      });
      btn.classList.remove("btn-outline-success");
      btn.classList.add("btn-primary");
    }

    this.selectedStartIsoTz = btn.getAttribute("data-start");
    if (this.hasConfirmBtnTarget) {
      this.confirmBtnTarget.disabled = !this.selectedStartIsoTz;
    }
  }

  async confirm() {
    if (!this.selectedStartIsoTz) return;
    if (this.hasConfirmBtnTarget) {
      this.confirmBtnTarget.disabled = true;
      this.confirmBtnTarget.insertAdjacentHTML(
        "beforeend",
        ` <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>`
      );
    }

    try {
      const resp = await fetch(this.rescheduleUrlValue, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": this.csrfValue,
        },
        body: JSON.stringify({
          id: this.appointmentIdValue,
          startIsoTz: this.selectedStartIsoTz,
        }),
      });
      const data = await resp.json();

      if (!resp.ok) {
        alert(data?.error || "Le report a échoué.");
        return;
      }

      // Succès : ferme la modale
      this.bs?.hide();

      // Toast global
      document.dispatchEvent(
        new CustomEvent("toast:show", {
          detail: { message: "Report confirmé ✅", variant: "success" },
        })
      );

      // MAJ DOM : dates sur la ligne du RDV
      const row = document.querySelector(
        `[data-appointment-row][data-appointment-id="${this.appointmentIdValue}"]`
      );
      if (row) {
        const startLbl = row.querySelector("[data-appointment-start-label]");
        const endLbl = row.querySelector("[data-appointment-end-label]");
        const fmt = (iso) =>
          new Date(iso).toLocaleString("fr-FR", { dateStyle: "long", timeStyle: "short" });

        const newStartISO = data?.start ?? this.selectedStartIsoTz;
        const newEndISO = data?.end ?? null;

        if (startLbl && newStartISO) startLbl.textContent = fmt(newStartISO);
        if (endLbl && newEndISO) endLbl.textContent = fmt(newEndISO);
      }

      // Event global (si d'autres widgets doivent réagir)
      document.dispatchEvent(
        new CustomEvent("appointment:rescheduled", {
          detail: { id: this.appointmentIdValue, start: data?.start, end: data?.end },
        })
      );
    } catch {
      alert("Erreur réseau.");
    } finally {
      this.confirmBtnTarget?.querySelector(".spinner-border")?.remove();
      if (this.hasConfirmBtnTarget) this.confirmBtnTarget.disabled = false;
    }
  }

  // ===== Helpers DOM sûrs =====
  ensureModal() {
    if (this.hasModalTarget) return;
    // fallback: cherche une .modal dans ce scope
    const el = this.element.querySelector(".modal");
    if (el) el.setAttribute("data-appointment-reschedule-target", "modal");
  }

  ensureEmptyNotice() {
    if (this.hasEmptyNoticeTarget) return;
    const body = this.modalTarget?.querySelector(".modal-body");
    if (!body) return;
    const el = document.createElement("div");
    el.className = "alert alert-warning d-none";
    el.setAttribute("data-appointment-reschedule-target", "emptyNotice");
    body.prepend(el);
  }

  ensureList() {
    if (this.hasListTarget) return;
    const body = this.modalTarget?.querySelector(".modal-body");
    if (!body) return;
    const el = document.createElement("div");
    el.className = "reschedule-slot-list";
    el.setAttribute("data-appointment-reschedule-target", "list");
    body.appendChild(el);
  }

  // ===== Spinner & Empty notice =====
  toggleSpinner(on) {
    if (this.hasSpinnerTarget) this.spinnerTarget.classList.toggle("d-none", !on);
    if (this.hasHintTarget) this.hintTarget.textContent = on ? "Chargement des créneaux…" : "Sélectionnez un créneau ci-dessous.";
  }
  showEmpty(show) {
    if (this.hasEmptyNoticeTarget) this.emptyNoticeTarget.classList.toggle("d-none", !show);
  }

  // ===== Barrière ouvrable (cohérente avec calendar_controller.js) =====
  computeBusinessBarrier() {
    const tz = this.timeZoneValue || "Europe/Paris";
    const openDelay = Number.isFinite(this.openDelayHoursValue) ? this.openDelayHoursValue : 48;
    const openDays = this.openDaysValue.split(",").map(s => parseInt(s.trim(), 10))
      .filter(n => Number.isInteger(n) && n >= 1 && n <= 7);

    const sod = this.startOfDayInTz(new Date(), tz);
    let d = new Date(sod.getTime());
    let remaining = Math.ceil(openDelay / 24);

    while (remaining > 0) {
      const dow = ((d.getDay() + 6) % 7) + 1; // 1..7
      if (openDays.includes(dow)) {
        remaining--;
        if (remaining === 0) break;
      }
      d = new Date(d.getTime() + 86400000);
    }
    return new Date(d.getTime() + 86400000);
  }

  startOfDayInTz(date, timeZone) {
    const parts = new Intl.DateTimeFormat("fr-FR", {
      timeZone, year: "numeric", month: "2-digit", day: "2-digit",
    }).formatToParts(date).reduce((acc, p) => ((acc[p.type] = p.value), acc), {});
    return new Date(`${parts.year}-${parts.month}-${parts.day}T00:00:00`);
  }
}
