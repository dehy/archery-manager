/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import "./styles/app.scss";

// start the Stimulus application
import "./bootstrap";

// Bootstrap Javascript
import * as bootstrap from "bootstrap";

import { library, dom } from "@fortawesome/fontawesome-svg-core";
import {
  faAddressCard,
  faArrowLeft,
  faArrowRightFromBracket,
  faArrowsRotate,
  faBullseye,
  faCalendar,
  faCalendarDays,
  faCheck,
  faComment,
  faGaugeHigh,
  faMars,
  faPeopleGroup,
  faPlus,
  faScrewdriverWrench,
  faTimes,
  faUser,
  faUserGear,
  faUsers,
  faVenus,
} from "@fortawesome/free-solid-svg-icons";
import { faDiscord } from "@fortawesome/free-brands-svg-icons";

library.add(
  faAddressCard,
  faArrowLeft,
  faArrowRightFromBracket,
  faArrowsRotate,
  faBullseye,
  faCalendar,
  faCalendarDays,
  faCheck,
  faComment,
  faDiscord,
  faGaugeHigh,
  faMars,
  faPeopleGroup,
  faPlus,
  faScrewdriverWrench,
  faTimes,
  faUser,
  faUsers,
  faUserGear,
  faVenus
);
dom.watch();

(() => {
  const tooltipTriggerList = document.querySelectorAll(
    '[data-bs-toggle="tooltip"]'
  );
  const tooltipList = [...tooltipTriggerList].map(
    (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
  );
})();
