import { Controller } from '@hotwired/stimulus';

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
