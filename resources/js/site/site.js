/**
 * The public site's behaviour ([[00-foundation]]).
 *
 * Alpine, not Vue. The measurement behind that: legacy's Vue is 1,570 LOC on the
 * public site against 10,047 in the dashboard, and the only substantial piece
 * here is the checkout — which is server-priced and server-driven, so there is
 * very little for a framework to do. The dashboard stays Vue; these pages ship
 * no framework beyond this file.
 */
import Alpine from 'alpinejs';

import basket from './stores/basket';
import courseFilter from './components/course-filter';
import menu from './components/menu';

Alpine.store('basket', basket);

Alpine.data('menu', menu);
Alpine.data('courseFilter', courseFilter);

window.Alpine = Alpine;
Alpine.start();
