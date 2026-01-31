import "./styles/unauthenticated.scss";

import { library, dom } from "@fortawesome/fontawesome-svg-core";
import {
  faArrowLeft,
  faArrowRightToBracket,
  faHeart,
} from "@fortawesome/pro-solid-svg-icons";

library.add(faArrowLeft, faArrowRightToBracket, faHeart);
dom.watch();
