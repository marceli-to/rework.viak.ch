import { ref } from 'vue';

/**
 * Drag a list into a new order with the browser's own drag and drop
 * ([[07-dashboard]]) — no library, which is all the accordion on *Kurse* needs.
 *
 *   const { dragging, handlers } = useSortable(list, onDrop)
 *   <section v-for="(item, index) in list" draggable="true" v-bind="handlers(index)">
 *
 * The list is reordered live while dragging, so what the admin sees is what
 * will be saved; `onDrop` runs once, when the item is let go somewhere new.
 */
export function useSortable(list, onDrop) {
	const dragging = ref(null);
	let start = null;

	function handlers(index) {
		return {
			onDragstart(event) {
				dragging.value = index;
				start = index;
				event.dataTransfer.effectAllowed = 'move';
				// Firefox will not start a drag without some data on it.
				event.dataTransfer.setData('text/plain', String(index));
			},
			onDragover(event) {
				event.preventDefault();
				if (dragging.value === null || dragging.value === index) return;

				const [item] = list.value.splice(dragging.value, 1);
				list.value.splice(index, 0, item);
				dragging.value = index;
			},
			onDragend() {
				const moved = start !== null && dragging.value !== start;
				dragging.value = null;
				start = null;
				if (moved) onDrop(list.value);
			},
		};
	}

	return { dragging, handlers };
}
