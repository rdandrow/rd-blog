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

interface Admin {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

const props = defineProps<{
    admins: Admin[];
}>();

const showCreateDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedUser = ref<Admin | null>(null);

const createForm = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'admin',
});

const editForm = ref({
    role: '',
});

function openCreateDialog() {
    createForm.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'admin',
    };
    showCreateDialog.value = true;
}

function openEditDialog(user: Admin) {
    selectedUser.value = user;
    editForm.value.role = user.role;
    showEditDialog.value = true;
}

function openDeleteDialog(user: Admin) {
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

function updateUserRole() {
    if (!selectedUser.value) return;
    
    router.patch(`/admin/users/${selectedUser.value.id}/role`, editForm.value, {
        onSuccess: () => {
            showEditDialog.value = false;
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

function getRoleBadgeClass(role: string) {
    if (role === 'master_admin') {
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
    }
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
}

function formatRole(role: string) {
    return role === 'master_admin' ? 'Master Admin' : 'Admin';
}
</script>

<template>
    <AppLayout>
        <Head title="Admin Users" />

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold">Admin Users</h2>
                        <p class="text-sm text-muted-foreground">
                            Manage admin and master admin users
                        </p>
                    </div>
                    <Button @click="openCreateDialog">
                        Create Admin User
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
                                        Role
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
                                <tr v-for="admin in admins" :key="admin.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ admin.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ admin.email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="getRoleBadgeClass(admin.role)"
                                        >
                                            {{ formatRole(admin.role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ admin.created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="openEditDialog(admin)"
                                            class="mr-2"
                                        >
                                            Edit Role
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            @click="openDeleteDialog(admin)"
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
                    <DialogTitle>Create Admin User</DialogTitle>
                    <DialogDescription>
                        Add a new admin or master admin user to the system.
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
                    <div class="grid gap-2">
                        <Label for="role">Role</Label>
                        <select
                            id="role"
                            v-model="createForm.role"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="admin">Admin</option>
                            <option value="master_admin">Master Admin</option>
                        </select>
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

        <!-- Edit Role Dialog -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit User Role</DialogTitle>
                    <DialogDescription>
                        Change the role for {{ selectedUser?.name }}
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="edit-role">Role</Label>
                        <select
                            id="edit-role"
                            v-model="editForm.role"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option value="admin">Admin</option>
                            <option value="master_admin">Master Admin</option>
                        </select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="showEditDialog = false">
                        Cancel
                    </Button>
                    <Button @click="updateUserRole">Update Role</Button>
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
