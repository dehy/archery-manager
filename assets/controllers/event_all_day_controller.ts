import { Controller } from '@hotwired/stimulus';

/**
 * Automatically adjusts start/end datetime fields when the "all day" checkbox
 * is toggled on an event form.
 *
 * When the checkbox is checked, the time portion of both `startsAt` and
 * `endsAt` is replaced: start is set to 00:00 and end to 23:59, while the
 * date portion is preserved.
 *
 * Targets:
 *   - `allDay`   (HTMLInputElement) — the "all day" checkbox
 *   - `startsAt` (HTMLInputElement) — the event start datetime-local input
 *   - `endsAt`   (HTMLInputElement) — the event end datetime-local input
 *
 * Usage:
 *   <div data-controller="event-all-day">
 *     <input type="checkbox" data-event-all-day-target="allDay"
 *            data-action="change->event-all-day#updateTimes">
 *     <input type="datetime-local" data-event-all-day-target="startsAt">
 *     <input type="datetime-local" data-event-all-day-target="endsAt">
 *   </div>
 */
export default class EventAllDayController extends Controller {
    static readonly targets = ['allDay', 'startsAt', 'endsAt'];

    declare readonly allDayTarget: HTMLInputElement;
    declare readonly startsAtTarget: HTMLInputElement;
    declare readonly endsAtTarget: HTMLInputElement;

    updateTimes(): void {
        if (!this.allDayTarget.checked) {
            return;
        }

        this.startsAtTarget.value = this.replaceTime(this.startsAtTarget.value, '00:00');
        this.endsAtTarget.value = this.replaceTime(this.endsAtTarget.value, '23:59');
    }

    private replaceTime(value: string, newTime: string): string {
        if (value.length < 10) {
            return value;
        }

        return `${value.substring(0, 10)}T${newTime}`;
    }
}
