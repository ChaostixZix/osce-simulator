<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import TiptapEditor from '@/components/TiptapEditor.vue';
import { sanitizeHtml, stripHtml } from '@/utils/sanitize';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from 'axios';

const props = defineProps<{
  patient: any,
  notes: {
    data: any[],
    next_page_url: string,
  },
  can: {
    admin: boolean,
  },
  tz: string,
  errors: any,
  auth: any,
  newNoteId?: number,
}>();

const noteId = ref<number | null>(props.newNoteId || null);
const saving = ref(false);
const saveStatus = ref('');
const editingNoteId = ref<number | null>(null);

const form = useForm({
  subjective: '',
  objective: '',
  assessment: '',
  plan: '',
});

const editForm = useForm({
  subjective: '',
  objective: '',
  assessment: '',
  plan: '',
});

watch(() => props.newNoteId, (newId) => {
  if (newId) {
    noteId.value = newId;
  }
});

// Helper function to refresh timeline data while preserving state
const refreshTimeline = () => {
  router.get(route('soap.page', props.patient.id), {}, {
    preserveState: true,
    preserveScroll: true,
    only: ['notes'],
    onSuccess: (page: any) => {
      timelineNotes.value = page.props.notes.data;
      nextPageUrl.value = page.props.notes.next_page_url;
    }
  });
};


const save = () => {
  if (saving.value) {
    return;
  }

  // Check if form has any content
  const hasContent = form.subjective || form.objective || form.assessment || form.plan;
  if (!hasContent) {
    saveStatus.value = 'Please add some content before saving';
    setTimeout(() => saveStatus.value = '', 3000);
    return;
  }

  saving.value = true;
  saveStatus.value = 'Saving...';

  const onFinish = {
    onSuccess: (page: any) => {
      if (page.props.newNoteId && !noteId.value) {
        noteId.value = page.props.newNoteId;
      }
      saveStatus.value = 'Saved successfully!';
      setTimeout(() => saveStatus.value = '', 3000);
      
      // Update the timeline data without full reload
      refreshTimeline();
    },
    onFinish: () => {
      saving.value = false;
    },
    onError: () => {
      saveStatus.value = 'Error saving';
      setTimeout(() => saveStatus.value = '', 3000);
    }
  };

  if (noteId.value === null) {
    form.post(route('soap.store', props.patient.id), onFinish);
  } else {
    form.put(route('soap.update', noteId.value), onFinish);
  }
};

const editNote = (note: any) => {
  editingNoteId.value = note.id;
  editForm.subjective = note.subjective;
  editForm.objective = note.objective;
  editForm.assessment = note.assessment;
  editForm.plan = note.plan;
};

const saveEdit = (noteIdToSave: number) => {
  editForm.put(route('soap.update', noteIdToSave), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      editingNoteId.value = null;
      editForm.reset();
      // Refresh timeline to show updated note
      refreshTimeline();
    },
    onError: (errors: any) => {
      console.error('Error updating note:', errors);
    }
  });
};

const cancelEdit = () => {
  editingNoteId.value = null;
  editForm.reset();
};

const attachmentsForm = useForm({
  files: [] as File[],
});

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    attachmentsForm.files = Array.from(target.files);
    attachmentsForm.post(route('soap.attach', noteId.value!), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        // Refresh timeline to show new attachments
        refreshTimeline();
        // Reset file input
        if (event.target) {
          (event.target as HTMLInputElement).value = '';
        }
      }
    });
  }
};

const timelineNotes = ref(props.notes.data);
const nextPageUrl = ref(props.notes.next_page_url);
const loadingMore = ref(false);

const loadMoreNotes = () => {
  if (!nextPageUrl.value || loadingMore.value) return;

  loadingMore.value = true;
  router.get(nextPageUrl.value, {}, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['notes'],
    onSuccess: (page: any) => {
      timelineNotes.value.push(...page.props.notes.data);
      nextPageUrl.value = page.props.notes.next_page_url;
    },
    onFinish: () => {
      loadingMore.value = false;
    }
  });
};

const comments = ref<{ [key: number]: any[] }>({});
const newComment = ref<{ [key: number]: string }>({});

const toggleComments = async (note: any) => {
  if (!comments.value[note.id]) {
    const response = await axios.get(route('soap.comments.index', note.id));
    comments.value[note.id] = response.data.data;
  } else {
    delete comments.value[note.id];
  }
};

const postComment = async (note: any) => {
    if (!newComment.value[note.id]) return;
    await axios.post(route('soap.comments.store', note.id), { body: newComment.value[note.id] });
    newComment.value[note.id] = '';
    const response = await axios.get(route('soap.comments.index', note.id));
    comments.value[note.id] = response.data.data;
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
  <Head :title="`SOAP - ${patient.name}`" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">SOAP - {{ patient.name }} ({{ patient.bangsal }} / {{ patient.nomor_kamar }})</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <Card>
          <CardHeader>
            <div class="flex justify-between items-center">
              <CardTitle>SOAP Form</CardTitle>
              <span>{{ saveStatus }}</span>
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label for="subjective">Subjective</Label>
              <TiptapEditor 
                id="subjective" 
                v-model="form.subjective" 
 
                placeholder="Chief complaint, history of present illness, review of systems..."
                min-height="160px"
              />
            </div>
            <div>
              <Label for="objective">Objective</Label>
              <TiptapEditor 
                id="objective" 
                v-model="form.objective" 
 
                placeholder="Vital signs, physical examination findings, diagnostic results..."
                min-height="160px"
              />
            </div>
            <div>
              <Label for="assessment">Assessment</Label>
              <TiptapEditor 
                id="assessment" 
                v-model="form.assessment" 
 
                placeholder="Clinical impression, differential diagnosis, problem list..."
                min-height="160px"
              />
            </div>
            <div>
              <Label for="plan">Plan</Label>
              <TiptapEditor 
                id="plan" 
                v-model="form.plan" 
 
                placeholder="Treatment plan, medications, follow-up instructions, patient education..."
                min-height="160px"
              />
            </div>
            <div class="flex justify-end space-x-4">
              <Button @click="save" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</Button>
            </div>
          </CardContent>
        </Card>

        <Card v-if="noteId" class="mt-6">
            <CardHeader>
                <CardTitle>Attachments</CardTitle>
            </CardHeader>
            <CardContent>
                <Input type="file" multiple @change="handleFileUpload" />
                <ul class="mt-4 space-y-2">
                    <li v-for="attachment in notes.data.find(n => n.id === noteId)?.attachments" :key="attachment.id">
                        <a :href="`/storage/${attachment.path}`" target="_blank">{{ attachment.original_name }}</a>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <div class="mt-6">
          <h3 class="text-lg font-semibold mb-4">Timeline</h3>
          <div class="space-y-4">
            <Card v-for="note in timelineNotes" :key="note.id">
              <CardHeader>
                <div class="flex justify-between">
                  <div>
                    <span class="font-bold">{{ note.author.name }}</span>
                    <Badge :class="note.state === 'draft' ? 'bg-yellow-500' : 'bg-green-500'" class="ml-2">{{ note.state }}</Badge>
                  </div>
                  <span>{{ rel(note.created_at) }}</span>
                </div>
              </CardHeader>
              <CardContent>
                <div v-if="editingNoteId === note.id">
                  <!-- Inline editing mode -->
                  <div class="space-y-4">
                    <div>
                      <Label>Subjective</Label>
                      <TiptapEditor 
                        v-model="editForm.subjective" 
                        placeholder="Chief complaint, history of present illness, review of systems..."
                        min-height="120px"
                      />
                    </div>
                    <div>
                      <Label>Objective</Label>
                      <TiptapEditor 
                        v-model="editForm.objective" 
                        placeholder="Vital signs, physical examination findings, diagnostic results..."
                        min-height="120px"
                      />
                    </div>
                    <div>
                      <Label>Assessment</Label>
                      <TiptapEditor 
                        v-model="editForm.assessment" 
                        placeholder="Clinical impression, differential diagnosis, problem list..."
                        min-height="120px"
                      />
                    </div>
                    <div>
                      <Label>Plan</Label>
                      <TiptapEditor 
                        v-model="editForm.plan" 
                        placeholder="Treatment plan, medications, follow-up instructions, patient education..."
                        min-height="120px"
                      />
                    </div>
                    <div class="flex space-x-2">
                      <Button @click="saveEdit(note.id)" :disabled="editForm.processing">{{ editForm.processing ? 'Saving...' : 'Save' }}</Button>
                      <Button @click="cancelEdit" variant="outline">Cancel</Button>
                    </div>
                  </div>
                </div>
                <div v-else>
                  <!-- Display mode -->
                  <details>
                    <summary>{{ stripHtml(note.subjective).substring(0, 120) }}...</summary>
                    <div class="mt-4 space-y-2">
                      <div><strong>Subjective:</strong> <div v-html="sanitizeHtml(note.subjective)" class="inline"></div></div>
                      <div><strong>Objective:</strong> <div v-html="sanitizeHtml(note.objective)" class="inline"></div></div>
                      <div><strong>Assessment:</strong> <div v-html="sanitizeHtml(note.assessment)" class="inline"></div></div>
                      <div><strong>Plan:</strong> <div v-html="sanitizeHtml(note.plan)" class="inline"></div></div>
                    </div>
                  </details>
                  <!-- Edit button -->
                  <div class="mt-2 flex space-x-2">
                    <Button @click="editNote(note)" variant="outline" size="sm">Edit</Button>
                  </div>
                </div>
                <div class="mt-4">
                  <Button @click="toggleComments(note)" variant="link">Show/Hide Comments</Button>
                  <div v-if="comments[note.id]" class="mt-2 space-y-2">
                    <div v-for="comment in comments[note.id]" :key="comment.id">
                      <p><strong>{{ comment.author.name }}:</strong> {{ comment.body }}</p>
                    </div>
                    <div class="flex space-x-2">
                      <Input v-model="newComment[note.id]" placeholder="Add a comment..." />
                      <Button @click="postComment(note)">Post</Button>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
          <div v-if="loadingMore" class="text-center mt-4">Loading...</div>
          <Button v-if="nextPageUrl" @click="loadMoreNotes" class="mt-4 w-full">Load More</Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
