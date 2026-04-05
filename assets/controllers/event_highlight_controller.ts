import { Controller } from "@hotwired/stimulus";

/**
 * Synchronises hover highlights between the event list and the calendar grid
 * on the homepage/dashboard view.
 *
 * When the user hovers over a list item, the matching calendar event gains the
 * `highlight` CSS class, and vice-versa. Both directions work independently.
 *
 * Matching is done via `data-event-id` attributes; an event can appear more
 * than once in the calendar (e.g. multi-day), so the controller handles
 * multiple calendar elements sharing the same ID.
 *
 * Targets:
 *   - `listItem`      (HTMLElement[]) — rows in the upcoming-events list
 *   - `calendarEvent` (HTMLElement[]) — cells in the calendar grid
 *
 * Bound handler references are stored as instance properties so that the same
 * function objects can be passed to both `addEventListener` and
 * `removeEventListener`, ensuring proper cleanup on `disconnect`.
 *
 * Usage:
 *   <div data-controller="event-highlight">
 *     <!-- list -->
 *     <div class="event-list-item"
 *          data-event-highlight-target="listItem"
 *          data-event-id="42">…</div>
 *     <!-- calendar -->
 *     <div data-event-highlight-target="calendarEvent"
 *          data-event-id="42">…</div>
 *   </div>
 */
export default class EventHighlightController extends Controller {
    static readonly targets = ["listItem", "calendarEvent"];

    declare readonly listItemTargets: HTMLElement[];
    declare readonly calendarEventTargets: HTMLElement[];

    // Store bound functions to ensure proper cleanup
    private boundHighlightCalendarEvent!: (event: Event) => void;
    private boundUnhighlightCalendarEvent!: (event: Event) => void;
    private boundHighlightListEvent!: (event: Event) => void;
    private boundUnhighlightListEvent!: (event: Event) => void;

    connect() {
        // Bind functions once and store references
        this.boundHighlightCalendarEvent = this.highlightCalendarEvent.bind(this);
        this.boundUnhighlightCalendarEvent = this.unhighlightCalendarEvent.bind(this);
        this.boundHighlightListEvent = this.highlightListEvent.bind(this);
        this.boundUnhighlightListEvent = this.unhighlightListEvent.bind(this);

        // Add hover listeners to list items
        this.listItemTargets.forEach((listItem) => {
            listItem.addEventListener("mouseenter", this.boundHighlightCalendarEvent);
            listItem.addEventListener("mouseleave", this.boundUnhighlightCalendarEvent);
        });

        // Add hover listeners to calendar events
        this.calendarEventTargets.forEach((calendarEvent) => {
            calendarEvent.addEventListener("mouseenter", this.boundHighlightListEvent);
            calendarEvent.addEventListener("mouseleave", this.boundUnhighlightListEvent);
        });
    }

    disconnect() {
        // Clean up listeners using stored bound functions
        this.listItemTargets.forEach((listItem) => {
            listItem.removeEventListener("mouseenter", this.boundHighlightCalendarEvent);
            listItem.removeEventListener("mouseleave", this.boundUnhighlightCalendarEvent);
        });

        this.calendarEventTargets.forEach((calendarEvent) => {
            calendarEvent.removeEventListener("mouseenter", this.boundHighlightListEvent);
            calendarEvent.removeEventListener("mouseleave", this.boundUnhighlightListEvent);
        });
    }

    highlightCalendarEvent(event: Event) {
        const target = event.currentTarget as HTMLElement;
        const eventId = target.dataset.eventId;

        if (eventId) {
            // Find and highlight all calendar events with this ID
            const calendarEvents = document.querySelectorAll(
                `.calendar-case [data-event-id="${eventId}"]`
            );
            calendarEvents.forEach((el) => el.classList.add("highlight"));
        }
    }

    unhighlightCalendarEvent(event: Event) {
        const target = event.currentTarget as HTMLElement;
        const eventId = target.dataset.eventId;

        if (eventId) {
            // Remove highlight from calendar events
            const calendarEvents = document.querySelectorAll(
                `.calendar-case [data-event-id="${eventId}"]`
            );
            calendarEvents.forEach((el) => el.classList.remove("highlight"));
        }
    }

    highlightListEvent(event: Event) {
        const target = event.currentTarget as HTMLElement;
        const eventId = target.dataset.eventId;

        if (eventId) {
            // Find and highlight the list event
            const listEvent = document.querySelector(
                `.event-list-item[data-event-id="${eventId}"]`
            );
            if (listEvent) {
                listEvent.closest(".event-list-event")?.classList.add("highlight");
            }
        }
    }

    unhighlightListEvent(event: Event) {
        const target = event.currentTarget as HTMLElement;
        const eventId = target.dataset.eventId;

        if (eventId) {
            // Remove highlight from list event
            const listEvent = document.querySelector(
                `.event-list-item[data-event-id="${eventId}"]`
            );
            if (listEvent) {
                listEvent.closest(".event-list-event")?.classList.remove("highlight");
            }
        }
    }
}
