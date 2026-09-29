<script setup>
import { ref } from 'vue';
import { fetchStudent, saveStudent, setStudentActive } from '@/api/students';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { shortDate } from '@/support/format';
import Button from '@/components/ui/Button.vue';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Student hinzufügen* / *bearbeiten* — legacy's `views/student/Form.vue`
 * ([[07-dashboard]], step 6): the fields are [[StudentSchema]].
 *
 * **Deactivated, never deleted** (#16): where legacy's red box said *Student
 * löschen*, this one deactivates the account, and reactivates it. Bookings,
 * invoices and documents keep pointing at a real person; the person can no
 * longer sign in, and a session they have open ends.
 */
const busy = ref(false);

async function toggle(form, meta, patchMeta) {
	const active = Boolean(meta.deactivated_at);
	const name = `${form.first_name} ${form.last_name}, ${form.email}`;

	if (!active && !(await confirm('Konto deaktivieren?', `${name}\nKann sich danach nicht mehr anmelden.`))) return;

	busy.value = true;
	try {
		const record = await setStudentActive(meta.uuid, active);
		patchMeta({ deactivated_at: record.deactivated_at });
		toast(active ? 'Konto reaktiviert' : 'Konto deaktiviert');
	} catch (problem) {
		toast(problem.message, 'error');
	} finally {
		busy.value = false;
	}
}
</script>

<template>
	<ResourceForm
		schema="student"
		:load="fetchStudent"
		:save="saveStudent"
		:list="{ name: 'students' }"
		:edit="(uuid) => ({ name: 'student.edit', params: { uuid } })"
		noun="Student"
		:titles="{ create: 'Student hinzufügen', edit: 'Student bearbeiten' }"
		:note="(meta) => (meta.deactivated_at ? `Dieses Konto ist seit dem ${shortDate(meta.deactivated_at.slice(0, 10))} deaktiviert.` : null)"
	>
		<template #danger="{ form, meta, patchMeta }">
			<template v-if="meta.deactivated_at">
				<h2 class="mb-8 font-bold sm:mb-16">Konto reaktivieren</h2>
				<p class="mb-12 lg:mb-16">Mit dieser Aktion kann sich der Student wieder anmelden und buchen.</p>
				<div class="mt-12 sm:mt-24">
					<Button variant="danger" class="w-full" :disabled="busy" @click="toggle(form, meta, patchMeta)">Reaktivieren</Button>
				</div>
			</template>
			<template v-else>
				<h2 class="mb-8 font-bold sm:mb-16">Konto deaktivieren</h2>
				<p v-if="meta.is_self">Du kannst dein eigenes Konto nicht deaktivieren.</p>
				<template v-else>
					<p class="mb-12 lg:mb-16">Mit dieser Aktion kann sich der Student nicht mehr anmelden. Buchungen, Rechnungen und Dokumente bleiben erhalten.</p>
					<div class="mt-12 sm:mt-24">
						<Button variant="danger" class="w-full" :disabled="busy" @click="toggle(form, meta, patchMeta)">Deaktivieren</Button>
					</div>
				</template>
			</template>
		</template>
	</ResourceForm>
</template>
