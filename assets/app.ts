/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import "./observability"

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
  faBolt,
  faBuilding,
  faBullhorn,
  faBullseye,
  faCalendar,
  faCalendarCheck,
  faCalendarDays,
  faCalendarPlus,
  faCalendarWeek,
  faCalendarXmark,
  faChartBar,
  faCheck,
  faChevronRight,
  faCircleArrowRight,
  faCircleRight,
  faComment,
  faDownload,
  faEllipsis,
  faFloppyDisk,
  faGaugeHigh,
  faHeart,
  faHourglass,
  faIdBadge,
  faInfo,
  faInfoCircle,
  faMap,
  faMars,
  faPaperclip,
  faPencil,
  faPeopleGroup,
  faPlus,
  faQuestion,
  faScrewdriverWrench,
  faShare,
  faSquarePollVertical,
  faTimes,
  faUpload,
  faUser,
  faUserGear,
  faUserGroup,
  faUsers,
  faUserSecret,
  faUserXmark,
  faVenus,
  faVenusMars,
} from "@fortawesome/free-solid-svg-icons";
import {faApple, faDiscord, faGoogle, faWaze} from "@fortawesome/free-brands-svg-icons";
import {faFile} from "@fortawesome/free-regular-svg-icons";

import Chart from 'chart.js/auto';
import annotationPlugin from "chartjs-plugin-annotation";
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(annotationPlugin, ChartDataLabels);

library.add(
    faAddressCard,
    faAngleLeft,
    faAngleRight,
    faApple,
    faArrowLeft,
    faArrowRightFromBracket,
    faArrowUpRightFromSquare,
    faArrowsRotate,
    faBolt,
    faBuilding,
    faBullhorn,
    faBullseye,
    faCalendar,
    faCalendarCheck,
    faCalendarDays,
    faCalendarPlus,
    faCalendarWeek,
    faCalendarXmark,
    faChartBar,
    faCheck,
    faChevronRight,
    faCircleArrowRight,
    faCircleRight,
    faComment,
    faDiscord,
    faDownload,
    faEllipsis,
    faFile,
    faFloppyDisk,
    faGaugeHigh,
    faGoogle,
    faInfoCircle,
    faHeart,
    faHourglass,
    faIdBadge,
    faInfo,
    faMap,
    faMars,
    faPaperclip,
    faPencil,
    faPeopleGroup,
    faPlus,
    faQuestion,
    faShare,
    faSquarePollVertical,
    faScrewdriverWrench,
    faTimes,
    faUpload,
    faUser,
    faUserGear,
    faUserGroup,
    faUserSecret,
    faUsers,
    faUserXmark,
    faVenus,
    faVenusMars,
    faWaze
);
dom.watch();

(() => {
    const tooltipTriggerList = Array.prototype.slice.call(document.querySelectorAll(
        '[data-bs-toggle="tooltip"]'
    ));
    tooltipTriggerList.forEach(
        (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
    );

    const collections = [...document.querySelectorAll('.collection')];
    //formCollection(collections);
})();
