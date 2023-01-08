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

import {
  InjectCSS,
  MissingIconIndicator,
  ReplaceElements,
  register,
} from "@fortawesome/fontawesome-svg-core/plugins";

import { faAddressCard } from "@fortawesome/free-solid-svg-icons/faAddressCard";
import { faAngleLeft } from "@fortawesome/free-solid-svg-icons/faAngleLeft";
import { faAngleRight } from "@fortawesome/free-solid-svg-icons/faAngleRight";
import { faArrowLeft } from "@fortawesome/free-solid-svg-icons/faArrowLeft";
import { faArrowRightFromBracket } from "@fortawesome/free-solid-svg-icons/faArrowRightFromBracket";
import { faArrowsRotate } from "@fortawesome/free-solid-svg-icons/faArrowsRotate";
import { faArrowUpRightFromSquare } from "@fortawesome/free-solid-svg-icons/faArrowUpRightFromSquare";
import { faBullhorn } from "@fortawesome/free-solid-svg-icons/faBullhorn";
import { faBullseye } from "@fortawesome/free-solid-svg-icons/faBullseye";
import { faCalendar } from "@fortawesome/free-solid-svg-icons/faCalendar";
import { faCalendarCheck } from "@fortawesome/free-solid-svg-icons/faCalendarCheck";
import { faCalendarDays } from "@fortawesome/free-solid-svg-icons/faCalendarDays";
import { faCalendarPlus } from "@fortawesome/free-solid-svg-icons/faCalendarPlus";
import { faCalendarXmark } from "@fortawesome/free-solid-svg-icons/faCalendarXmark";
import { faCheck } from "@fortawesome/free-solid-svg-icons/faCheck";
import { faChevronRight } from "@fortawesome/free-solid-svg-icons/faChevronRight";
import { faCircleArrowRight } from "@fortawesome/free-solid-svg-icons/faCircleArrowRight";
import { faCircleRight } from "@fortawesome/free-solid-svg-icons/faCircleRight";
import { faComment } from "@fortawesome/free-solid-svg-icons/faComment";
import { faDownload } from "@fortawesome/free-solid-svg-icons/faDownload";
import { faEllipsis } from "@fortawesome/free-solid-svg-icons/faEllipsis";
import { faEye } from "@fortawesome/free-solid-svg-icons/faEye";
import { faGaugeHigh } from "@fortawesome/free-solid-svg-icons/faGaugeHigh";
import { faHeart } from "@fortawesome/free-solid-svg-icons/faHeart";
import { faHourglass } from "@fortawesome/free-solid-svg-icons/faHourglass";
import { faInfo } from "@fortawesome/free-solid-svg-icons/faInfo";
import { faInfoCircle } from "@fortawesome/free-solid-svg-icons/faInfoCircle";
import { faMap } from "@fortawesome/free-solid-svg-icons/faMap";
import { faMars } from "@fortawesome/free-solid-svg-icons/faMars";
import { faPaperclip } from "@fortawesome/free-solid-svg-icons/faPaperclip";
import { faPencil } from "@fortawesome/free-solid-svg-icons/faPencil";
import { faPeopleGroup } from "@fortawesome/free-solid-svg-icons/faPeopleGroup";
import { faPlus } from "@fortawesome/free-solid-svg-icons/faPlus";
import { faQuestion } from "@fortawesome/free-solid-svg-icons/faQuestion";
import { faScrewdriverWrench } from "@fortawesome/free-solid-svg-icons/faScrewdriverWrench";
import { faShare } from "@fortawesome/free-solid-svg-icons/faShare";
import { faSquarePollVertical } from "@fortawesome/free-solid-svg-icons/faSquarePollVertical";
import { faTimes } from "@fortawesome/free-solid-svg-icons/faTimes";
import { faUser } from "@fortawesome/free-solid-svg-icons/faUser";
import { faUserGear } from "@fortawesome/free-solid-svg-icons/faUserGear";
import { faUserGroup } from "@fortawesome/free-solid-svg-icons/faUserGroup";
import { faUsers } from "@fortawesome/free-solid-svg-icons/faUsers";
import { faUserSecret } from "@fortawesome/free-solid-svg-icons/faUserSecret";
import { faUserXmark } from "@fortawesome/free-solid-svg-icons/faUserXmark";
import { faVenus } from "@fortawesome/free-solid-svg-icons/faVenus";
import { faVenusMars } from "@fortawesome/free-solid-svg-icons/faVenusMars";

import { faApple } from "@fortawesome/free-brands-svg-icons/faApple";
import { faDiscord } from "@fortawesome/free-brands-svg-icons/faDiscord";
import { faGoogle } from "@fortawesome/free-brands-svg-icons/faGoogle";
import { faWaze } from "@fortawesome/free-brands-svg-icons/faWaze";

import { faFile } from "@fortawesome/free-regular-svg-icons/faFile";

const api = register([InjectCSS, ReplaceElements, MissingIconIndicator]);

api.library.add(
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
    faCalendarCheck,
    faCalendarDays,
    faCalendarPlus,
    faCalendarXmark,
    faCheck,
    faChevronRight,
    faCircleArrowRight,
    faCircleRight,
    faComment,
    faDiscord,
    faDownload,
    faEye,
    faEllipsis,
    faFile,
    faGaugeHigh,
    faGoogle,
    faInfoCircle,
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
    faShare,
    faSquarePollVertical,
    faScrewdriverWrench,
    faTimes,
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


(() => {
    api.dom.i2svg();
    api.dom.watch();

    const tooltipTriggerList = Array.prototype.slice.call(document.querySelectorAll(
        '[data-bs-toggle="tooltip"]'
    ));
    tooltipTriggerList.forEach(
        (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
    );
})();
