import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["listItem", "calendarEvent"];

    declare readonly listItemTargets: HTMLElement[];
    declare readonly calendarEventTargets: HTMLElement[];

    connect() {
        // Add hover listeners to list items
        this.listItemTargets.forEach((listItem) => {
            listItem.addEventListener("mouseenter", this.highlightCalendarEvent.bind(this));
            listItem.addEventListener("mouseleave", this.unhighlightCalendarEvent.bind(this));
        });

        // Add hover listeners to calendar events
        this.calendarEventTargets.forEach((calendarEvent) => {
            calendarEvent.addEventListener("mouseenter", this.highlightListEvent.bind(this));
            calendarEvent.addEventListener("mouseleave", this.unhighlightListEvent.bind(this));
        });
    }

    disconnect() {
        // Clean up listeners
        this.listItemTargets.forEach((listItem) => {
            listItem.removeEventListener("mouseenter", this.highlightCalendarEvent.bind(this));
            listItem.removeEventListener("mouseleave", this.unhighlightCalendarEvent.bind(this));
        });

        this.calendarEventTargets.forEach((calendarEvent) => {
            calendarEvent.removeEventListener("mouseenter", this.highlightListEvent.bind(this));
            calendarEvent.removeEventListener("mouseleave", this.unhighlightListEvent.bind(this));
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
