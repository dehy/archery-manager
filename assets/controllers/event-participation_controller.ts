import {ActionEvent, Controller} from "@hotwired/stimulus";
import api from "../api";
import axios from "axios";

export default class EventParticipationController extends Controller<HTMLElement> {
    eventId?: string;
    participationId?: string;
    licenseeId?: string;

    static readonly targets = ['stateButtons'];

    declare readonly stateButtonsTargets: HTMLButtonElement[];

    connect() {
        super.connect();
        this.eventId = `/api/events/${this.element.dataset.eventId}`;
        this.licenseeId = `/api/licensees/${this.element.dataset.licenseeId}`;
        this.participationId = this.element.dataset.participationId;

        if (this.participationId !== undefined && this.participationId !== "") {
            this.getEventParticipation(this.participationId);
        }
    }

    updateParticipationState(event: ActionEvent) {
        event.preventDefault();
        event.stopImmediatePropagation();

        const clickedElement = event.currentTarget as HTMLElement;
        const state = clickedElement.dataset.state;

        let url = `/api/event_participations`
        let body = {
            event: this.eventId,
            participant: this.licenseeId,
            participationState: state,
        }
        if (this.participationId) {
            url = `${url}/${this.participationId}`;
            api.update(url, body).then((response) => {
                this.updateUIFromEventParticipation(response.data);
            });
        } else {
            api.create(url, body).then((response) => {
                this.participationId = response.data.id.toString();
                this.updateUIFromEventParticipation(response.data);
            });
        }
    }

    updateUIFromEventParticipation = (eventParticipation: { [key: string]: string }) => {
        this.stateButtonsTargets.forEach((button) => {
            let badgeType: string | null = null;
            button.classList.forEach((className) => {
                if (className.startsWith('btn-')) {
                    const match = /^btn-(outline-)?(.*)$/.exec(className);
                    badgeType = match ? match[2] : null;
                }
            });
            console.debug(badgeType);
            if (badgeType !== null) {
                button.classList.remove(`btn-outline-${badgeType}`, `btn-${badgeType}`);
            }
            console.debug(eventParticipation.participationState, button.dataset.state);
            const outline = eventParticipation.participationState != button.dataset.state ? 'outline-' : '';
            button.classList.add(`btn-${outline}${badgeType}`);
        });
    }

    getEventParticipation = (eventId: string) => {
        axios.get(`/api/event_participations/${eventId}`).then((response) => {
            console.debug(response.data);
            this.updateUIFromEventParticipation(response.data);
        });
    }
}
