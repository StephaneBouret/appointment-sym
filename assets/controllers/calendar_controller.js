import { Controller } from "@hotwired/stimulus";

// ===== Helpers =====
function isPast(date) {
    const t = typeof date === "string" ? new Date(date) : date;
    return t.getTime() <= Date.now();
}
function fmtDateFr(d, timeZone = "Europe/Paris") {
    return new Intl.DateTimeFormat("fr-FR", {
        timeZone,
        weekday: "long",
        day: "2-digit",
        month: "long",
        year: "numeric",
    }).format(d);
}
function fmtTimeFr(d, timeZone = "Europe/Paris") {
    return new Intl.DateTimeFormat("fr-FR", {
        timeZone,
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    }).format(d);
}
// Ex: "2025-10-15T09:00:00+02:00" -> "2025-10-15T09:00" (pour <input type="datetime-local">)
function isoTzToLocalInputValue(isoTz) {
    return String(isoTz)
        .replace(/([+-]\d{2}:\d{2}|Z)$/, "")
        .slice(0, 16);
}

export default class extends Controller {
    static values = {
        endpoint: String, // ex: "/api/fixed-slots-range"
        typeId: Number, // ex: 12
        startInputSelector: { type: String, default: "#appointment_startAt" },
        initialView: { type: String, default: "timeGridWeek" },
        slotMinTime: { type: String, default: "08:00:00" },
        slotMaxTime: { type: String, default: "20:00:00" },
        timeZone: { type: String, default: "Europe/Paris" },
        firstDay: { type: Number, default: 1 },
        // Admin
        openDelayHours: { type: Number, default: 48 }, // "opening_delay_hours"
        openDays: { type: String, default: "1,2,3,4,5" }, // "1=lundi … 7=dimanche"
    };

    // ====== Jours ouverts ======
    parseOpenDays(csv) {
        return csv
            .split(",")
            .map((s) => parseInt(s.trim(), 10))
            .filter((n) => Number.isInteger(n) && n >= 1 && n <= 7);
    }
    // FullCalendar: 0=dim..6=sam ; Notre config: 1=lun..7=dim
    openDaysToHiddenDays(openDays) {
        const keepFc = new Set(openDays.map((n) => (n === 7 ? 0 : n)));
        const all = new Set([0, 1, 2, 3, 4, 5, 6]);
        keepFc.forEach((k) => all.delete(k));
        return Array.from(all); // ex: [0,6] pour cacher dim & sam
    }

    // ====== Barrière ouvrable (corrigée) ======
    computeBusinessBarrier(timeZone, openDelayHours, openDays) {
        const now = new Date();
        const sod = this.startOfDayInTz(now, timeZone);
        const daysToAdd = Math.ceil(openDelayHours / 24);

        let d = new Date(sod.getTime());
        let remaining = daysToAdd;

        // 🟢 On compte aussi le jour courant s'il est ouvré
        while (remaining > 0) {
            const dow = ((d.getDay() + 6) % 7) + 1; // 1=lun..7=dim
            if (openDays.includes(dow)) {
                remaining--;
                if (remaining === 0) break; // on s'arrête sur le Nᵉ jour ouvré
            }
            d = this.addDays(d, 1);
        }

        // Ouverture = lendemain du Nᵉ jour ouvré consommé
        d = this.addDays(d, 1);
        return d;
    }

    startOfDayInTz(date, timeZone) {
        const parts = new Intl.DateTimeFormat("fr-FR", {
            timeZone,
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
        })
            .formatToParts(date)
            .reduce((acc, p) => ((acc[p.type] = p.value), acc), {});
        return new Date(`${parts.year}-${parts.month}-${parts.day}T00:00:00`);
    }

    addDays(d, n) {
        return new Date(d.getTime() + n * 24 * 60 * 60 * 1000);
    }

    // ====== Sélection visuelle (réinitialisation propre) ======
    resetSelectedEventAppearance() {
        if (this.selectedEvent) {
            const ep = this.selectedEvent.extendedProps || {};
            this.selectedEvent.setProp(
                "backgroundColor",
                ep._defaultBg || "#d1e7dd"
            );
            this.selectedEvent.setProp(
                "borderColor",
                ep._defaultBorder || "#198754"
            );
            this.selectedEvent.setProp(
                "textColor",
                ep._defaultText || "#0f5132"
            );
            this.selectedEvent = null;
        }
    }

    connect() {
        const fc = window.FullCalendar;

        // Récupère l'input hidden "startAt"
        this.startInput =
            document.querySelector(this.startInputSelectorValue) ||
            document.querySelector("[name$='[startAt]']") ||
            document.querySelector("[id$='_startAt']");
        if (!this.startInput) {
            console.warn("[calendar] Champ startAt introuvable.");
        }

        this.selectedEvent = null;
        this.loadingEl = this.ensureLoadingEl();

        // ===== Calculs init =====
        this.tz = this.timeZoneValue || "Europe/Paris";
        const openDelay = Number.isFinite(this.openDelayHoursValue)
            ? this.openDelayHoursValue
            : 48;

        // Jours ouverts
        this.openDaysList = this.parseOpenDays(this.openDaysValue);
        this.hiddenDays = this.openDaysToHiddenDays(this.openDaysList);

        // Barrière ouvrable
        this.barrierStart = this.computeBusinessBarrier(
            this.tz,
            openDelay,
            this.openDaysList
        );

        // ===== Initialisation FullCalendar =====
        this.calendar = new fc.Calendar(this.element, {
            themeSystem: "bootstrap5",
            locale: "fr",
            initialView: this.initialViewValue,
            firstDay: this.firstDayValue,
            timeZone: this.tz,
            slotMinTime: this.slotMinTimeValue,
            slotMaxTime: this.slotMaxTimeValue,
            nowIndicator: true,
            selectable: false,
            allDaySlot: false, // ⬅️ Masque la ligne "Toute la journée"
            stickyHeaderDates: true,
            expandRows: true,
            height: "auto",
            slotDuration: "00:30:00",
            slotLabelFormat: {
                hour: "2-digit",
                minute: "2-digit",
                hour12: false,
            },
            eventTimeFormat: {
                hour: "2-digit",
                minute: "2-digit",
                hour12: false,
            },
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "timeGridWeek",
            },

            hiddenDays: this.hiddenDays,
            validRange: { start: this.barrierStart },

            events: (info, success, failure) =>
                this.loadRangeClamped(info)
                    .then((events) => {
                        const filtered = events.filter(
                            (e) =>
                                new Date(e.start) >= this.barrierStart &&
                                !isPast(e.end || e.start)
                        );
                        this.toggleEmptyNotice(filtered.length === 0);
                        this.renderList(filtered);
                        success(filtered);
                    })
                    .catch((e) => {
                        console.error("[calendar] events load error", e);
                        this.toggleEmptyNotice(true);
                        this.renderList([]);
                        failure(e);
                    }),

            eventClick: (arg) => this.onEventClick(arg),
            eventMouseEnter: (info) => {
                info.el.style.cursor = "pointer";
            },
        });

        this.calendar.render();

        // UI en dehors du conteneur FC
        this.ensureEmptyNoticeEl();
        this.ensureListEl();
        this.ensureSelectedNoticeEl();
    }

    disconnect() {
        if (this.calendar) this.calendar.destroy();
        if (this.listEl)
            this.listEl.removeEventListener("click", this.onListClickBound);
    }

    // ===== Chargement par range (avec clamp barrière) =====
    async loadRangeClamped(info) {
        this.setLoading(true);

        const start =
            info.start < this.barrierStart ? this.barrierStart : info.start;
        const end = info.end;

        const url = new URL(this.endpointValue, window.location.origin);
        url.searchParams.set("type", String(this.typeIdValue));
        const ymd = (d) => d.toISOString().slice(0, 10);
        url.searchParams.set("start", ymd(start));
        url.searchParams.set("end", ymd(end));

        try {
            const resp = await fetch(url.toString(), {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (!resp.ok) return [];

            const data = await resp.json(); // [{start,end}] (RFC3339 avec fuseau)
            // On conserve les ISO TZ-AWARE tel quels, et on pose les couleurs
           return data.map((e) => {
                const past = isPast(e.end || e.start);
                return {
                    ...e,
                    display: "block",
                    backgroundColor: past ? "#e9ecef" : "#d1e7dd",
                    borderColor: past ? "#ced4da" : "#198754",
                    textColor: past ? "#6c757d" : "#0f5132",
                    extendedProps: {
                        disabled: past,
                        _defaultBg: past ? "#e9ecef" : "#d1e7dd",
                        _defaultBorder: past ? "#ced4da" : "#198754",
                        _defaultText: past ? "#6c757d" : "#0f5132",
                    },
                };
            });
        } finally {
            this.setLoading(false);
        }
    }

    // ===== Clic sur un créneau (depuis le calendrier) =====
    onEventClick(arg) {
        if (
            arg.event.extendedProps?.disabled ||
            isPast(arg.event.end || arg.event.start)
        )
            return;
        if (!this.startInput) return;

        // startStr / endStr sont des ISO avec fuseau selon la timeZone du calendrier
        const startIsoTz = arg.event.startStr;
        const endIsoTz =
            arg.event.endStr || arg.event.end?.toISOString() || startIsoTz;

        // Remplir l'input en local (on retire juste le fuseau)
        this.startInput.value = isoTzToLocalInputValue(startIsoTz);
        this.startInput.dispatchEvent(new Event("input", { bubbles: true }));
        this.startInput.dispatchEvent(new Event("change", { bubbles: true }));

        // Réinitialise l'ancien éventuel puis colore le nouveau
        this.resetSelectedEventAppearance();
        arg.event.setProp("backgroundColor", "#cfe2ff");
        arg.event.setProp("borderColor", "#0d6efd");
        arg.event.setProp("textColor", "#084298");
        this.selectedEvent = arg.event;

        // Bannière "créneau sélectionné" (en format Europe/Paris)
        this.showSelectedNotice(startIsoTz, endIsoTz);

        // Feedback dans la liste (actif)
        this.highlightListItem(startIsoTz);
    }

    // ===== UI: Loader =====
    ensureLoadingEl() {
        let el = this.element.querySelector(".fc-loading-indicator");
        if (!el) {
            el = document.createElement("div");
            el.className = "fc-loading-indicator";
            el.style.cssText =
                "position:absolute;top:8px;right:8px;z-index:5;padding:.25rem .5rem;border-radius:.25rem;background:#f8f9fa;border:1px solid #dee2e6;font-size:.85rem;display:none;";
            el.textContent = "Chargement des créneaux…";
            if (!this.element.style.position)
                this.element.style.position = "relative";
            this.element.appendChild(el);
        }
        return el;
    }
    setLoading(isLoading) {
        if (this.loadingEl)
            this.loadingEl.style.display = isLoading ? "block" : "none";
    }

    // ===== UI: Alerte "aucun créneau" =====
    ensureEmptyNoticeEl() {
        let box = this.element.nextElementSibling;
        if (box && box.classList.contains("fc-empty-notice")) {
            this.emptyNoticeEl = box;
            return box;
        }
        box = document.createElement("div");
        box.className = "fc-empty-notice alert alert-warning d-none";
        box.style.cssText = "margin:.5rem 0;";
        const barrierLabel = this.barrierStart.toLocaleDateString("fr-FR");
        box.innerHTML = `
      <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
        <div><strong>Aucun créneau visible avant ${barrierLabel}.</strong></div>
        <div class="text-muted">Essayez une autre semaine.</div>
        <div class="ms-sm-auto">
          <button type="button" class="btn btn-sm btn-primary fc-next-week">Voir la semaine suivante</button>
        </div>
      </div>
    `;
        if (this.element.parentNode) {
            this.element.parentNode.insertBefore(box, this.element.nextSibling);
        } else {
            document.body.appendChild(box);
        }
        const btn = box.querySelector(".fc-next-week");
        btn?.addEventListener("click", () => this.calendar.next());
        this.emptyNoticeEl = box;
        return box;
    }
    toggleEmptyNotice(show) {
        if (this.emptyNoticeEl)
            this.emptyNoticeEl.classList.toggle("d-none", !show);
    }

    // ===== UI: Liste des créneaux restants =====
    ensureListEl() {
        let after = this.emptyNoticeEl || this.element;
        let el = after.nextElementSibling;
        if (!(el && el.classList && el.classList.contains("fc-slot-list"))) {
            el = document.createElement("div");
            el.className = "fc-slot-list mt-2";
            el.innerHTML = `
        <h6 class="mb-2">Créneaux restants</h6>
        <div class="fc-slot-list-body"></div>
      `;
            if (after.parentNode)
                after.parentNode.insertBefore(el, after.nextSibling);
            else document.body.appendChild(el);
        }
        this.listEl = el;
        this.listBodyEl = el.querySelector(".fc-slot-list-body");

        this.onListClickBound = this.onListClick.bind(this);
        this.listEl.addEventListener("click", this.onListClickBound);

        return el;
    }

    renderList(events) {
        if (!this.listBodyEl) return;

        const avail = events
            .filter(
                (e) =>
                    new Date(e.start) >= this.barrierStart &&
                    !isPast(e.end || e.start)
            )
            .sort((a, b) => new Date(a.start) - new Date(b.start));

        if (avail.length === 0) {
            this.listBodyEl.innerHTML = `<div class="text-muted small">Aucun créneau sur cette période.</div>`;
            return;
        }

        const byDay = new Map();
        for (const e of avail) {
            const d = new Date(e.start);
            const key = d.toISOString().slice(0, 10); // YYYY-MM-DD (ok pour regrouper)
            if (!byDay.has(key)) byDay.set(key, []);
            byDay.get(key).push(e);
        }

        const chunks = [];
        for (const [day, evts] of byDay.entries()) {
            const d = new Date(evts[0].start);
            const dayLabel = fmtDateFr(d, this.tz);
            const items = evts
                .map((e) => {
                    // e.start / e.end proviennent de l'API (RFC3339 avec fuseau)
                    const startIsoTz = e.start;
                    const endIsoTz = e.end;
                    const s = new Date(startIsoTz);
                    const en = new Date(endIsoTz);
                    const label = `${fmtTimeFr(s, this.tz)} – ${fmtTimeFr(
                        en,
                        this.tz
                    )}`;
                    return `<button type="button"
              class="btn btn-outline-success btn-sm me-2 mb-2 fc-slot-btn"
              data-start-tz="${startIsoTz}"
              data-end-tz="${endIsoTz}"
            >${label}</button>`;
                })
                .join("");

            chunks.push(`
        <div class="card mb-2">
          <div class="card-body py-2">
            <div class="fw-semibold mb-2">${dayLabel}</div>
            <div class="d-flex flex-wrap">${items}</div>
          </div>
        </div>
      `);
        }

        this.listBodyEl.innerHTML = chunks.join("");
        this.syncListHighlight();
    }

    onListClick(e) {
        const btn = e.target.closest(".fc-slot-btn");
        if (!btn) return;

        const startIsoTz = btn.getAttribute("data-start-tz");
        const endIsoTz = btn.getAttribute("data-end-tz");

        // Toujours nettoyer l'ancienne sélection visuelle AVANT de changer
        this.resetSelectedEventAppearance();

        // 1) Essaie de retrouver l'event dans FC par l'instant (tolérance 1 min)
        const targetMs = new Date(startIsoTz).getTime();
        const candidates = this.calendar
            .getEvents()
            .filter((ev) => ev.start && ev.end);
        let match = null;
        for (const ev of candidates) {
            if (Math.abs(ev.start.getTime() - targetMs) < 60000) {
                match = ev;
                break;
            }
        }

        if (match) {
            this.onEventClick({ event: match }); // se charge de tout (input + bannière + bleu)
            this.highlightListItem(startIsoTz);
            return;
        }

        // 2) Fallback : remplir l'input directement depuis la liste (calendrier masqué/pas de match)
        if (this.startInput) {
            this.startInput.value = isoTzToLocalInputValue(startIsoTz);
            this.startInput.dispatchEvent(
                new Event("input", { bubbles: true })
            );
            this.startInput.dispatchEvent(
                new Event("change", { bubbles: true })
            );
        }
        this.selectedEvent = null;
        this.highlightListItem(startIsoTz);

        // Bannière "créneau sélectionné"
        this.showSelectedNotice(startIsoTz, endIsoTz);
    }

    // Surbrillance dans la liste pour le start sélectionné
    highlightListItem(startIsoTz) {
        if (!this.listBodyEl) return;
        const targetMs = new Date(startIsoTz).getTime();
        this.listBodyEl.querySelectorAll(".fc-slot-btn").forEach((btn) => {
            const ms = new Date(btn.getAttribute("data-start-tz")).getTime();
            btn.classList.toggle(
                "btn-primary",
                Math.abs(ms - targetMs) < 60000
            );
            btn.classList.toggle(
                "btn-outline-success",
                Math.abs(ms - targetMs) >= 60000
            );
        });
    }

    // Si on revient sur une vue déjà sélectionnée, garde la cohérence visuelle
    syncListHighlight() {
        if (!this.selectedEvent) return;
        this.highlightListItem(
            this.selectedEvent.startStr ||
                this.selectedEvent.start.toISOString()
        );
    }

        // ===== UI: Bannière "créneau sélectionné" =====
    ensureSelectedNoticeEl() {
        let after = this.listEl || this.emptyNoticeEl || this.element;
        let el = after.nextElementSibling;
        if (
            !(el && el.classList && el.classList.contains("fc-selected-notice"))
        ) {
            el = document.createElement("div");
            el.className = "fc-selected-notice alert alert-info d-none mt-2";
            el.innerHTML = `
        <div class="d-flex align-items-center gap-2">
          <div class="fw-semibold">Créneau sélectionné :</div>
          <div class="fc-selected-text"></div>
        </div>
      `;
            if (after.parentNode)
                after.parentNode.insertBefore(el, after.nextSibling);
            else document.body.appendChild(el);
        }
        this.selectedNoticeEl = el;
        this.selectedNoticeTextEl = el.querySelector(".fc-selected-text");
        return el;
    }
    showSelectedNotice(startIsoTz, endIsoTz) {
        if (!this.selectedNoticeEl) this.ensureSelectedNoticeEl();
        const s = new Date(startIsoTz);
        const e = new Date(endIsoTz);
        const day = fmtDateFr(s, this.tz);
        const range = `${fmtTimeFr(s, this.tz)} – ${fmtTimeFr(e, this.tz)}`;
        this.selectedNoticeTextEl.textContent = `${day} • ${range}`;
        this.selectedNoticeEl.classList.remove("d-none");
    }
    clearSelectedNotice() {
        this.selectedNoticeEl?.classList.add("d-none");
    }

    // ===== UI: Loader & Empty Notice =====
    ensureLoadingEl() {
        let el = this.element.querySelector(".fc-loading-indicator");
        if (!el) {
            el = document.createElement("div");
            el.className = "fc-loading-indicator";
            el.style.cssText =
                "position:absolute;top:8px;right:8px;z-index:5;padding:.25rem .5rem;border-radius:.25rem;background:#f8f9fa;border:1px solid #dee2e6;font-size:.85rem;display:none;";
            el.textContent = "Chargement des créneaux…";
            if (!this.element.style.position)
                this.element.style.position = "relative";
            this.element.appendChild(el);
        }
        return el;
    }
    setLoading(isLoading) {
        if (this.loadingEl)
            this.loadingEl.style.display = isLoading ? "block" : "none";
    }

    ensureEmptyNoticeEl() {
        let box = this.element.nextElementSibling;
        if (box && box.classList.contains("fc-empty-notice")) {
            this.emptyNoticeEl = box;
            return box;
        }
        box = document.createElement("div");
        box.className = "fc-empty-notice alert alert-warning d-none";
        box.style.cssText = "margin:.5rem 0;";
        const barrierLabel = this.barrierStart.toLocaleDateString("fr-FR");
        box.innerHTML = `
      <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
        <div><strong>Aucun créneau visible avant ${barrierLabel}.</strong></div>
        <div class="text-muted">Essayez une autre semaine.</div>
        <div class="ms-sm-auto">
          <button type="button" class="btn btn-sm btn-primary fc-next-week">Voir la semaine suivante</button>
        </div>
      </div>
    `;
        if (this.element.parentNode) {
            this.element.parentNode.insertBefore(box, this.element.nextSibling);
        } else {
            document.body.appendChild(box);
        }
        const btn = box.querySelector(".fc-next-week");
        btn?.addEventListener("click", () => this.calendar.next());
        this.emptyNoticeEl = box;
        return box;
    }
    toggleEmptyNotice(show) {
        if (this.emptyNoticeEl)
            this.emptyNoticeEl.classList.toggle("d-none", !show);
    }
}
