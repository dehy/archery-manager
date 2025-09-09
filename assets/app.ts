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
  faCheckCircle,
  faChevronLeft,
  faChevronRight,
  faCircleArrowRight,
  faCircleRight,
  faClock,
  faCog,
  faComment,
  faCrosshairs,
  faDoorClosed,
  faDoorOpen,
  faDownload,
  faDumbbell,
  faEdit,
  faEllipsis,
  faEllipsisVertical,
  faExclamationTriangle,
  faExternalLinkAlt,
  faEye,
  faFileContract,
  faFloppyDisk,
  faGaugeHigh,
  faHeart,
  faHistory,
  faHourglass,
  faIdBadge,
  faInfo,
  faInfoCircle,
  faLightbulb,
  faList,
  faMap,
  faMapMarkerAlt,
  faMars,
  faMinus,
  faPaperclip,
  faPencil,
  faPeopleGroup,
  faPlus,
  faPlusCircle,
  faQuestion,
  faQuestionCircle,
  faSave,
  faScrewdriverWrench,
  faShare,
  faSquarePollVertical,
  faStar,
  faTag,
  faTimes,
  faTrash,
  faTrophy,
  faUpload,
  faUser,
  faUserCheck,
  faUserGear,
  faUserGroup,
  faUserPlus,
  faUsers,
  faUsersGear,
  faUserSecret,
  faUserSlash,
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
    faCheckCircle,
    faChevronLeft,
    faChevronRight,
    faCircleArrowRight,
    faCircleRight,
    faClock,
    faCog,
    faComment,
    faCrosshairs,
    faDiscord,
    faDoorClosed,
    faDoorOpen,
    faDownload,
    faDumbbell,
    faEdit,
    faEllipsis,
    faEllipsisVertical,
    faExclamationTriangle,
    faExternalLinkAlt,
    faEye,
    faFile,
    faFileContract,
    faFloppyDisk,
    faGaugeHigh,
    faGoogle,
    faHeart,
    faHistory,
    faHourglass,
    faIdBadge,
    faInfo,
    faInfoCircle,
    faLightbulb,
    faList,
    faMap,
    faMapMarkerAlt,
    faMars,
    faMinus,
    faPaperclip,
    faPencil,
    faPeopleGroup,
    faPlus,
    faPlusCircle,
    faQuestion,
    faQuestionCircle,
    faSave,
    faScrewdriverWrench,
    faShare,
    faSquarePollVertical,
    faStar,
    faTag,
    faTimes,
    faTrash,
    faTrophy,
    faUpload,
    faUser,
    faUserCheck,
    faUserGear,
    faUserGroup,
    faUserPlus,
    faUserSecret,
    faUserSlash,
    faUsers,
    faUsersGear,
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
