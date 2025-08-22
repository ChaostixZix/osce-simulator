<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';

const form = useForm({
  name: '',
  bangsal: '',
  nomor_kamar: '',
  status: 'active',
});

const submit = () => {
  form.post(route('patients.store'));
};
</script>

<template>
  <Head title="Add New Patient" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Patient</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <Card>
          <CardHeader>
            <CardTitle>Patient Information</CardTitle>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" class="space-y-4">
              <div>
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" type="text" required />
                <InputError :message="form.errors.name" class="mt-2" />
              </div>
              <div>
                <Label for="bangsal">Bangsal (Ward)</Label>
                <Input id="bangsal" v-model="form.bangsal" type="text" required />
                <InputError :message="form.errors.bangsal" class="mt-2" />
              </div>
              <div>
                <Label for="nomor_kamar">Nomor Kamar (Room Number)</Label>
                <Input id="nomor_kamar" v-model="form.nomor_kamar" type="text" required />
                <InputError :message="form.errors.nomor_kamar" class="mt-2" />
              </div>
              <div>
                <Label for="status">Status</Label>
                <Select v-model="form.status">
                  <SelectTrigger>
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="discharged">Discharged</SelectItem>
                  </SelectContent>
                </Select>
                <InputError :message="form.errors.status" class="mt-2" />
              </div>
              <div class="flex items-center justify-end">
                <Button :disabled="form.processing">
                  Save Patient
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
