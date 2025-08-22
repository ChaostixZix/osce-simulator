<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Pagination, PaginationContent, PaginationEllipsis, PaginationFirst, PaginationLast, PaginationItem, PaginationNext, PaginationPrevious } from '@/components/ui/pagination'
import AppModal from '@/components/modal/AppModal.vue';
import InitialAvatar from '@/components/avatar/InitialAvatar.vue';
import SoapNovelEditorClean from '@/components/SoapNovelEditorClean.vue';
import { Label } from '@/components/ui/label';
import { sanitizeHtml, stripHtml } from '@/utils/sanitize';
import axios from 'axios';

const props = defineProps<{
  patients: {
    data: any[],
    links: any[],
    current_page: number,
    last_page: number,
    prev_page_url: string,
    next_page_url: string,
  },
  filters: {
    status?: string,
    search?: string,
    sort?: string,
  }
}>();

const filters = ref({
  status: props.filters.status || 'all',
  search: props.filters.search || '',
  sort: props.filters.sort || 'name',
});

watch(filters, () => {
  router.get(route('soap.board'), filters.value, {
    preserveState: true,
    replace: true,
  });
}, { deep: true });

const search = () => {
  router.get(route('soap.board'), filters.value, {
    preserveState: true,
    replace: true,
  });
};

function rel(t: string): string {
  const d = (Date.now() - new Date(t).getTime()) / 1000;
  if (d < 60) return `${Math.floor(d)}s ago`;
  if (d < 3600) return `${Math.floor(d / 60)}m ago`;
  if (d < 86400) return `${Math.floor(d / 3600)}h ago`;
  return `${Math.floor(d / 86400)}d ago`;
}

// Add Patient modal state and form
const showAddPatient = ref(false);
const patientForm = useForm({ name: '', bangsal: '', nomor_kamar: '', status: 'active' });
const submitPatient = () => {
  patientForm.post(route('patients.store'), {
    preserveScroll: true,
    onSuccess: () => {
      showAddPatient.value = false;
      // Refresh board without full reload
      router.get(route('soap.board'), filters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      });
    },
    onError: () => {
      showAddPatient.value = true;
    }
  });
};

// SOAP modal state and data
const showSoap = ref(false);
const selectedPatientId = ref<number | null>(null);
const soapPatient = ref<any | null>(null);
const soapNotes = ref<any[]>([]);
const soapNextPageUrl = ref<string | null>(null);
const soapLoading = ref(false);

const openSoapModal = async (patientId: number) => {
  selectedPatientId.value = patientId;
  showSoap.value = true;
  await loadSoapData(patientId);
};

const loadSoapData = async (patientId: number, url?: string) => {
  try {
    soapLoading.value = true;
    const endpoint = url || route('api.soap.patient', patientId);
    const { data } = await axios.get(endpoint);
    soapPatient.value = data.patient;
    soapNotes.value = data.notes.data;
    soapNextPageUrl.value = data.notes.next_page_url;
  } finally {
    soapLoading.value = false;
  }
};

const loadMoreSoapNotes = async () => {
  if (!soapNextPageUrl.value || soapLoading.value) return;
  soapLoading.value = true;
  try {
    const { data } = await axios.get(soapNextPageUrl.value);
    soapNotes.value.push(...data.notes.data);
    soapNextPageUrl.value = data.notes.next_page_url;
  } finally {
    soapLoading.value = false;
  }
};

function simpleText(content: any): string {
  return stripHtml(JSON.stringify(content || ''));
}

function preview(content: any): string {
  const raw = simpleText(content);
  return raw.substring(0, 120) || 'Empty';
}

// Composer modal inside SOAP modal
const showComposer = ref(false);
const noteForm = useForm({ subjective: {}, objective: {}, assessment: {}, plan: {} });
const composerSaving = ref(false);
const submitNote = async () => {
  if (!selectedPatientId.value || composerSaving.value) return;
  try {
    composerSaving.value = true;
    await axios.post(route('api.soap.notes.store', selectedPatientId.value), noteForm.data());
    showComposer.value = false;
    await loadSoapData(selectedPatientId.value!);
    noteForm.reset();
  } catch (e) {
    // Leave modal open for inline errors if any future validation added
    console.error(e);
  } finally {
    composerSaving.value = false;
  }
};

</script>

<template>
  <Head title="SOAP Patient Board" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">SOAP Patient Board</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <Card>
          <CardHeader>
            <CardTitle>Filter & Sort</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex flex-wrap items-center gap-3 md:gap-4">
              <Input v-model="filters.search" placeholder="Search by patient name..." @keyup.enter="search" class="flex-1 min-w-[220px]" />
              <Button class="whitespace-nowrap" @click="showAddPatient = true">Add Patient</Button>
              <Select v-model="filters.status">
                <SelectTrigger>
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="discharged">Discharged</SelectItem>
                </SelectContent>
              </Select>
              <Select v-model="filters.sort">
                <SelectTrigger>
                  <SelectValue placeholder="Sort by" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="name">Name</SelectItem>
                  <SelectItem value="admission">Admission Date</SelectItem>
                  <SelectItem value="latest">Latest SOAP</SelectItem>
                </SelectContent>
              </Select>
              <Button @click="search">Search</Button>
            </div>
          </CardContent>
        </Card>


        <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <Card v-for="patient in patients.data" :key="patient.id">
            <CardHeader>
              <CardTitle>
                <div class="flex items-center gap-2">
                  <InitialAvatar :name="patient.name" size="sm" />
                  <button type="button" class="text-blue-600 hover:underline" @click="openSoapModal(patient.id)">{{ patient.name }}</button>
                </div>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p>{{ patient.bangsal }} / {{ patient.nomor_kamar }}</p>
              <div class="mt-4 flex space-x-2">
                <Badge>Total SOAP: {{ patient.soap_notes_count }}</Badge>
                <Badge v-if="patient.soap_notes.length">{{ rel(patient.soap_notes[0].created_at) }}</Badge>
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="mt-6" v-if="patients.last_page > 1">
            <Pagination v-slot="{ page }" :total="patients.last_page * 10" :sibling-count="1" show-edges :default-page="patients.current_page">
                <PaginationContent v-slot="{ items }" class="flex items-center justify-center gap-1">
                    <PaginationFirst :href="patients.links[0].url" />
                    <PaginationPrevious :href="patients.prev_page_url" />

                    <template v-for="(item, index) in items">
                        <PaginationItem v-if="item.type === 'page'" :key="index" :value="item.value" as-child>
                             <Button class="w-10 h-10 p-0" :variant="item.value === page ? 'default' : 'outline'" :href="item.value === page ? null : `/soap?page=${item.value}`">
                                {{ item.value }}
                            </Button>
                        </PaginationItem>
                        <PaginationEllipsis v-else :key="item.type" :index="index" />
                    </template>

                    <PaginationNext :href="patients.next_page_url" />
                    <PaginationLast :href="patients.links[patients.links.length-1].url" />
                </PaginationContent>
            </Pagination>
        </div>
      </div>
    </div>
  </AppLayout>

  <!-- Add Patient Modal -->
  <AppModal v-model="showAddPatient" title="Add New Patient" width="md">
    <form @submit.prevent="submitPatient" class="space-y-4">
      <div>
        <Label for="name">Name</Label>
        <Input id="name" v-model="patientForm.name" type="text" required />
        <div v-if="patientForm.errors.name" class="text-sm text-red-600 mt-1">{{ patientForm.errors.name }}</div>
      </div>
      <div>
        <Label for="bangsal">Bangsal (Ward)</Label>
        <Input id="bangsal" v-model="patientForm.bangsal" type="text" required />
        <div v-if="patientForm.errors.bangsal" class="text-sm text-red-600 mt-1">{{ patientForm.errors.bangsal }}</div>
      </div>
      <div>
        <Label for="nomor_kamar">Nomor Kamar (Room Number)</Label>
        <Input id="nomor_kamar" v-model="patientForm.nomor_kamar" type="text" required />
        <div v-if="patientForm.errors.nomor_kamar" class="text-sm text-red-600 mt-1">{{ patientForm.errors.nomor_kamar }}</div>
      </div>
      <div>
        <Label for="status">Status</Label>
        <Select v-model="patientForm.status">
          <SelectTrigger>
            <SelectValue placeholder="Select status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="discharged">Discharged</SelectItem>
          </SelectContent>
        </Select>
        <div v-if="patientForm.errors.status" class="text-sm text-red-600 mt-1">{{ patientForm.errors.status }}</div>
      </div>
      <div class="flex justify-end gap-2">
        <Button type="button" variant="outline" @click="showAddPatient = false">Cancel</Button>
        <Button type="submit" :disabled="patientForm.processing">Save</Button>
      </div>
    </form>
  </AppModal>

  <!-- SOAP Modal with Timeline -->
  <AppModal v-model="showSoap" :title="soapPatient ? `SOAP - ${soapPatient.name}` : 'SOAP'" width="xl">
    <div v-if="soapPatient" class="space-y-4">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">{{ soapPatient.bangsal }} / {{ soapPatient.nomor_kamar }}</div>
        <Button size="sm" @click="showComposer = true">Add SOAP Timeline</Button>
      </div>

      <div class="space-y-3">
        <div v-for="note in soapNotes" :key="note.id" class="border rounded p-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <InitialAvatar :name="note.author?.name" size="sm" />
              <span class="font-medium">{{ note.author?.name }}</span>
              <Badge :class="note.state === 'draft' ? 'bg-yellow-500' : 'bg-green-500'">{{ note.state }}</Badge>
            </div>
            <span class="text-xs text-gray-500">{{ rel(note.created_at) }}</span>
          </div>
          <details class="mt-2">
            <summary class="cursor-pointer text-sm text-gray-700">{{ preview(note.subjective) }}...</summary>
            <div class="mt-2 text-sm text-gray-800">
              <div class="prose prose-sm max-w-none" v-html="sanitizeHtml(simpleText(note.subjective))"></div>
            </div>
          </details>
        </div>
        <div v-if="soapLoading" class="text-center text-sm">Loading...</div>
        <Button v-if="soapNextPageUrl" @click="loadMoreSoapNotes" class="w-full" variant="outline" size="sm">Load More</Button>
      </div>
    </div>
  </AppModal>

  <!-- Composer Modal -->
  <AppModal v-model="showComposer" title="Add SOAP Timeline" width="lg">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <Label>Subjective</Label>
        <SoapNovelEditorClean v-model="noteForm.subjective" />
      </div>
      <div>
        <Label>Objective</Label>
        <SoapNovelEditorClean v-model="noteForm.objective" />
      </div>
      <div>
        <Label>Assessment</Label>
        <SoapNovelEditorClean v-model="noteForm.assessment" />
      </div>
      <div>
        <Label>Plan</Label>
        <SoapNovelEditorClean v-model="noteForm.plan" />
      </div>
    </div>
    <template #actions>
      <Button type="button" variant="outline" @click="showComposer = false">Cancel</Button>
      <Button type="button" :disabled="composerSaving" @click="submitNote">Save</Button>
    </template>
  </AppModal>

</template>
