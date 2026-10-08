<script setup>
import Badge from '@/components/ui/Badge.vue';

/**
 * Whether a licence order is paid ([[LicenceOrderResource::payment]]): a free
 * one has no invoice (#35), a priced one is paid when its invoice is.
 */
defineProps({
	payment: { type: String, required: true },
});

const LABELS = {
	free: ['Kostenlos', 'neutral'],
	paid: ['Bezahlt', 'success'],
	open: ['Rechnung offen', 'warning'],
};
</script>

<template>
	<Badge :variant="LABELS[payment]?.[1]">{{ LABELS[payment]?.[0] ?? payment }}</Badge>
</template>
