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
import basketList from './components/basket-list';
import bookmark from './components/bookmark';
import courseFilter from './components/course-filter';
import menu from './components/menu';
import toast from './stores/toast';

Alpine.store('basket', basket);
Alpine.store('toast', toast);

/*
 * The course filter is *one* mechanism, not two. The server still filters by
 * query string — that is what a shared link, a crawler and a browser without
 * JavaScript get — and this only decides which of the already-rendered cards
 * are hidden, so the phone's full-screen panel survives a selection instead of
 * being closed by the navigation. See `components/course-filter.js`.
 */
Alpine.data('courseFilter', courseFilter);
Alpine.data('menu', menu);
Alpine.data('bookmark', bookmark);
Alpine.data('basketList', basketList);

window.Alpine = Alpine;
Alpine.start();
