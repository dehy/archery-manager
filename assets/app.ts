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

import {dom, library} from "@fortawesome/fontawesome-svg-core";
import {
  faAddressCard,
  faAngleLeft,
  faAngleRight,
  faArrowLeft,
  faArrowRightFromBracket,
  faArrowsRotate,
  faArrowUpRightFromSquare,
  faBullhorn,
  faBullseye,
  faCalendar,
  faCalendarDays,
  faCheck,
  faCircleArrowRight,
  faCircleRight,
  faComment,
  faDownload,
  faGaugeHigh,
  faHeart,
  faHourglass,
  faInfo,
  faMap,
  faMars,
  faPaperclip,
  faPencil,
  faPeopleGroup,
  faPlus,
  faQuestion,
  faScrewdriverWrench,
  faSquarePollVertical,
  faTimes,
  faUser,
  faUserGear,
  faUsers,
  faUserSecret,
  faUserXmark,
  faVenus,
} from "@fortawesome/free-solid-svg-icons";
import {faApple, faDiscord, faGoogle, faWaze} from "@fortawesome/free-brands-svg-icons";
import {faFile} from "@fortawesome/free-regular-svg-icons";

library.add(
    faAddressCard,
    faAngleLeft,
    faAngleRight,
    faApple,
    faArrowLeft,
    faArrowRightFromBracket,
    faArrowUpRightFromSquare,
    faArrowsRotate,
    faBullhorn,
    faBullseye,
    faCalendar,
    faCalendarDays,
    faCheck,
    faCircleArrowRight,
    faCircleRight,
    faComment,
    faDiscord,
    faDownload,
    faFile,
    faGaugeHigh,
    faGoogle,
    faPeopleGroup,
    faHeart,
    faHourglass,
    faInfo,
    faMap,
    faMars,
    faPaperclip,
    faPencil,
    faPeopleGroup,
    faPlus,
    faQuestion,
    faSquarePollVertical,
    faScrewdriverWrench,
    faTimes,
    faUser,
    faUserGear,
    faUserSecret,
    faUsers,
    faUserXmark,
    faVenus,
    faWaze
);
dom.watch();

(() => {
    const tooltipTriggerList = Array.prototype.slice.call(document.querySelectorAll(
        '[data-bs-toggle="tooltip"]'
    ));
    const tooltipList = tooltipTriggerList.map(
        (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
    );
})();
