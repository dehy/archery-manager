import { Controller } from "@hotwired/stimulus";

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
