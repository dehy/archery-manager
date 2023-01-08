import "./styles/unauthenticated.scss";

import {
  InjectCSS,
  MissingIconIndicator,
  ReplaceElements,
  register,
} from "@fortawesome/fontawesome-svg-core/plugins";

const api = register([InjectCSS, ReplaceElements, MissingIconIndicator]);

import { faArrowLeft } from '@fortawesome/free-solid-svg-icons/faArrowLeft';
import { faArrowRightToBracket } from '@fortawesome/free-solid-svg-icons/faArrowRightToBracket';
import { faHeart } from '@fortawesome/free-solid-svg-icons/faHeart';

api.library.add(faArrowLeft, faArrowRightToBracket, faHeart);

(() => {
  api.dom.i2svg()
  api.dom.watch()
})();
