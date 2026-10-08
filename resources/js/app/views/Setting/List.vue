<script setup>
import { RouterLink } from 'vue-router';
import Badge from '@/components/ui/Badge.vue';
import Collapsible from '@/components/ui/Collapsible.vue';
import EditableListItem from '@/components/list/EditableListItem.vue';
import IconPlus from '@/components/icons/Plus.vue';
import NoResults from '@/components/ui/NoResults.vue';
import { KINDS, usage } from './kinds';

/**
 * One settings list as a collapsible: each row a name and where it is used,
 * a `+` under the list to add one. *Einstellungen* draws its lists with it,
 * and *Software* its own two ([[kinds]]), so each list's links go to the
 * page it sits on.
 */
const props = defineProps({
	kindKey: { type: String, required: true },
	items: { type: Array, required: true },
	expanded: { type: Boolean, default: false },
});

const kind = KINDS[props.kindKey];
const routes = kind.home ? { edit: 'licence.term.edit', create: 'licence.term.create' } : { edit: 'setting.edit', create: 'setting.create' };
const label = (item) => (props.kindKey === 'locations' ? item.description : item.title);
</script>

<template>
	<Collapsible :expanded="expanded" :count="items.length">
		<template #title>{{ kind.title }}</template>

		<EditableListItem
			v-for="item in items"
			:key="item.uuid"
			:edit="{ name: routes.edit, params: { kind: kindKey, uuid: item.uuid } }"
			:dimmed="kindKey === 'locations' && !item.publish"
			wide
		>
			<div class="col-span-12 sm:col-span-4">{{ label(item) }}</div>
			<div class="col-span-12 flex flex-wrap gap-8 pr-40 max-sm:mt-8 sm:col-span-8">
				<Badge v-for="text in usage(kindKey, item)" :key="text">{{ text }}</Badge>
			</div>
		</EditableListItem>
		<NoResults v-if="!items.length">Noch keine erfasst.</NoResults>

		<div class="mt-24 flex">
			<RouterLink :to="{ name: routes.create, params: { kind: kindKey } }" :title="`${kind.noun} hinzufügen`" class="block hover:text-teal">
				<IconPlus size="lg" class="block" />
			</RouterLink>
		</div>
	</Collapsible>
</template>
