<script setup>
import { ref } from 'vue';
import { deleteCompanyCourse, fetchCompanyCourse, invoiceCompanyCourse, saveCompanyCourse, withdrawCompanyCourseInvoice } from '@/api/companyCourses';
import { confirm } from '@/composables/useConfirm';
import { toast } from '@/composables/useToast';
import { shortDate } from '@/support/format';
import ActionBox from '@/components/form/ActionBox.vue';
import Button from '@/components/ui/Button.vue';
import ResourceForm from '@/components/form/ResourceForm.vue';

/**
 * *Firmenkurs erfassen* / *bearbeiten* ([[16-firmenkurse]]): the fields are
 * [[CompanyCourseSchema]].
 *
 * Under them the invoice, in the course date's green box: *Rechnung stellen*
 * issues the saved lines and mails the PDF, which locks the form, as a course
 * date that has run is locked. While that invoice is open, *Rechnung
 * stornieren* in the orange box withdraws it and opens the form again.
 */
const busy = ref(false);

const chf = (value) => `CHF ${Number(value).toFixed(2)}`;
const STATUS = { OPEN: 'offen', OVERDUE: 'fällig', PAID: 'bezahlt', CANCELLED: 'storniert' };

async function act(meta, patchMeta, call, question, done) {
	if (!(await confirm(question, meta.name))) return;

	busy.value = true;
	try {
		const now = await call(meta.uuid);
		patchMeta({ locked: now.locked, invoice: now.invoice });
		toast(done);
	} catch (problem) {
		toast(problem.errors?.lines?.[0] ?? problem.message, 'error');
	} finally {
		busy.value = false;
	}
}
</script>

<template>
	<ResourceForm
		schema="company-course"
		:load="fetchCompanyCourse"
		:save="saveCompanyCourse"
		:remove="deleteCompanyCourse"
		:list="{ name: 'backoffice.company-courses' }"
		:edit="(uuid) => ({ name: 'backoffice.company-course.edit', params: { uuid } })"
		noun="Firmenkurs"
		:titles="{ create: 'Firmenkurs erfassen', edit: 'Firmenkurs bearbeiten' }"
		:deletion="{
			title: 'Firmenkurs löschen',
			text: 'Mit dieser Aktion wird der Firmenkurs gelöscht.',
			question: (form, meta) => meta.name,
		}"
		:locked="(meta) => meta.locked"
	>
		<template #actions="{ form, meta, patchMeta, dirty }">
			<ActionBox v-if="meta.invoice && meta.invoice.status !== 'CANCELLED'" tone="success">
				<h2 class="mb-8 font-bold sm:mb-16">Rechnung {{ meta.invoice.number }}</h2>
				<p>
					{{ chf(meta.invoice.grand_total) }}, {{ STATUS[meta.invoice.status] }}<template v-if="meta.invoice.pending">, zahlbar bis {{ shortDate(meta.invoice.due_at) }}</template>.
					<a v-if="meta.invoice.document" :href="meta.invoice.document" class="underline decoration-1 underline-offset-[3px] hover:no-underline">PDF herunterladen</a>
				</p>
			</ActionBox>

			<ActionBox v-else tone="success">
				<h2 class="mb-8 font-bold sm:mb-16">Rechnung stellen</h2>
				<p class="mb-12 lg:mb-16">
					Mit dieser Aktion wird die Rechnung aus den gespeicherten Positionen erstellt und an {{ form.email }} gesendet. Danach kann der Firmenkurs nicht mehr geändert werden, ausser die Rechnung wird storniert.
				</p>
				<p v-if="dirty" class="mb-12 lg:mb-16">Zuerst die Änderungen speichern.</p>
				<div class="mt-12 sm:mt-24">
					<Button variant="success" class="w-full" :disabled="busy || dirty" @click="act(meta, patchMeta, invoiceCompanyCourse, 'Bitte «Rechnung stellen» bestätigen!', 'Rechnung gestellt')">Rechnung stellen</Button>
				</div>
			</ActionBox>

			<ActionBox v-if="meta.invoice?.pending" tone="warning">
				<h2 class="mb-8 font-bold sm:mb-16">Rechnung stornieren</h2>
				<p class="mb-12 lg:mb-16">Mit dieser Aktion wird die Rechnung {{ meta.invoice.number }} storniert, auch in der Buchhaltung. Danach kann der Firmenkurs geändert und neu verrechnet werden. Die Firma wird nicht informiert.</p>
				<div class="mt-12 sm:mt-24">
					<Button variant="warning" class="w-full" :disabled="busy" @click="act(meta, patchMeta, withdrawCompanyCourseInvoice, 'Bitte «Rechnung stornieren» bestätigen!', 'Rechnung storniert')">Stornieren</Button>
				</div>
			</ActionBox>
		</template>
	</ResourceForm>
</template>
