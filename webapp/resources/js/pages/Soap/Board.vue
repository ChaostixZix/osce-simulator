<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Pagination, PaginationContent, PaginationEllipsis, PaginationFirst, PaginationLast, PaginationItem, PaginationNext, PaginationPrevious } from '@/components/ui/pagination'

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
  status: props.filters.status || '',
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
            <div class="flex space-x-4">
              <Input v-model="filters.search" placeholder="Search by patient name..." @keyup.enter="search" />
              <Select v-model="filters.status">
                <SelectTrigger>
                  <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All</SelectItem>
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
              <CardTitle>{{ patient.name }}</CardTitle>
            </CardHeader>
            <CardContent>
              <p>{{ patient.bangsal }} / {{ patient.nomor_kamar }}</p>
              <div class="mt-4 flex space-x-2">
                <Badge>Total SOAP: {{ patient.soap_notes_count }}</Badge>
                <Badge v-if="patient.soap_notes.length">{{ rel(patient.soap_notes[0].created_at) }}</Badge>
              </div>
              <Link :href="route('soap.page', patient.id)" class="mt-4 inline-block text-blue-500">
                Open SOAP
              </Link>
            </CardContent>
          </Card>
        </div>

        <div class="mt-6">
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
</template>
