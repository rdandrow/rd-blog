<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Member {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

const props = defineProps<{
    members: Member[];
}>();

const showCreateDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedUser = ref<Member | null>(null);

const createForm = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'member',
});

function openCreateDialog() {
    createForm.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'member',
    };
    showCreateDialog.value = true;
}

function openDeleteDialog(user: Member) {
    selectedUser.value = user;
    showDeleteDialog.value = true;
}

function createUser() {
    router.post('/admin/users', createForm.value, {
        onSuccess: () => {
            showCreateDialog.value = false;
        },
    });
}

function deleteUser() {
    if (!selectedUser.value) return;
    
    router.delete(`/admin/users/${selectedUser.value.id}`, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Member Users" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold">Member Users</h2>
                        <p class="text-sm text-muted-foreground">
                            Manage member users
                        </p>
                    </div>
                    <Button @click="openCreateDialog">
                        Create Member User
                    </Button>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Email
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Created
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                <tr v-for="member in members" :key="member.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ member.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ member.email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ member.created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            @click="openDeleteDialog(member)"
                                        >
                                            Delete
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create User Dialog -->
        <Dialog v-model:open="showCreateDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create Member User</DialogTitle>
                    <DialogDescription>
                        Add a new member user to the system.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="createForm.name"
                            placeholder="Full name"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="createForm.email"
                            type="email"
                            placeholder="email@example.com"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
                        <Input
                            id="password"
                            v-model="createForm.password"
                            type="password"
                            placeholder="Password"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm Password</Label>
                        <Input
                            id="password_confirmation"
                            v-model="createForm.password_confirmation"
                            type="password"
                            placeholder="Confirm password"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showCreateDialog = false">
                        Cancel
                    </Button>
                    <Button @click="createUser">Create User</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete User Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete User</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete {{ selectedUser?.name }}? This action
                        cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="deleteUser">
                        Delete User
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
